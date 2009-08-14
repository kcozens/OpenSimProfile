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

		
	$result = mysql_query("select * from classifieds where ".
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

xmlrpc_server_register_method($xmlrpc_server, "classified_update",
		"classified_update");

function classified_update($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$classifieduuid = $req['classifiedUUID'];
	$creator		= $req['creatorUUID'];
	$category		= $req['category'];
	$name			= $req['name'];
	$description	= $req['description'];
	$parceluuid		= $req['parcelUUID'];
	$parentestate	= $req['parentestate'];
	$snapshotuuid	= $req['snapshotUUID'];
	$simname		= $req['sim_name'];
	$globalpos		= $req['globalpos'];
	$parcelname		= $req['parcelname'];
	$classifiedflag = $req['classifiedFlags'];
	$priceforlist	= $req['classifiedPrice'];
	
	// Check if we already have this one in the database
	$check = mysql_query("select count(*) from classifieds WHERE ".
			"classifieduuid = '". mysql_escape_string($classifieduuid) ."'");

	while ($row = mysql_fetch_row($check))
	{
		$ready = $row[0];
	}

	if ($ready == 0)
	{
		// Doing some late checking
		// Should be done by the module but let's see what happens when
		// I do it here

		if($parcelname == "")
		{
			$parcelname = "Unknown";
		}
		
		if($parceluuid == "")
		{
			$parceluuid = "00000000-0000-0000-0000-0000000000000";
		}

		if($description == "")
		{
			$description = "No Description";
		}

		if($classifiedflag == 2)
		{
			$creationdate = time();
			$expirationdate = time() + (7 * 24 * 60 * 60);
		}
		else
		{
			$creationdate = time();
			$expirationdate = time() + (365 * 24 * 60 * 60);
		}
	
		$insertquery = "insert into classifieds VALUES ".
			"('". mysql_escape_string($classifieduuid) ."',".
			"'". mysql_escape_string($creator) ."',".
			"". mysql_escape_string($creationdate) .",".
			"". mysql_escape_string($expirationdate) .",".
			"'". mysql_escape_string($category) ."',".
			"'". mysql_escape_string($name) ."',".
			"'". mysql_escape_string($description) ."',".
			"'". mysql_escape_string($parceluuid) ."',".
			"". mysql_escape_string($parentestate) .",".
			"'". mysql_escape_string($snapshotuuid) ."',".
			"'". mysql_escape_string($simname) ."',".
			"'". mysql_escape_string($globalpos) ."',".
			"'". mysql_escape_string($parcelname) ."',".
			"". mysql_escape_string($classifiedflag) .",".
			"". mysql_escape_string($priceforlist) .")";
		
		print $insertquery;

		// Create a new record for this avatar note		
		$result = mysql_query($insertquery);
	}
	else
	{

	}
	
	$response_xml = xmlrpc_encode(array(
		'success'	  => True,
		'errorMessage' => "",
		'data' => $data
	));

	print $response_xml;
}

# Classifieds Delete

xmlrpc_server_register_method($xmlrpc_server, "classified_delete",
		"classified_delete");

function classified_delete($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$classifieduuid		= $req['classifiedID'];

	$result = mysql_query("delete from classifieds where ".
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
		if ($row["description"] == "")
		{
			$row["description"] = "No description given";
		}
		
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
			$description = "No Description";
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
		
		//print $insertquery;

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
			$parceluuid = "00000000-0000-0000-0000-00000000000";
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

		$updatequery1 = "update userpicks SET ".
			"parceluuid = '". mysql_escape_string($parceluuid) ."' WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'";

		$updatequery2 = "update userpicks SET ".
			"name = '". mysql_escape_string($name) ."' WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'";

		$updatequery3 = "update userpicks SET ".
			"description = '". mysql_escape_string($description) ."' WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'";

		$updatequery4 = "update userpicks SET ".
			"snapshotuuid = '". mysql_escape_string($snapshotuuid) ."' WHERE ".
			"pickuuid = '". mysql_escape_string($pickuuid) ."'";
		
		//print $updatequery1."\r\n";
		//print $updatequery2."\r\n";
		//print $updatequery3."\r\n";
		//print $updatequery4."\r\n";

		// Update the existing record
		$resultQ1 = mysql_query($updatequery1);
		$resultQ2 = mysql_query($updatequery2);
		$resultQ3 = mysql_query($updatequery3);
		$resultQ4 = mysql_query($updatequery4);
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

# Profile bits

xmlrpc_server_register_method($xmlrpc_server, "profile_request",
		"profile_request");

function profile_request($method_name, $params, $app_data)
{
	$req 			= $params[0];

	$uuid 			= $req['avatar_id'];

	$result = mysql_query("select profileURL from userprofile where ".
			"useruuid = '". mysql_escape_string($uuid) ."'");

	while (($row = mysql_fetch_assoc($result)))
	{
		$data[] = array(
				"ProfileUrl" => $row["profileURL"]);
	}

	$response_xml = xmlrpc_encode(array(
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
