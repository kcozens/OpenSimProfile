<?php
include("databaseinfo.php");

$link = mysqli_connect ($DB_HOST, $DB_USER, $DB_PASSWORD);
if (!$link)
    die('Connect error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());

mysqli_select_db ($link,$DB_NAME);
mysqli_set_charset($link, "utf8");

#
#  Copyright (c)Melanie Thielker (http://opensimulator.org/)
#  modified by Richardus Raymaker to support mysqli.
#

###################### No user serviceable parts below #####################

$zeroUUID = "00000000-0000-0000-0000-000000000000";

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
    global $link;

    $req            = $params[0];

    $uuid           = $req['uuid'];


    $result = mysqli_query($link,"SELECT * FROM classifieds WHERE ".
            "creatoruuid = '". mysqli_real_escape_string($link,$uuid) ."'");

    $data = array();

    while (($row = mysqli_fetch_assoc($result)))
    {
        $data[] = array(
                "classifiedid" => $row["classifieduuid"],
                "name" => $row["name"]);
    }

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

# Classifieds Update

xmlrpc_server_register_method($xmlrpc_server, "classified_update",
        "classified_update");

function classified_update($method_name, $params, $app_data)
{
    global $link, $zeroUUID;

    $req            = $params[0];

    $classifieduuid = $req['classifiedUUID'];
    $creator        = $req['creatorUUID'];
    $category       = $req['category'];
    $name           = $req['name'];
    $description    = $req['description'];
    $parceluuid     = $req['parcelUUID'];
    $parentestate   = $req['parentestate'];
    $snapshotuuid   = $req['snapshotUUID'];
    $simname        = $req['sim_name'];
    $parcelname     = $req['parcelname'];
    $globalpos      = $req['globalpos'];
    $classifiedflag = $req['classifiedFlags'];
    $priceforlist   = $req['classifiedPrice'];

    // Check if we already have this one in the database
    $check = mysqli_query($link,"SELECT COUNT(*) FROM classifieds WHERE ".
            "classifieduuid = '". mysqli_real_escape_string($link,$classifieduuid) ."'");

    while ($row = mysqli_fetch_row($check))
    {
        $found = $row[0];
    }

    // Doing some late checking
    // Should be done by the module but let's see what happens when
    // I do it here

    if ($parcelname == "")
        $parcelname = "Unknown";

    if ($parceluuid == "")
        $parceluuid = $zeroUUID;

    if ($description == "")
        $description = "No Description";

    //If PG, Mature, and Adult flags are all 0 assume PG and set bit 2.
    //This works around what might be a viewer bug regarding the flags.
    //The ossearch query.php file expects bit 2 set for any PG listing.
    if (($classifiedflag & 76) == 0)
        $classifiedflag |= 4;

    //Renew Weekly flag is 32 (1 << 5)
    if (($classifiedflag & 32) == 0)
    {
        $creationdate = time();
        $expirationdate = time() + (7 * 24 * 60 * 60);
    }
    else
    {
        $creationdate = time();
        $expirationdate = time() + (52 * 7 * 24 * 60 * 60);
    }

    if ($found == 0)
    {
        $sql = "INSERT INTO classifieds VALUES ".
            "('". mysqli_real_escape_string($link,$classifieduuid) ."',".
            "'". mysqli_real_escape_string($link,$creator) ."',".
            "". mysqli_real_escape_string($link,$creationdate) .",".
            "". mysqli_real_escape_string($link,$expirationdate) .",".
            "'". mysqli_real_escape_string($link,$category) ."',".
            "'". mysqli_real_escape_string($link,$name) ."',".
            "'". mysqli_real_escape_string($link,$description) ."',".
            "'". mysqli_real_escape_string($link,$parceluuid) ."',".
            "". mysqli_real_escape_string($link,$parentestate) .",".
            "'". mysqli_real_escape_string($link,$snapshotuuid) ."',".
            "'". mysqli_real_escape_string($link,$simname) ."',".
            "'". mysqli_real_escape_string($link,$globalpos) ."',".
            "'". mysqli_real_escape_string($link,$parcelname) ."',".
            "". mysqli_real_escape_string($link,$classifiedflag) .",".
            "". mysqli_real_escape_string($link,$priceforlist) .")";
    }
    else
    {
        $sql = "UPDATE classifieds SET ".
            "`creatoruuid`='". mysqli_real_escape_string($link,$creator)."',".
            "`expirationdate`=". mysqli_real_escape_string($link,$expirationdate).",".
            "`category`='". mysqli_real_escape_string($link,$category)."',".
            "`name`='". mysqli_real_escape_string($link,$name)."',".
            "`description`='". mysqli_real_escape_string($link,$description)."',".
            "`parceluuid`='". mysqli_real_escape_string($link,$parceluuid)."',".
            "`parentestate`=". mysqli_real_escape_string($link,$parentestate).",".
            "`snapshotuuid`='". mysqli_real_escape_string($link,$snapshotuuid)."',".
            "`simname`='". mysqli_real_escape_string($link,$simname)."',".
            "`posglobal`='". mysqli_real_escape_string($link,$globalpos)."',".
            "`parcelname`='". mysqli_real_escape_string($link,$parcelname)."',".
            "`classifiedflags`=". mysqli_real_escape_string($link,$classifiedflag).",".
            "`priceforlisting`=". mysqli_real_escape_string($link,$priceforlist).
            " WHERE ".
            "`classifieduuid`='". mysqli_real_escape_string($link,$classifieduuid)."'";
    }

    // Create a new record for this classified
    $result = mysqli_query($link,$sql);

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'created' => $found == 0,
        'errorMessage' => mysqli_error($link)
    ));

    print $response_xml;
}

# Classifieds Delete

xmlrpc_server_register_method($xmlrpc_server, "classified_delete",
        "classified_delete");

function classified_delete($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $classifieduuid = $req['classifiedID'];

    $result = mysqli_query($link,"DELETE FROM classifieds WHERE ".
            "classifieduuid = '".mysqli_real_escape_string($link,$classifieduuid) ."'");

    $response_xml = xmlrpc_encode(array(
        'success' => True,
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
    global $link;

    $req            = $params[0];

    $uuid           = $req['uuid'];

    $data = array();

    $result = mysqli_query($link,"SELECT `pickuuid`,`name` FROM userpicks WHERE ".
            "creatoruuid = '". mysqli_real_escape_string($link,$uuid) ."'");

    while (($row = mysqli_fetch_assoc($result)))
    {
        $data[] = array(
                "pickid" => $row["pickuuid"],
                "name" => $row["name"]);
    }

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

# Request Picks for User

xmlrpc_server_register_method($xmlrpc_server, "pickinforequest",
        "pickinforequest");

function pickinforequest($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $pick           = $req['pick_id'];

    $data = array();

    $result = mysqli_query($link,"SELECT * FROM userpicks WHERE ".
            "creatoruuid = '". mysqli_real_escape_string($link,$uuid) ."' AND ".
            "pickuuid = '". mysqli_real_escape_string($link,$pick) ."'");

    $row = mysqli_fetch_assoc($result);
    if ($row != False)
    {
        if ($row["description"] == null || $row["description"] == "")
            $row["description"] = "No description given";

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
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

# Picks Update

xmlrpc_server_register_method($xmlrpc_server, "picks_update",
        "picks_update");

function picks_update($method_name, $params, $app_data)
{
    global $link, $zeroUUID;

    $req            = $params[0];

    $pickuuid       = $req['pick_id'];
    $creator        = $req['creator_id'];
    $toppick        = $req['top_pick'];
    $name           = $req['name'];
    $description    = $req['desc'];
    $parceluuid     = $req['parcel_uuid'];
    $snapshotuuid   = $req['snapshot_id'];
    $user           = $req['user'];
    $simname        = $req['sim_name'];
    $posglobal      = $req['pos_global'];
    $sortorder      = $req['sort_order'];
    $enabled        = $req['enabled'];

    if ($parceluuid == "")
        $parceluuid = $zeroUUID;

    if ($description == "")
        $description = "No Description";

    // Check if we already have this one in the database
    $check = mysqli_query($link,"SELECT COUNT(*) FROM userpicks WHERE ".
            "pickuuid = '". mysqli_real_escape_string($link,$pickuuid) ."'");

    $row = mysqli_fetch_row($check);

    if ($row[0] == 0)
    {
        if ($user == null || $user == "")
            $user = "Unknown";

        //The original parcel name is the same as the name of the
        //profile pick when a new profile pick is being created.
        $original = $name;

        $query = "INSERT INTO userpicks VALUES ".
            "('". mysqli_real_escape_string($link,$pickuuid) ."',".
            "'". mysqli_real_escape_string($link,$creator) ."',".
            "'". mysqli_real_escape_string($link,$toppick) ."',".
            "'". mysqli_real_escape_string($link,$parceluuid) ."',".
            "'". mysqli_real_escape_string($link,$name) ."',".
            "'". mysqli_real_escape_string($link,$description) ."',".
            "'". mysqli_real_escape_string($link,$snapshotuuid) ."',".
            "'". mysqli_real_escape_string($link,$user) ."',".
            "'". mysqli_real_escape_string($link,$original) ."',".
            "'". mysqli_real_escape_string($link,$simname) ."',".
            "'". mysqli_real_escape_string($link,$posglobal) ."',".
            "'". mysqli_real_escape_string($link,$sortorder) ."',".
            "'". mysqli_real_escape_string($link,$enabled) ."')";
    }
    else
    {
        $query = "UPDATE userpicks SET " .
            "parceluuid = '". mysqli_real_escape_string($link,$parceluuid) . "', " .
            "name = '". mysqli_real_escape_string($link,$name) . "', " .
            "description = '". mysqli_real_escape_string($link,$description) . "', " .
            "snapshotuuid = '". mysqli_real_escape_string($link,$snapshotuuid) . "' WHERE ".
            "pickuuid = '". mysqli_real_escape_string($link,$pickuuid) ."'";
    }

    $result = mysqli_query($link,$query);
    if ($result != False)
        $result = True;

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => mysqli_error($link)
    ));

    print $response_xml;
}

# Picks Delete

xmlrpc_server_register_method($xmlrpc_server, "picks_delete",
        "picks_delete");

function picks_delete($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $pickuuid       = $req['pick_id'];

    $result = mysqli_query($link,"DELETE FROM userpicks WHERE ".
            "pickuuid = '".mysqli_real_escape_string($link,$pickuuid) ."'");

    if ($result != False)
        $result = True;

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => mysqli_error($link)
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
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $targetuuid     = $req['uuid'];

    $result = mysqli_query($link,"SELECT notes FROM usernotes WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."' AND ".
            "targetuuid = '". mysqli_real_escape_string($link,$targetuuid) ."'");

    $row = mysqli_fetch_row($result);
    if ($row == False)
        $notes = "";
    else
        $notes = $row[0];

    $data[] = array(
            "targetid" => $targetuuid,
            "notes" => $notes);

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

# Avatar Notes Update

xmlrpc_server_register_method($xmlrpc_server, "avatar_notes_update",
        "avatar_notes_update");

function avatar_notes_update($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $targetuuid     = $req['target_id'];
    $notes          = $req['notes'];

    // Check if we already have this one in the database

    $check = mysqli_query($link,"SELECT COUNT(*) FROM usernotes WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."' AND ".
            "targetuuid = '". mysqli_real_escape_string($link,$targetuuid) ."'");

    $row = mysqli_fetch_row($check);

    if ($row[0] == 0)
    {
        // Create a new record for this avatar note
        $result = mysqli_query($link,"INSERT INTO usernotes VALUES ".
            "('". mysqli_real_escape_string($link,$uuid) ."',".
            "'". mysqli_real_escape_string($link,$targetuuid) ."',".
            "'". mysqli_real_escape_string($link,$notes) ."')");
    }
    else if ($notes == "")
    {
        // Delete the record for this avatar note
        $result = mysqli_query($link,"DELETE FROM usernotes WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."' AND ".
            "targetuuid = '". mysqli_real_escape_string($link,$targetuuid) ."'");
    }
    else
    {
        // Update the existing record
        $result = mysqli_query($link,"UPDATE usernotes SET ".
            "notes = '". mysqli_real_escape_string($link,$notes) ."' WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."' AND ".
            "targetuuid = '". mysqli_real_escape_string($link,$targetuuid) ."'");
    }

    $response_xml = xmlrpc_encode(array(
        'success' => True
    ));

    print $response_xml;
}

# Profile bits

xmlrpc_server_register_method($xmlrpc_server, "avatar_properties_request",
        "avatar_properties_request");

function avatar_properties_request($method_name, $params, $app_data)
{
    global $link, $zeroUUID;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];

    $result = mysqli_query($link,"SELECT * FROM userprofile WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."'");
    $row = mysqli_fetch_assoc($result);

    if ($row != False)
    {
        $data[] = array(
                "ProfileUrl" => $row["profileURL"],
                "Image" => $row["profileImage"],
                "AboutText" => $row["profileAboutText"],
                "FirstLifeImage" => $row["profileFirstImage"],
                "FirstLifeAboutText" => $row["profileFirstText"],
                "Partner" => $row["profilePartner"],

                //Return interest data along with avatar properties
                "wantmask"   => $row["profileWantToMask"],
                "wanttext"   => $row["profileWantToText"],
                "skillsmask" => $row["profileSkillsMask"],
                "skillstext" => $row["profileSkillsText"],
                "languages"  => $row["profileLanguages"]);
    }
    else
    {
        //Insert empty record for avatar.
        //FIXME: Should this only be done when asking for ones own profile?
        $sql = "INSERT INTO userprofile VALUES ( ".
                "'". mysqli_real_escape_string($link,$uuid) ."', ".
                "'$zeroUUID', 0, 0, '', 0, '', 0, '', '', ".
                "'$zeroUUID', '', '$zeroUUID', '')";
        $result = mysqli_query($link,$sql);

        $data[] = array(
                "ProfileUrl" => "",
                "Image" => $zeroUUID,
                "AboutText" => "",
                "FirstLifeImage" => $zeroUUID,
                "FirstLifeAboutText" => "",
                "Partner" => $zeroUUID,

                "wantmask"   => 0,
                "wanttext"   => "",
                "skillsmask" => 0,
                "skillstext" => "",
                "languages"  => "");
    }

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "avatar_properties_update",
        "avatar_properties_update");

function avatar_properties_update($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $profileURL     = $req['ProfileUrl'];
    $image          = $req['Image'];
    $abouttext      = $req['AboutText'];
    $firstlifeimage = $req['FirstLifeImage'];
    $firstlifetext  = $req['FirstLifeAboutText'];

    $result=mysqli_query($link,"UPDATE userprofile SET ".
            "profileURL='". mysqli_real_escape_string($link,$profileURL) ."', ".
            "profileImage='". mysqli_real_escape_string($link,$image) ."', ".
            "profileAboutText='". mysqli_real_escape_string($link,$abouttext) ."', ".
            "profileFirstImage='". mysqli_real_escape_string($link,$firstlifeimage) ."', ".
            "profileFirstText='". mysqli_real_escape_string($link,$firstlifetext) ."' ".
            "WHERE useruuid='". mysqli_real_escape_string($link,$uuid) ."'"
        );

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => mysqli_error($link)
    ));

    print $response_xml;
}


// Profile Interests

xmlrpc_server_register_method($xmlrpc_server, "avatar_interests_update",
        "avatar_interests_update");

function avatar_interests_update($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $wanttext       = $req['wanttext'];
    $wantmask       = $req['wantmask'];
    $skillstext     = $req['skillstext'];
    $skillsmask     = $req['skillsmask'];
    $languages      = $req['languages'];

    $result = mysqli_query($link,"UPDATE userprofile SET ".
            "profileWantToMask = ". mysqli_real_escape_string($link,$wantmask) .",".
            "profileWantToText = '". mysqli_real_escape_string($link,$wanttext) ."',".
            "profileSkillsMask = ". mysqli_real_escape_string($link,$skillsmask) .",".
            "profileSkillsText = '". mysqli_real_escape_string($link,$skillstext) ."',".
            "profileLanguages = '". mysqli_real_escape_string($link,$languages) ."' ".
            "WHERE useruuid = '". mysqli_real_escape_string($link,$uuid) ."'"
        );

    $response_xml = xmlrpc_encode(array(
        'success' => True
    ));

    print $response_xml;
}

// User Preferences

xmlrpc_server_register_method($xmlrpc_server, "user_preferences_request",
        "user_preferences_request");

function user_preferences_request($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];

    $result = mysqli_query($link,"SELECT imviaemail,visible,email FROM usersettings WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."'");

    $row = mysqli_fetch_assoc($result);

    if ($row != False)
    {
        $data[] = array(
                "imviaemail" => $row["imviaemail"],
                "visible" => $row["visible"],
                "email" => $row["email"]);
    }
    else
    {
        //Insert empty record for avatar.
        //NOTE: The 'false' values here are enums defined in database
        $sql = "INSERT INTO usersettings VALUES ".
                "('". mysqli_real_escape_string($link,$uuid) ."', ".
                "'false', 'false', '')";
        $result = mysqli_query($link,$sql);

        $data[] = array(
                "imviaemail" => False,
                "visible" => False,
                "email" => "");
    }

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "user_preferences_update",
        "user_preferences_update");

function user_preferences_update($method_name, $params, $app_data)
{
    global $link;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $wantim         = $req['imViaEmail'];
    $directory      = $req['visible'];

    $result = mysqli_query($link,"UPDATE usersettings SET ".
            "imviaemail = '".mysqli_real_escape_string($link,$wantim) ."', ".
            "visible = '".mysqli_real_escape_string($link,$directory) ."' WHERE ".
            "useruuid = '". mysqli_real_escape_string($link,$uuid) ."'");

    $response_xml = xmlrpc_encode(array(
        'success' => True,
        'data' => $data
    ));

    print $response_xml;
}


#
# Process the request
#

$request_xml = file_get_contents("php://input");

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);

mysqli_close($link);
?>
