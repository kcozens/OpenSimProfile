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
using OpenSim.Region.Framework.Interfaces;
using OpenSim.Region.Framework.Scenes;
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

			// Classifieds
			client.AddGenericPacketHandler("avatarclassifiedsrequest", HandleAvatarClassifiedsRequest);
			client.OnClassifiedInfoUpdate += ClassifiedInfoUpdate;
			client.OnClassifiedDelete += ClassifiedDelete;

			// Picks
			client.AddGenericPacketHandler("avatarpicksrequest", HandleAvatarPicksRequest);
			client.AddGenericPacketHandler("pickinforequest", HandlePickInfoRequest);
			client.OnPickInfoUpdate += PickInfoUpdate;
			client.OnPickDelete += PickDelete;

			// Notes
			client.AddGenericPacketHandler("avatarnotesrequest", HandleAvatarNotesRequest);
			client.OnAvatarNotesUpdate += AvatarNotesUpdate;
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

		// Classifieds Handler

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

		// Classifieds Update

		public void ClassifiedInfoUpdate(UUID queryclassifiedID, uint queryCategory, string queryName, string queryDescription, UUID queryParcelID, 
										uint queryParentEstate, UUID querySnapshotID, Vector3 queryGlobalPos, byte queryclassifiedFlags, 
										int queryclassifiedPrice, IClientAPI remoteClient)
		{
			Hashtable ReqHash = new Hashtable();

			ReqHash["classifiedUUID"] = queryclassifiedID.ToString();
			ReqHash["category"] = queryCategory;
			ReqHash["name"] = queryName;
			ReqHash["description"] = queryDescription;
			ReqHash["parcelUUID"] = queryParcelID.ToString();
			ReqHash["parentestate"] = queryParentEstate;
			ReqHash["snapshotUUID"] = querySnapshotID.ToString();
			ReqHash["globalpos"] = queryGlobalPos;
			ReqHash["classifiedFlags"] = queryclassifiedFlags;
			ReqHash["classifiedPrice"] = queryclassifiedPrice;

			Hashtable result = GenericXMLRPCRequest(ReqHash,
					"classified_update");

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}
		}

		// Classifieds Delete

		public void ClassifiedDelete (UUID queryClassifiedID, IClientAPI remoteClient)
		{
			Hashtable ReqHash = new Hashtable();

			ReqHash["classifiedID"] = queryClassifiedID.ToString();

			Hashtable result = GenericXMLRPCRequest(ReqHash,
					"classified_delete");

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}
		}

		// Picks Handler

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

		// Picks Request

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

			remoteClient.SendPickInfoReply(
                    new UUID(d["pickuuid"].ToString()),
                    new UUID(d["creatoruuid"].ToString()),
					Convert.ToBoolean(d["toppick"]),
					new UUID(d["parceluuid"].ToString()),
					d["name"].ToString(),
					d["description"].ToString(),
					new UUID(d["snapshotuuid"].ToString()),
					d["user"].ToString(),
					d["originalname"].ToString(),
					d["simname"].ToString(),
					globalPos,
					Convert.ToInt32(d["sortorder"]),
					Convert.ToBoolean(d["enabled"]));
		}

		// Picks Update

		public void PickInfoUpdate(IClientAPI remoteClient, UUID pickID, UUID creatorID, bool topPick, string name, string desc, UUID snapshotID, int sortOrder, bool enabled)
		{
			Hashtable ReqHash = new Hashtable();
			
			Hashtable result = GenericXMLRPCRequest(ReqHash,
					"picks_update");

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}
		}

		// Picks Delete

		public void PickDelete(IClientAPI remoteClient, UUID queryPickID)
		{
			Hashtable ReqHash = new Hashtable();
			
			ReqHash["pick_id"] = queryPickID;

			Hashtable result = GenericXMLRPCRequest(ReqHash,
					"picks_delete");

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}
		}

		// Notes Handler

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

            if (dataArray != null && dataArray[0] != null)
            {
                Hashtable d = (Hashtable)dataArray[0];

                remoteClient.SendAvatarNotesReply(
                                new UUID(d["targetid"].ToString()),
                                d["notes"].ToString());
            }
            else
            {
                remoteClient.SendAvatarNotesReply(
                                new UUID(ReqHash["uuid"].ToString()),
                                "");
            }
		}

		// Notes Update

		public void AvatarNotesUpdate(IClientAPI remoteClient, UUID queryTargetID, string queryNotes)
		{
			Hashtable ReqHash = new Hashtable();
			
			ReqHash["avatar_id"] = remoteClient.AgentId.ToString();
			ReqHash["target_id"] = queryTargetID;
			ReqHash["notes"] = queryNotes;

			Hashtable result = GenericXMLRPCRequest(ReqHash,
					"avatar_notes_update");

			if (!Convert.ToBoolean(result["success"]))
			{
				remoteClient.SendAgentAlertMessage(
						result["errorMessage"].ToString(), false);
				return;
			}
		}
	}
}
