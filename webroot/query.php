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
# Classifieds
#

# Avatar Classifieds Request

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

# Classifieds Update


# Classifieds Delete

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
# Picks
#

# Avatar Picks Request

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

# Request Picks for User

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
				"description" => $row["description"],
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

# Picks Update

xmlrpc_server_register_method($xmlrpc_server, "picks_update",
		"picks_update");

function picks_update($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$pickuuid		= $req['pick_id'];
	$creator		= $req['creator_id'];
	$toppick		= $req['top_pick'];
	$name			= $req['name'];
	$description	= $req['desc'];
	$parceluuid		= $req['parcel_uuid'];
	$snapshotuuid	= $req['snapshot_id']; 
	$user			= $req['user'];
	$original		= $req['original'];
	$simname		= $req['sim_name'];
	$posglobal		= $req['pos_global'];
	$sortorder		= $req['sort_order'];
	$enabled		= $req['enabled'];

	// Check if we already have this one in the database
	$check = mysql_query("select count(*) from userpicks WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'");

	while ($row = mysql_fetch_row($check))
	{
		$ready = $row[0];
	}
	
	if ($ready == 0)
	{
		// Doing some late checking
		// Should be done by the module but let's see what happens when
		// I do it here

		if($parceluuid == "")
		{
			$parceluuid = "00000000-0000-0000-0000-0000000000000";
		}

		if($description == "")
		{
			$description = "Test";
		}

		if($user == "")
		{
			$user = "Unknown";
		}

		if($original == "")
		{
			$original = "Unknown";
		}
		
		$insertquery = "insert into userpicks VALUES ".
			"('". mysql_escape_string($pickuuid) ."',".
			"'". mysql_escape_string($creator) ."',".
			"'". mysql_escape_string($toppick) ."',".
			"'". mysql_escape_string($parceluuid) ."',".
			"'". mysql_escape_string($name) ."',".
			"'". mysql_escape_string($description) ."',".
			"'". mysql_escape_string($snapshotuuid) ."',".
			"'". mysql_escape_string($user) ."',".
			"'". mysql_escape_string($original) ."',".
			"'". mysql_escape_string($simname) ."',".
			"'". mysql_escape_string($posglobal) ."',".
			"'". mysql_escape_string($sortorder) ."',".
			"'". mysql_escape_string($enabled) ."')";
		
		print $insertquery;

		// Create a new record for this avatar note		
		$result = mysql_query($insertquery);
	}
	else
	{
		// Doing some late checking
		// Should be done by the module but let's see what happens when
		// I do it here

		if($parceluuid == "")
		{
			$parceluuid = "00000000-0000-0000-0000-0000000000000";
		}

		if($description == "")
		{
			$description = "Test";
		}

		if($user == "")
		{
			$user = "Unknown";
		}

		if($original == "")
		{
			$original = "Unknown";
		}

		$updatequery = "update userpicks SET ".
			"parceluuid = '". mysql_escape_string($parceluuid) ."' AND ".
			"name = '". mysql_escape_string($name) ."' AND ".
			"description = '". mysql_escape_string($description) ."' AND ".
			"snapshotuuid = '". mysql_escape_string($snapshotuuid) ."' AND ".
			"user = '". mysql_escape_string($user) ."' AND ".
			"originalname = '". mysql_escape_string($original) ."' AND ".
			"simname = '". mysql_escape_string($simname) ."' AND ".
			"posglobal = '". mysql_escape_string($posglobal) ."' WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'";

		print $updatequery;

		// Update the existing record
		$result = mysql_query($updatequery);
	}

	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

# Picks Delete

xmlrpc_server_register_method($xmlrpc_server, "picks_delete",
		"picks_delete");

function picks_delete($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$pickuuid		= $req['pick_id'];

	$result = mysql_query("delete from userpicks where ".
			"pickuuid = '".mysql_escape_string($pickuuid) ."'");
	
	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

#
# Notes
#

# Avatar Notes Request


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

# Avatar Notes Update

xmlrpc_server_register_method($xmlrpc_server, "avatar_notes_update",
		"avatar_notes_update");

function avatar_notes_update($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['avatar_id'];
	$targetuuid		= $req['target_id'];
	$notes			= $req['notes'];

	// Check if we already have this one in the database

	$check = mysql_query("select count(*) from usernotes WHERE ".
			"useruuid = '". mysql_escape_string($uuid) ."' AND ".
			"targetuuid = '". mysql_escape_string($targetuuid) ."'");

	while ($row = mysql_fetch_row($check))
	{
		$ready = $row[0];
	}
	
	if ($ready == 0)
	{
		// Create a new record for this avatar note		
		$result = mysql_query("insert into usernotes VALUES ".
			"('". mysql_escape_string($uuid) ."',".
			"'". mysql_escape_string($targetuuid) ."',".
			"'". mysql_escape_string($notes) ."')");
	}
	else if ($notes == "")
	{
		// Delete the record for this avatar note		
		$result = mysql_query("delete from usernotes WHERE ".
			"useruuid = '". mysql_escape_string($uuid) ."' AND ".
			"targetuuid = '". mysql_escape_string($targetuuid) ."'");
	}
	else
	{
		// Update the existing record
		$result = mysql_query("update usernotes SET ".
			"notes = '". mysql_escape_string($notes) ."' WHERE ".
			"useruuid = '". mysql_escape_string($uuid) ."' AND ".
			"targetuuid = '". mysql_escape_string($targetuuid) ."'");
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
