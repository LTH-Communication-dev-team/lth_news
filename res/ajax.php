<?php

// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');

$action = htmlspecialchars(t3lib_div::_GP("action"));
$table = htmlspecialchars(t3lib_div::_GP("table"));
$query = htmlspecialchars(t3lib_div::_GP("query"));
$sid = htmlspecialchars(t3lib_div::_GP("sid"));

tslib_eidtools::connectDB();


switch($action) {
    case "updateIndex":
        $content = updateIndex($table,$query);
        break;
}

echo json_encode($content);

function initTSFE($pageUid=1)
{
    require_once(PATH_tslib.'class.tslib_fe.php');
    require_once(PATH_t3lib.'class.t3lib_userauth.php');
    require_once(PATH_tslib.'class.tslib_feuserauth.php');
    require_once(PATH_t3lib.'class.t3lib_cs.php');
    require_once(PATH_tslib.'class.tslib_content.php');
    require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
    require_once(PATH_t3lib.'class.t3lib_page.php');

    //$TSFEclassName = t3lib_div::makeInstance('tslib_fe');

    if (!is_object($GLOBALS['TT'])) {
        $GLOBALS['TT'] = new t3lib_timeTrack;
        $GLOBALS['TT']->start();
    }

    // Create the TSFE class.
    //$GLOBALS['TSFE'] = new $TSFEclassName($GLOBALS['TYPO3_CONF_VARS'],$pageUid,'0',1,'','','','');
    $GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe');
    $GLOBALS['TSFE']->connectToDB();
    $GLOBALS['TSFE']->initFEuser();
    $GLOBALS['TSFE']->fetch_the_id();
    $GLOBALS['TSFE']->getPageAndRootline();
    $GLOBALS['TSFE']->initTemplate();
    $GLOBALS['TSFE']->tmpl->getFileName_backPath = PATH_site;
    $GLOBALS['TSFE']->forceTemplateParsing = 1;
    $GLOBALS['TSFE']->getConfigArray();
}

function updateIndex($table,$query)
{
    $feUserObject = tslib_eidtools::initFeUser();
    $data = json_decode(html_entity_decode($query));
    $orgUid =  $data->uid;
    $pid = $data->pid;
    $where = $data->where;

    $sortingArray = array();
    //Get new sorting
    $res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,sorting', "pages", "pid=".intval($pid),"","sorting") or die("58; ".mysql_error());
    while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
	$uid = $row["uid"];
	$sorting = $row["sorting"];
	$sortingArray[$uid] = $sorting;
    }
    $GLOBALS["TYPO3_DB"]->sql_free_result($res);
    
    if($where=='first') {
	$sortingArray[$orgUid] = 0;
    } else {
	$sortingArray[$orgUid] = $sortingArray[intval($where)] + 1;
    }
	
    asort($sortingArray);
    
    $content .= print_r($sortingArray,true);
    $i=0;
    foreach ($sortingArray as $key => $value) {
	$i = $i+128;
	updateSorting($key,$i);
	$sortingArray[$key] = $i;
    }
    
    //Solr
    $scheme = 'http';
    $host = 'www2.lth.se';
    $port = '8080';
    $path = '/solr/typo3_sv/';

    $solr = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnection($host, $port, $path, $scheme);
    $solrQuery = "pid:$pid";
    $results = false;
    
    try {
        $response = $solr->search($solrQuery, 0);
    }
    catch(Exception $e) {
        $content = '99:' . $e->getMessage();
    }
    
    if(isset($response->response->docs[0])) {
 
        foreach($response->response->docs as $document) {
            $doc = array();
            foreach($document as $field => $value) {
                $doc[$field] = $value;		
            }
            $doc['sorting_intS'] = $sortingArray[$document->uid];
	    $doc['appKey'] = 'EXT:solr';
	    $docs[] = $doc;
        }
         
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
	    //echo 'getFeUsers done!';
	}
	catch ( Exception $e ) {
	    echo $e->getMessage();
	}

    } else {
        $content .= "No data!";
    }
    return $content.$where.$orgUid.$pid;
}

function updateSorting($uid,$sorting)
{
    $updateArray = array('sorting' => $sorting, 'tstamp' => time());
    $res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery("pages", "uid=" . intval($uid), $updateArray);
}