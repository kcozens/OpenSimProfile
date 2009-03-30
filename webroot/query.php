<?PHP

include("databaseinfo.php");

//
// Search DB
//
mysql_connect ($DB_HOST, $DB_USER, $DB_PASSWORD);
mysql_select_db ($DB_NAME);

#
#  Copyright (c)Melanie Thielker (http://opensimulator.org/)
#

###################### No user serviceable parts below #####################

#
# The XMLRPC server object
#

$xmlrpc_server = xmlrpc_server_create();

#
# Places Query
#

xmlrpc_server_register_method($xmlrpc_server, "avatarclassifiedsrequest",
		"avatarclassifiedsrequest");

function avatarclassifiedsrequest($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['uuid'];

		
	$result = mysql_query("select * from ossearch.classifieds where ".
			"creatoruuid = '". mysql_escape_string($uuid) ."'");

	$data = array();

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"classifiedid" => $row["classifieduuid"],
				"name" => $row["name"]);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "avatarpicksrequest",
		"avatarpicksrequest");

function avatarpicksrequest($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['uuid'];

	$result = mysql_query("select * from userpicks where ".
			"creatoruuid = '". mysql_escape_string($uuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"pickid" => $row["pickuuid"],
				"name" => $row["name"]);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "avatarnotesrequest",
		"avatarnotesrequest");

function avatarnotesrequest($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['uuid'];
	$targetuuid		= $req['avatar_id'];

	$result = mysql_query("select * from usernotes where ".
			"useruuid = '". mysql_escape_string($uuid) ."' AND ".
			"targetuuid = '". mysql_escape_string($targetuuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"targetid" => $row["targetuuid"],
				"notes" => $row["notes"]);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "pickinforequest",
		"pickinforequest");

function pickinforequest($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['avatar_id'];
	$pick			= $req['pick_id'];

	$result = mysql_query("select * from userpicks where ".
			"creatoruuid = '". mysql_escape_string($uuid) ."' AND ".
			"pickuuid = '". mysql_escape_string($pick) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"pickuuid" => $row["pickuuid"],
				"creatoruuid" => $row["creatoruuid"],
				"toppick" => $row["toppick"],
				"parceluuid" => $row["parceluuid"],
				"name" => $row["name"],
				"description" => $row["desc"],
				"snapshotuuid" => $row["snapshotuuid"],
				"user" => $row["user"],
				"originalname" => $row["originalname"],
				"simname" => $row["simname"],
				"posglobal" => $row["posglobal"],
				"sortorder"=> $row["sortorder"],
				"enabled" => $row["enabled"]);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "classified_delete",
		"classified_delete");

function classified_delete($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$classifieduuid		= $req['classifiedID'];

	$result = mysql_query("delete from ossearch.classifieds where ".
			"classifieduuid = '".mysql_escape_string($classifieduuid) ."'");
	
	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}


#
# Process the request
#

$request_xml = $HTTP_RAW_POST_DATA;
xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);
?>
