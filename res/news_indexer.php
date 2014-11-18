<?php

ini_set('memory_limit', '-1');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_time_limit(0);

require_once(__DIR__ . '/vendor/solr/Service.php');

// 
// 
// Try to connect to the named server, port, and url
// 
$solr = new Apache_Solr_Service( 'www2.lth.se', '8080', '/solr/personal' );

if ( ! $solr->ping() ) {
    echo 'Solr service not responding.';
    exit;
}
    
    //$dbhost = "dbmysql.kansli.lth.se";
$dbhost = "localhost";
//$db = "t3_dev";
//$db = "t3";
//$db="test";
//$db="typo_45";
$db="typo3_demo";
//die(getLastModDate($pid, $db, $dbhost));
$lastmoddate = getLastModDate($db, $dbhost);

$feGroupArray = array();
$feUserArray = array();
$titleCategoriesArray = array();

getFeGroups($lastmoddate,$db, $dbhost);

getTitleCategories($db, $dbhost);
    
getFeUsers($lastmoddate,$db, $dbhost);

/*echo '<pre>';
print_r($titleCategoriesArray);
echo '<pre>';

echo '<pre>';
print_r($feUserArray);
echo '<pre>';*/

$docs = array();

foreach($feUserArray as $key => $value) {
    $doc = array();
    //echo $key;
    $doc['id'] = $key;
      
    if(is_array($value['group_lucat'])) {
        foreach ($value['group_lucat'] as $key1 => $value1) {
            $doc['group_lucat_id'][] = $value1['id'];
            $doc['group_lucat_title'][] = utf8_encode($value1['title']);
        }
    } else {
        $doc['group_lucat_id'] = $value1['id'];
        $doc['group_lucat_title'] = utf8_encode($value1['title']);
    }
    $doc['uid'] = $value['uid'];
    $doc['pid'] = $value['pid'];
    $doc['name'] = utf8_encode($value['name']);
    $doc['email'] = utf8_encode($value['email']);
    $doc['telephone'] = utf8_encode($value['telephone']);
    $doc['first_name'] = utf8_encode($value['first_name']);
    $doc['last_name'] = utf8_encode($value['last_name']);        
    //echo $value['title'];
    
    foreach($titleCategoriesArray as $key2 => $value2) {
        if(strtolower($value2['T_title_sv']) === strtolower($value['title']) or strtolower($value2['T_title_en']) === strtolower($value['title'])) {
            //echo '<br /> ' . $value2['T_title_sv'].';'.$value2['T_title_en'] . ';'.$value['title'];
                $doc['staff_standard_category_sv'] = utf8_encode($value2['C_name_sv']);
                $doc['staff_standard_category_en'] = utf8_encode($value2['C_name_en']);
            /*if($value2['C1_name_sv'] or $value2['C1_name_en']) {
                $doc['staff_standard_category_sv'][] = utf8_encode($value2['C1_name_sv']);
                $doc['staff_standard_category_en'][] = utf8_encode($value2['C1_name_en']);
            }*/
        }
    }
    $doc['title'] = utf8_encode($value['title']);
    $doc['www'] = utf8_encode($value['www']);
    $doc['ou'] = utf8_encode($value['ou']);
    $doc['image'] = utf8_encode($value['image']);
    $doc['roomnumber'] = utf8_encode($value['roomnumber']);
    $doc['registeredaddress'] = utf8_encode($value['registeredaddress']);
    $doc['address'] = utf8_encode($value['address']);
    $doc['zip'] = utf8_encode($value['zip']);
    $doc['street'] = utf8_encode($value['street']);
    $doc['tstamp'] = $value['tstamp'];
    $doc['crdate'] = $value['crdate'];

    $docs[] = $doc;
}

/*print '<pre>';
print_r($docs);
print '</pre>';
die();*/
$documents = array();
  
foreach ( $docs as $item => $fields ) {

    $part = new Apache_Solr_Document();

    foreach ( $fields as $key => $value ) {
        if ( is_array( $value ) ) {
            foreach ( $value as $data ) {
                $part->setMultiValue( $key, $data );
            }
        }
        else {
            $part->$key = $value;
        }
    }

    $documents[] = $part;
}

try {
    $solr->addDocuments( $documents );
    $solr->commit();
    $solr->optimize();
    echo 'getFeUsers done!';
}
catch ( Exception $e ) {
    echo $e->getMessage();
}

/*
*/
                                
function getFeGroups($lastmoddate, $db, $dbhost)
{
    global $feGroupArray;
    //Database
    $conn = mysql_connect($dbhost, "fe_user_update", "ibi124Co") or die("45; ".mysql_error());
    $databas = mysql_select_db($db);
	
    $sql = "SELECT G1.uid AS G1_uid, G1.title AS G1_title, G1.tx_institutioner_lucatid AS G1_tx_institutioner_lucatid, 
        G2.uid AS G2_uid, G2.title AS G2_title, G2.tx_institutioner_lucatid AS G2_tx_institutioner_lucatid, 
        G3.uid AS G3_uid, G3.title AS G3_title, G3.tx_institutioner_lucatid AS G3_tx_institutioner_lucatid, 
        G4.uid AS G4_uid, G4.title AS G4_title, G4.tx_institutioner_lucatid AS G4_tx_institutioner_lucatid, 
        G5.uid AS G5_uid, G5.title AS G5_title, G5.tx_institutioner_lucatid AS G5_tx_institutioner_lucatid, 
        G6.uid AS G6_uid, G6.title AS G6_title, G6.tx_institutioner_lucatid AS G6_tx_institutioner_lucatid, 
        G7.uid AS G7_uid, G7.title AS G7_title, G7.tx_institutioner_lucatid AS G7_tx_institutioner_lucatid
        FROM fe_groups G1 JOIN fe_groups G2 ON G2.subgroup = G1.uid 
        LEFT JOIN fe_groups G3 ON G3.subgroup = G2.uid 
        LEFT JOIN fe_groups G4 ON G4.subgroup = G3.uid 
        LEFT JOIN fe_groups G5 ON G5.subgroup = G4.uid 
        LEFT JOIN fe_groups G6 ON G6.subgroup = G5.uid 
        LEFT JOIN fe_groups G7 ON G7.subgroup = G6.uid 
        WHERE G1.deleted=0";
    $result = mysql_query($sql) or die("51: ".mysql_error());
    while($row = mysql_fetch_array($result)) {
        $G7_tx_institutioner_lucatid = $row['G7_tx_institutioner_lucatid'];
        $G6_tx_institutioner_lucatid = $row['G6_tx_institutioner_lucatid'];
        $G5_tx_institutioner_lucatid = $row['G5_tx_institutioner_lucatid'];
        $G4_tx_institutioner_lucatid = $row['G4_tx_institutioner_lucatid'];
        $G3_tx_institutioner_lucatid = $row['G3_tx_institutioner_lucatid'];
        $G2_tx_institutioner_lucatid = $row['G2_tx_institutioner_lucatid'];
        $G1_tx_institutioner_lucatid = $row['G1_tx_institutioner_lucatid'];
        
        $G7_title = $row['G7_title'];
        $G6_title = $row['G6_title'];
        $G5_title = $row['G5_title'];
        $G4_title = $row['G4_title'];
        $G3_title = $row['G3_title'];
        $G2_title = $row['G2_title'];
        $G1_title = $row['G1_title'];
        
        $G7_uid = $row['G7_uid'];
        $G6_uid = $row['G6_uid'];
        $G5_uid = $row['G5_uid'];
        $G4_uid = $row['G4_uid'];
        $G3_uid = $row['G3_uid'];
        $G2_uid = $row['G2_uid'];
        $G1_uid = $row['G1_uid'];
        
        if($G7_tx_institutioner_lucatid) {
            $feGroupArray[$G7_tx_institutioner_lucatid]['uid'] = $G7_uid;
            $feGroupArray[$G7_tx_institutioner_lucatid]['title'] = $G7_title;
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G6_tx_institutioner_lucatid, "title" => $G6_title, "uid" => $G6_uid);
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G5_tx_institutioner_lucatid, "title" => $G5_title, "uid" => $G5_uid);
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G4_tx_institutioner_lucatid, "title" => $G4_title, "uid" => $G4_uid);
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G3_tx_institutioner_lucatid, "title" => $G3_title, "uid" => $G3_uid);
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G2_tx_institutioner_lucatid, "title" => $G2_title, "uid" => $G2_uid);
            $feGroupArray[$G7_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);
        } else if($G6_tx_institutioner_lucatid) {
            $feGroupArray[$G6_tx_institutioner_lucatid]['uid'] = $G6_uid;
            $feGroupArray[$G6_tx_institutioner_lucatid]['title'] = $G6_title;
            $feGroupArray[$G6_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G5_tx_institutioner_lucatid, "title" => $G5_title, "uid" => $G5_uid);
            $feGroupArray[$G6_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G4_tx_institutioner_lucatid, "title" => $G4_title, "uid" => $G4_uid);
            $feGroupArray[$G6_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G3_tx_institutioner_lucatid, "title" => $G3_title, "uid" => $G3_uid);
            $feGroupArray[$G6_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G2_tx_institutioner_lucatid, "title" => $G2_title, "uid" => $G2_uid);
            $feGroupArray[$G6_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);
        } else if($G5_tx_institutioner_lucatid) {
            $feGroupArray[$G5_tx_institutioner_lucatid]['uid'] = $G5_uid;
            $feGroupArray[$G5_tx_institutioner_lucatid]['title'] = $G5_title;
            $feGroupArray[$G5_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G4_tx_institutioner_lucatid, "title" => $G4_title, "uid" => $G4_uid);
            $feGroupArray[$G5_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G3_tx_institutioner_lucatid, "title" => $G3_title, "uid" => $G3_uid);
            $feGroupArray[$G5_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G2_tx_institutioner_lucatid, "title" => $G2_title, "uid" => $G2_uid);
            $feGroupArray[$G5_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);
        } else if($G4_tx_institutioner_lucatid) {
            $feGroupArray[$G4_tx_institutioner_lucatid]['uid'] = $G4_uid;
            $feGroupArray[$G4_tx_institutioner_lucatid]['title'] = $G4_title;
            $feGroupArray[$G4_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G3_tx_institutioner_lucatid, "title" => $G3_title, "uid" => $G3_uid);
            $feGroupArray[$G4_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G2_tx_institutioner_lucatid, "title" => $G2_title, "uid" => $G2_uid);
            $feGroupArray[$G4_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);           
        } else if($G3_tx_institutioner_lucatid) {
            $feGroupArray[$G3_tx_institutioner_lucatid]['uid'] = $G3_uid;
            $feGroupArray[$G3_tx_institutioner_lucatid]['title'] = $G3_title;
            $feGroupArray[$G3_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G2_tx_institutioner_lucatid, "title" => $G2_title, "uid" => $G2_uid);
            $feGroupArray[$G3_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);
        } else if($G2_tx_institutioner_lucatid) {
            $feGroupArray[$G2_tx_institutioner_lucatid]['uid'] = $G2_uid;
            $feGroupArray[$G2_tx_institutioner_lucatid]['title'] = $G2_title;
            $feGroupArray[$G2_tx_institutioner_lucatid]['subgroups'][] = array("tx_institutioner_lucatid" => $G1_tx_institutioner_lucatid, "title" => $G1_title, "uid" => $G1_uid);
        } else if($G1_tx_institutioner_lucatid) {
            $feGroupArray[$G1_tx_institutioner_lucatid]['uid'] = $G1_uid;
            $feGroupArray[$G1_tx_institutioner_lucatid]['title'] = $G1_title;
        }
    }
    mysql_close($conn);
    echo 'getFeGroups done!';
}

function getTitleCategories($db, $dbhost)
{
    global $titleCategoriesArray;
    
    $conn = mysql_connect($dbhost, "fe_user_update", "ibi124Co") or die("45; ".mysql_error());
    $databas = mysql_select_db($db);

    $sql = "SELECT C.name_sv AS C_name_sv, C.name_en AS C_name_en, C1.name_sv AS C1_name_sv, C1.name_en AS C1_name_en, T.title_sv AS T_title_sv, T.title_en AS T_title_en 
            FROM titles T JOIN categories C ON C.id = T.category LEFT JOIN Categories C1 ON C1.id = C.parentId;";
    $result = mysql_query($sql) or die("232: ".mysql_error());
    $i=0;
    while($row = mysql_fetch_array($result)) {
        $C_name_sv = $row['C_name_sv'];
        $C_name_en = $row['C_name_en'];
        $C1_name_sv = $row['C1_name_sv'];
        $C1_name_en = $row['C1_name_en'];
        $T_title_sv = $row['T_title_sv'];
        $T_title_en = $row['T_title_en'];
        
        $titleCategoriesArray[$i]['C_name_sv'] = $C_name_sv;
        $titleCategoriesArray[$i]['C_name_en'] = $C_name_en;
        $titleCategoriesArray[$i]['C1_name_sv'] = $C1_name_sv;
        $titleCategoriesArray[$i]['C1_name_en'] = $C1_name_en;
        $titleCategoriesArray[$i]['T_title_sv'] = $T_title_sv;
        $titleCategoriesArray[$i]['T_title_en'] = $T_title_en;
        $i++;
    }

    mysql_close($conn);
    echo 'getTitleCategories done!';
}

function getFeUsers($lastmoddate, $db, $dbhost)
{
    global $feUserArray;
    
    global $feGroupArray;
    
    if($lastmoddate) $lastmoddate = "(modifytimestamp>=$lastmoddate)";

            //Database
    $conn = mysql_connect($dbhost, "fe_user_update", "ibi124Co") or die("45; ".mysql_error());
    $databas = mysql_select_db($db);

            //Läser in fe users
    $sql = "SELECT GROUP_CONCAT('\"',FG.tx_institutioner_lucatid,'\"') AS group_lucat, FU.uid, FU.pid, FU.username, FU.password, FU.name, FU.email, FU.telephone, 
            FU.first_name, FU.last_name, FU.title, FU.www, FU.ou, FU.image,
            FU.roomnumber, FU.registeredaddress, FU.address, FU.zip, FU.street, FROM_UNIXTIME(FU.tstamp) AS tstamp, FROM_UNIXTIME(FU.crdate) AS crdate 
            FROM fe_users FU JOIN fe_groups FG ON FIND_IN_SET(FG.uid, FU.usergroup)
            WHERE FU.tx_institutioner_lth_search = 1 AND FU.deleted = 0
            GROUP BY FU.uid
            ORDER BY name
            ";
    $result = mysql_query($sql) or die("276: ".mysql_error());
    while($row = mysql_fetch_array($result)) {
        $username = $row['username'];
        $group_lucat = $row['group_lucat'];
        $group_lucatArray = explode(',', $group_lucat);
        foreach($group_lucatArray as $key => $value) {
            foreach($feGroupArray as $key1 => $value1) {
                if(str_replace('"','',$value) == $key1) {
                    $feUserArray[$username]['group_lucat'][0]['id'] = $key1;
                    $feUserArray[$username]['group_lucat'][0]['title'] = $value1['title'];
                    $i=1;
                    foreach($value1['subgroups'] as $key2 => $value2) {
                        $feUserArray[$username]['group_lucat'][$i]['id'] = $value2['tx_institutioner_lucatid'];
                        $feUserArray[$username]['group_lucat'][$i]['title'] = $value2['title'];
                        $i++;
                    }
                }
            }
            //$feUserArray['group_lucat'][] = $value;
        }
        $feUserArray[$username]['uid'] = $row['uid'];
        $feUserArray[$username]['pid'] = $row['pid'];
        $feUserArray[$username]['name'] = $row['name'];
        $feUserArray[$username]['email'] = $row['email'];
        $feUserArray[$username]['telephone'] = $row['telephone'];
        $feUserArray[$username]['first_name'] = $row['first_name'];
        $feUserArray[$username]['last_name'] = $row['last_name'];
        $feUserArray[$username]['title'] = $row['title'];
        $feUserArray[$username]['www'] = $row['www'];
        $feUserArray[$username]['ou'] = $row['ou'];
        $feUserArray[$username]['image'] = $row['image'];
        $feUserArray[$username]['roomnumber'] = $row['roomnumber'];
        $feUserArray[$username]['registeredaddress'] = $row['registeredaddress'];
        $feUserArray[$username]['address'] = $row['address'];
        $feUserArray[$username]['zip'] = $row['zip'];
        $feUserArray[$username]['street'] = $row['street'];
        $feUserArray[$username]['tstamp'] = $row['tstamp'];
        $feUserArray[$username]['crdate'] = $row['crdate'];
    }

    mysql_close($conn);
    echo 'getFeUsers done!';
}

function getLastModDate($db, $dbhost)
{
    //Database
    $conn = mysql_connect($dbhost, "fe_user_update", "ibi124Co") or die("35; ".mysql_error());
    $databas = mysql_select_db($db);
    $sql = "select MAX(tstamp) as maxdate from fe_users where tx_institutioner_lth_search=1";
    $result = mysql_query($sql) or die("325: ".mysql_error());
    $row = mysql_fetch_array($result);
    $maxdate = $row['maxdate'];
    mysql_close($conn);
    return date("YmdHis\Z", $maxdate-3600);
}