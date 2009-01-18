using System;
using System.Collections;
using System.Collections.Generic;
using System.Globalization;
using System.Net;
using System.Net.Sockets;
using System.Reflection;
using System.Xml;
using OpenMetaverse;
using log4net;
using Nini.Config;
using Nwc.XmlRpc;
using OpenSim.Framework;
using OpenSim.Region.Interfaces;
using OpenSim.Region.Environment.Interfaces;
using OpenSim.Region.Environment.Scenes;
using OpenSim.Framework.Communications.Cache;

namespace OpenSimProfile.Modules.OpenProfile
{
	public class OpenProfileModule : IRegionModule
	{
		//
		// Log module
		//
		private static readonly ILog m_log = LogManager.GetLogger(MethodBase.GetCurrentMethod().DeclaringType);

		//
		// Module vars
		//
		private IConfigSource m_gConfig;
		private List<Scene> m_Scenes = new List<Scene>();
		private string m_ProfileServer = "";
		private bool m_Enabled = true;

		public void Initialise(Scene scene, IConfigSource config)
		{
			if (!m_Enabled)
				return;

			IConfig profileConfig = config.Configs["Profile"];

			if (m_Scenes.Count == 0) // First time
			{
				if (profileConfig == null)
				{
					m_log.Info("[PROFILE] Not configured, disabling");
					m_Enabled = false;
					return;
				}
				m_ProfileServer = profileConfig.GetString("ProfileURL", "");
				if (m_ProfileServer == "")
				{
					m_log.Error("[PROFILE] No profile server, disabling profiles");
					m_Enabled = false;
					return;
				}
				else
				{
					m_log.Info("[PROFILE] Profile module is activated");
					m_Enabled = true;
				}
			}

			if (!m_Scenes.Contains(scene))
				m_Scenes.Add(scene);

			m_gConfig = config;

			// Hook up events
			scene.EventManager.OnNewClient += OnNewClient;
		}

		public void PostInitialise()
		{
			if (!m_Enabled)
				return;
		}

		public void Close()
		{
		}

		public string Name
		{
			get { return "ProfileModule"; }
		}

		public bool IsSharedModule
		{
			get { return true; }
		}

		/// New Client Event Handler
		private void OnNewClient(IClientAPI client)
		{
			// Subscribe to messages
			client.AddGenericPacketHandler("avatarclassifiedsrequest", HandleAvatarClassifiedsRequest);
			client.AddGenericPacketHandler("avatarpicksrequest", HandleAvatarPicksRequest);
			client.AddGenericPacketHandler("avatarnotesrequest", HandleAvatarNotesRequest);
			// Handle the Classifieds / Picks reading part
			client.AddGenericPacketHandler("pickinforequest", HandlePickInfoRequest);
		}

		//
		// Make external XMLRPC request
		//
		private Hashtable GenericXMLRPCRequest(Hashtable ReqParams, string method)
		{
			ArrayList SendParams = new ArrayList();
			SendParams.Add(ReqParams);

			// Send Request
			XmlRpcResponse Resp;
			try
			{
				XmlRpcRequest Req = new XmlRpcRequest(method, SendParams);
				Resp = Req.Send(m_ProfileServer, 30000);
			}
			catch (WebException ex)
			{
				m_log.ErrorFormat("[PROFILE]: Unable to connect to Profile " +
						"Server {0}.  Exception {1}", m_ProfileServer, ex);

				Hashtable ErrorHash = new Hashtable();
				ErrorHash["success"] = false;
				ErrorHash["errorMessage"] = "Unable to search at this time. ";
				ErrorHash["errorURI"] = "";

				return ErrorHash;
			}
			catch (SocketException ex)
			{
				m_log.ErrorFormat(
						"[PROFILE]: Unable to connect to Profile Server {0}. " +
						"Exception {1}", m_ProfileServer, ex);

				Hashtable ErrorHash = new Hashtable();
				ErrorHash["success"] = false;
				ErrorHash["errorMessage"] = "Unable to search at this time. ";
				ErrorHash["errorURI"] = "";

				return ErrorHash;
			}
			catch (XmlException ex)
			{
				m_log.ErrorFormat(
						"[PROFILE]: Unable to connect to Profile Server {0}. " +
						"Exception {1}", m_ProfileServer, ex);

				Hashtable ErrorHash = new Hashtable();
				ErrorHash["success"] = false;
				ErrorHash["errorMessage"] = "Unable to search at this time. ";
				ErrorHash["errorURI"] = "";

				return ErrorHash;
			}
			if (Resp.IsFault)
			{
				Hashtable ErrorHash = new Hashtable();
				ErrorHash["success"] = false;
				ErrorHash["errorMessage"] = "Unable to search at this time. ";
				ErrorHash["errorURI"] = "";
				return ErrorHash;
			}
			Hashtable RespData = (Hashtable)Resp.Value;

			return RespData;
		}

		public void HandleAvatarClassifiedsRequest(Object sender, string method, List<String> args) 
		{
            if (!(sender is IClientAPI))
                return;

            IClientAPI remoteClient = (IClientAPI)sender;

			Hashtable ReqHash = new Hashtable();
			ReqHash["uuid"] = args[0];
			
			Hashtable result = GenericXMLRPCRequest(ReqHash,
					method);

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}

			ArrayList dataArray = (ArrayList)result["data"];

            Dictionary<UUID, string> classifieds = new Dictionary<UUID, string>();

			foreach (Object o in dataArray)
			{
				Hashtable d = (Hashtable)o;
				
                classifieds[new UUID(d["classifiedid"].ToString())] = d["name"].ToString();
			}

			remoteClient.SendAvatarClassifiedReply(remoteClient.AgentId, 
						classifieds);
		}

		public void HandleAvatarPicksRequest(Object sender, string method, List<String> args) 
		{
            if (!(sender is IClientAPI))
                return;

            IClientAPI remoteClient = (IClientAPI)sender;

			Hashtable ReqHash = new Hashtable();
			ReqHash["uuid"] = args[0];
			
			Hashtable result = GenericXMLRPCRequest(ReqHash,
					method);

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}

			ArrayList dataArray = (ArrayList)result["data"];

            Dictionary<UUID, string> picks = new Dictionary<UUID, string>();

            if (dataArray != null)
            {
                foreach (Object o in dataArray)
                {
                        Hashtable d = (Hashtable)o;
                    
                    picks[new UUID(d["pickid"].ToString())] = d["name"].ToString();
                }
            }

			remoteClient.SendAvatarPicksReply(remoteClient.AgentId, 
						picks);		
		}

		public void HandleAvatarNotesRequest(Object sender, string method, List<String> args) 
		{
            if (!(sender is IClientAPI))
                return;

            IClientAPI remoteClient = (IClientAPI)sender;

			Hashtable ReqHash = new Hashtable();

			ReqHash["avatar_id"] = remoteClient.AgentId.ToString();
			ReqHash["uuid"] = args[0];
			
			Hashtable result = GenericXMLRPCRequest(ReqHash,
					method);

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}

			ArrayList dataArray = (ArrayList)result["data"];

			Hashtable d = (Hashtable)dataArray[0];

			remoteClient.SendAvatarNotesReply(
							new UUID(d["targetid"].ToString()),
							d["notes"].ToString());
		}

		public void HandlePickInfoRequest(Object sender, string method, List<String> args) 
		{
            if (!(sender is IClientAPI))
                return;

            IClientAPI remoteClient = (IClientAPI)sender;

			Hashtable ReqHash = new Hashtable();

			ReqHash["avatar_id"] = args[0];
			ReqHash["pick_id"] = args[1];
			
			Hashtable result = GenericXMLRPCRequest(ReqHash,
					method);

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}

			ArrayList dataArray = (ArrayList)result["data"];

			Hashtable d = (Hashtable)dataArray[0];

            Vector3 globalPos = new Vector3();
			Vector3.TryParse(d["posglobal"].ToString(), out globalPos);

//			remoteClient.SendPickInfoReply(
//                    new UUID(d["pickuuid"].ToString()),
//                    new UUID(d["creatoruuid"].ToString()),
//					d["toppick"].ToString(),
//					new UUID(d["parceluuid"].ToString()),
//					d["name"].ToString(),
//					d["description"].ToString(),
//					new UUID(d["snapshotuuid"].ToString()),
//					d["user"].ToString(),
//					d["originalname"].ToString(),
//					globalPos,
//					d["sortorder"].ToString(),
//					d["enabled"].ToString());
		}
	}
}
