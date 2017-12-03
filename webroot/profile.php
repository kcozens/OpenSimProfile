<?php

include("databaseinfo.php");

// Attempt to connect to the database
try {
  $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME", $DB_USER, $DB_PASSWORD);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e)
{
  echo "Error connecting to database\n";
  file_put_contents('PDOErrors.txt', $e->getMessage() . "\n-----\n", FILE_APPEND);
  exit;
}


#
#  Copyright (c)Melanie Thielker (http://opensimulator.org/)
#

###################### No user serviceable parts below #####################

$zeroUUID = "00000000-0000-0000-0000-000000000000";

function get_error_message($result)
{
    global $db;

    if (!$result)
        return "";

    $errorInfo = $db->errorInfo();
    return $errorInfo[2];
}


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
    global $db;

    $req            = $params[0];

    $uuid           = $req['uuid'];

    $query = $db->prepare("SELECT * FROM classifieds WHERE creatoruuid = ?");
    $result = $query->execute( array($uuid) );

    $data = array();

    while ($row = $query->fetch(PDO::FETCH_ASSOC))
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
    global $db, $zeroUUID;

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

    //FIXME: Just do an update then insert if update failed?
    // Check if we already have this one in the database
    $query = $db->prepare("SELECT COUNT(*) FROM classifieds WHERE " .
                            "classifieduuid = ?");
    $result = $query->execute( array($classifieduuid) );

    $row = $query->fetch(PDO::FETCH_NUM);
    if ($row[0] > 0)
        $found = true;
    else
        $found = false;

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

    $sqldata = array("creator"  => $creator,
                     "e_date"   => $expirationdate,
                     "cat"      => $category,
                     "name"     => $name,
                     "desc"     => $description,
                     "p_uuid"   => $parceluuid,
                     "estate"   => $parentestate,
                     "snapshot" => $snapshotuuid,
                     "simname"  => $simname,
                     "pos"      => $globalpos,
                     "p_name"   => $parcelname,
                     "flags"    => $classifiedflag,
                     "price"    => $priceforlist,
                     "c_uuid"   => $classifieduuid);

    if (!$found)
    {
        $sqldata["c_date"] = $creationdate;

        $sql = "INSERT INTO classifieds VALUES (:c_uuid, :creator, :c_date, " .
                ":e_date, :cat, :name, :desc, :p_uuid, :estate, :snapshot, " .
                ":simname, :pos, :p_name, :flags, :price)";
    }
    else
    {
        $sql = "UPDATE classifieds SET " .
                "`creatoruuid`= :creator, " .
                "`expirationdate`= :e_date, " .
                "`category`= :cat, " .
                "`name`= :name, " .
                "`description`= :desc, " .
                "`parceluuid`= :p_uuid, " .
                "`parentestate`= :estate, " .
                "`snapshotuuid`= :snapshot, " .
                "`simname`= :simname, " .
                "`posglobal`= :pos, " .
                "`parcelname`= :p_name, " .
                "`classifiedflags`= :flags, " .
                "`priceforlisting`= :price" .
                " WHERE `classifieduuid`= :c_uuid";
    }

    // Create a new record for this classified
    $query = $db->prepare($sql);
    $result = $query->execute($sqldata);

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'created' => $found,
        'errorMessage' => $db->errorInfo()
    ));

    print $response_xml;
}

# Classifieds Delete

xmlrpc_server_register_method($xmlrpc_server, "classified_delete",
        "classified_delete");

function classified_delete($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $classifieduuid = $req['classifiedID'];

    $query = $db->prepare("DELETE FROM classifieds WHERE classifieduuid = ?");
    $query->execute( array($classifieduuid) );

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
    global $db;

    $req            = $params[0];

    $uuid           = $req['uuid'];

    $data = array();

    $query = $db->prepare("SELECT `pickuuid`,`name` FROM userpicks WHERE " .
                            "creatoruuid = ?");
    $result = $query->execute( array($uuid) );

    if ($result)
    {
        while ($row = $query->fetch(PDO::FETCH_ASSOC))
        {
            $data[] = array(
                    "pickid" => $row["pickuuid"],
                    "name" => $row["name"]);
        }
    }

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'data' => $data,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

# Request Picks for User

xmlrpc_server_register_method($xmlrpc_server, "pickinforequest",
        "pickinforequest");

function pickinforequest($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $pick           = $req['pick_id'];

    $data = array();

    $query = $db->prepare("SELECT * FROM userpicks WHERE " .
                            "creatoruuid = ? AND pickuuid = ?");
    $result = $query->execute( array($uuid, $pick) );

    if ($result)
    {
        $row = $query->fetch(PDO::FETCH_ASSOC);

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
        'success' => $result,
        'data' => $data,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

# Picks Update

xmlrpc_server_register_method($xmlrpc_server, "picks_update",
        "picks_update");

function picks_update($method_name, $params, $app_data)
{
    global $db, $zeroUUID;

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
    $query = $db->prepare("SELECT COUNT(*) FROM userpicks WHERE pickuuid = ?");
    $query->execute( array($pickuuid) );

    if ($query->fetchColumn() == 0)
    {
        if ($user == null || $user == "")
            $user = "Unknown";

        //The original parcel name is the same as the name of the
        //profile pick when a new profile pick is being created .
        $original = $name;

        $sql = "INSERT INTO userpicks VALUES (" .
                ":uuid, :creator, :top, :parcel, :name, :desc, :snapshot, " .
                ":user, :original, :simname, :pos, :order, :enabled)";

        $query = $db->prepare($sql);
        $result = $query->execute( array("uuid"     => $pickuuid,
                                         "creator"  => $creator,
                                         "top"      => $toppick,
                                         "parcel"   => $parceluuid,
                                         "name"     => $name,
                                         "desc"     => $description,
                                         "snapshot" => $snapshotuuid,
                                         "user"     => $user,
                                         "original" => $original,
                                         "simname"  => $simname,
                                         "pos"      => $posglobal,
                                         "order"    => $sortorder,
                                         "enabled"  => $enabled) );
    }
    else
    {
        $query = $db->prepare("UPDATE userpicks SET " .
                                "parceluuid = :parcel, " .
                                "name = :name,  " .
                                "description = :desc,  " .
                                "snapshotuuid = :snapshot " .
                                "WHERE pickuuid = :pick");
        $result = $query->execute( array("parcel"   => $parceluuid,
                                         "name"     => $name,
                                         "desc"     => $description,
                                         "snapshot" => $snapshotuuid,
                                         "pick"     => $pickuuid) );
    }

    if ($query->rowCount() == 1)
        $result = True;
    else
        $result = False;

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

# Picks Delete

xmlrpc_server_register_method($xmlrpc_server, "picks_delete",
        "picks_delete");

function picks_delete($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $pickuuid       = $req['pick_id'];

    $query = $db->prepare("DELETE FROM userpicks WHERE pickuuid = ?");
    $result = $query->execute( array($pickuuid) );

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => get_error_message($result)
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
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $targetuuid     = $req['uuid'];

    $query = $db->prepare("SELECT notes FROM usernotes WHERE " .
                            "useruuid = ? AND targetuuid = ?");
    $result = $query->execute( array($uuid, $targetuuid) );

    if ($result == False)
        $notes = "";
    else
    {
        $row = $query->fetch(PDO::FETCH_NUM);

        $notes = $row[0];
    }

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
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $targetuuid     = $req['target_id'];
    $notes          = $req['notes'];

    // Check if we already have this one in the database

    $query = $db->prepare("SELECT COUNT(*) FROM usernotes WHERE " .
                            "useruuid = ? AND targetuuid = ?");
    $query->execute( array($uuid, $targetuuid) );

    if ($query->fetchColumn() == 0)
    {
        // Create a new record for this avatar note
        $query = $db->prepare("INSERT INTO usernotes VALUES (?, ?, ?)");
        $result = $query->execute( array($uuid, $targetuuid, $notes) );
    }
    else if ($notes == "")
    {
        // Delete the record for this avatar note
        $query = $db->prepare("DELETE FROM usernotes WHERE " .
                                "useruuid = ? AND targetuuid = ?");
        $result = $query->execute( array($uuid, $targetuuid) );
    }
    else
    {
        // Update the existing record
        $query = $db->prepare("UPDATE usernotes SET notes = ? WHERE " .
                                "useruuid = ? AND targetuuid = ?");
        $result = $query->execute( array($notes, $uuid, $targetuuid) );
    }

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

# Profile bits

xmlrpc_server_register_method($xmlrpc_server, "avatar_properties_request",
        "avatar_properties_request");

function avatar_properties_request($method_name, $params, $app_data)
{
    global $db, $zeroUUID;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];

    $query = $db->prepare("SELECT * FROM userprofile WHERE useruuid = ?");
    $result = $query->execute( array($uuid) );

    if ($result)
    {
        $row = $query->fetch(PDO::FETCH_ASSOC);

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
        //FIXME: Should this only be done when asking for ones own profile?
        //Insert empty record for avatar .
        $query = $db->prepare("INSERT INTO userprofile VALUES ( " .
                    ":uuid, '$zeroUUID', 0, 0, '', 0, '', 0, '', '', " .
                    "'$zeroUUID', '', '$zeroUUID', '')");
        $result = $query->execute( array('uuid' => $uuid) );

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
        'success' => $result,
        'data' => $data,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "avatar_properties_update",
        "avatar_properties_update");

function avatar_properties_update($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $profileURL     = $req['ProfileUrl'];
    $image          = $req['Image'];
    $abouttext      = $req['AboutText'];
    $firstlifeimage = $req['FirstLifeImage'];
    $firstlifetext  = $req['FirstLifeAboutText'];

    $query = $db->prepare("UPDATE userprofile SET " .
                            "profileURL=:url, " .
                            "profileImage=:image, " .
                            "profileAboutText=:a_text, " .
                            "profileFirstImage=:f_image, " .
                            "profileFirstText=:f_text " .
                            "WHERE useruuid=:uuid");
    $result = $query->execute( array("url"     => $profileURL,
                                     "image"   => $image,
                                     "a_text"  => $abouttext,
                                     "f_image" => $firstlifeimage,
                                     "f_text"  => $firstlifetext,
                                     "uuid"    => $uuid) );

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}


// Profile Interests

xmlrpc_server_register_method($xmlrpc_server, "avatar_interests_update",
        "avatar_interests_update");

function avatar_interests_update($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $wanttext       = $req['wanttext'];
    $wantmask       = $req['wantmask'];
    $skillstext     = $req['skillstext'];
    $skillsmask     = $req['skillsmask'];
    $languages      = $req['languages'];

    $query = $db->prepare("UPDATE userprofile SET " .
                          "profileWantToMask = :wantmask, " .
                          "profileWantToText = :wanttext, " .
                          "profileSkillsMask = :skillmask, " .
                          "profileSkillsText = :skilltext, " .
                          "profileLanguages = :lang " .
                          "WHERE useruuid = :uuid");
    $result = $query->execute( array("wantmask"  => $wantmask,
                               "wanttext"  => $wanttext,
                               "skillmask" => $skillsmask,
                               "skilltext" => $skillstext,
                               "lang"      => $languages,
                               "uuid"      => $uuid) );

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

// User Preferences

xmlrpc_server_register_method($xmlrpc_server, "user_preferences_request",
        "user_preferences_request");

function user_preferences_request($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];

    $query = $db->prepare("SELECT imviaemail,visible,email " .
                            "FROM usersettings WHERE useruuid = ?");
    $result = $query->execute( array($uuid) );

    if ($result)
    {
        $row = $query->fetch(PDO::FETCH_ASSOC);

        $data[] = array(
                "imviaemail" => $row["imviaemail"],
                "visible" => $row["visible"],
                "email" => $row["email"]);
    }
    else
    {
        //Insert empty record for avatar .
        //NOTE: The 'false' values here are enums defined in database
        $query = $db->prepare("INSERT INTO usersettings VALUES (" .
                                ":uuid, 'false', 'false', '')");
        $result = $query->execute( array("uuid" => $uuid) );

        $data[] = array(
                "imviaemail" => False,
                "visible" => False,
                "email" => "");
    }

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'data' => $data,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

xmlrpc_server_register_method($xmlrpc_server, "user_preferences_update",
        "user_preferences_update");

function user_preferences_update($method_name, $params, $app_data)
{
    global $db;

    $req            = $params[0];

    $uuid           = $req['avatar_id'];
    $wantim         = $req['imViaEmail'];
    $directory      = $req['visible'];

    $query = $db->prepare("UPDATE usersettings SET " .
                            "imviaemail = ?, visible = ? " .
                            "WHERE useruuid = ?");
    $result = $query->execute( array($wantim, $directory, $uuid) );

    $response_xml = xmlrpc_encode(array(
        'success' => $result,
        'data' => $data,
        'errorMessage' => get_error_message($result)
    ));

    print $response_xml;
}

#
# Process the request
#

$request_xml = file_get_contents("php://input");
//file_put_contents('PDOErrors.txt', "$request_xml\n\n", FILE_APPEND);

xmlrpc_server_call_method($xmlrpc_server, $request_xml, '');
xmlrpc_server_destroy($xmlrpc_server);

$db = NULL;
?>
