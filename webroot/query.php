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

		
	$result = mysql_query("select * from userclassifieds where ".
			"userUUID = '". mysql_escape_string($uuid) ."'");

	$data = array();

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"classifiedid" => $row["ClassifiedID"],
				"name" => $row["Name"]);
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
			"userUUID = '". mysql_escape_string($uuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"pickID" => $row["PickID"],
				"name" => $row["Name"]);
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
			"userUUID = '". mysql_escape_string($uuid) ."' AND ".
			"TargetID = '". mysql_escape_string($targetuuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"targetID" => $row["TargetID"],
				"notes" => $row["Notes"]);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "classifiedclickthrough",
		"classifiedclickthrough");

function classifiedclickthrough($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['uuid'];
	$targetuuid		= $req['avatar_id'];

	$result = mysql_query("select * from usernotes where ".
			"userUUID = '". mysql_escape_string($uuid) ."' AND ".
			"TargetID = '". mysql_escape_string($targetuuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"targetID" => $row["TargetID"],
				"notes" => $row["Notes"]);
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
			"CreatorID = '". mysql_escape_string($uuid) ."' AND ".
			"PickID = '". mysql_escape_string($pick) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"pickuuid" => $row["PickID"],
				"creatoruuid" => $row["CreatorID"],
				"toppick" => $row["TopPick"],
				"parceluuid" => $row["ParcelID"],
				"name" => $row["Name"],
				"description" => $row["Desc"],
				"snapshotuuid" => $row["SnapshotID"],
				"user" => $row["User"],
				"originalname" => $row["OriginalName"],
				"posglobal" => $row["PosGlobal"],
				"sortorder"=> $row["SortOrder"],
				"enabled" => $row["Enabled"]);
	}

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
