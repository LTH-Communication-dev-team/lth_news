<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Tomas Havner <tomas.havner@kansli.lth.se>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'LTH News' for the 'lth_news' extension.
 *
 * @author	Tomas Havner <tomas.havner@kansli.lth.se>
 * @package	TYPO3
 * @subpackage	tx_lthnews
 */
class tx_lthnews_pi1 extends tslib_pibase {
    public $prefixId      = 'tx_lthnews_pi1';		// Same as class name
    public $scriptRelPath = 'pi1/class.tx_lthnews_pi1.php';	// Path to this script relative to the extension dir.
    public $extKey        = 'lth_news';	// The extension key.
    public $pi_checkCHash = TRUE;

    /**
     * The main method of the Plugin.
     *
     * @param string $content The Plugin content
     * @param array $conf The Plugin configuration
     * @return string The content that is displayed on the website
     */
    public function main($content, array $conf)
    {
	$this->conf = $conf;
	$this->pi_setPiVarDefaults();
	$this->pi_loadLL();
	
	$GLOBALS["TSFE"]->additionalHeaderData["lth_news_js"] = "<script language=\"JavaScript\" type=\"text/javascript\" src=\"/typo3conf/ext/lth_news/res/lth_news.js\"></script>"; 

	$content = '';

	$pid = intval($GLOBALS['TSFE']->id);

	if($pid) {
	    $resultDocuments = $this->getSolrData($pid);
	    if($resultDocuments) {
		$content = $this->newsList($resultDocuments);
	    }
	} else {
	    $content = 'No pid!';
	}

	return $content;
    }

    function newsList($resultDocuments)
    {
	    // Get the template
	$cObj = t3lib_div::makeInstance('tslib_cObj');
	$templateHtml = $cObj->fileResource("typo3conf/ext/lth_news/templates/tx_lth_news.html");
	    // Extract subparts from the template
	$subpart = $cObj->getSubpart($templateHtml, '###TEMPLATE_LIST###');
	$markerArray = array();
	$content = '';

	if ($resultDocuments) {
	    //if ( $numberOfHits > 0 ) {
	    foreach ( $resultDocuments as $doc ) {

		$markerArray['###NEWS_UID###'] = '';
		$markerArray['###NEWS_TITLE###'] = '';
		$markerArray['###NEWS_DATE###'] = '';
		$markerArray['###NEWS_IMAGE###'] = '';
		$markerArray['###NEWS_SUBHEADER###'] = '';
		$markerArray['###NEWS_DATE###'] = '';

		// Fill marker array
		$markerArray['###NEWS_UID###'] = $doc->uid;
		$markerArray['###NEWS_TITLE###'] = $doc->title;
		$markerArray['###NEWS_DATE###'] = $doc->first_name;
		if(is_array($doc->image_stringM)) {
		    //$i=0;
		    //foreach($doc->image_stringM as $key => $value) {
			$markerArray['###NEWS_IMAGE###'] = $this->getImageMarker($doc->image_stringM[0],$doc->vpixels_intM[0],$doc->hpixels_intM[0]);
			//$i++;
		    //}
		} else if($doc->image_stringM) {
		    $markerArray['###NEWS_IMAGE###'] = $this->getImageMarker($doc->image_stringM,$doc->vpixels_intM,$doc->hpixels_intM);
		}
		$markerArray['###NEWS_SUBHEADER###'] = $doc->displayContent_textM[0];
		if($doc->date_stringS) {
		    $markerArray['###NEWS_DATE###'] = gmdate("Y-m-d\Z", $doc->date_stringS);
		}

		// Create the content by replacing the content markers in the template
		$content .= $cObj->substituteMarkerArray($subpart, $markerArray);
	    }
	//}//A new comment
	    $content = "<div id=\"sortable\">$content<input type=\hidden\" id=\"lth_news_pid\" value=\"" . $GLOBALS['TSFE']->id . "\" /></div>";
	} else {
	    $content = '<div>NO!</div>';
	}
	
	return $content;
    }
    
    function getImageMarker($inputImage,$inputVpixels,$inputHpixels)
    {
	$content = '';
	$hpixels = 20;
	$vpixels = 20;
	if($inputVpixels and $inputHpixels) {
	    $ratio = 200 / intval($inputVpixels);
	    $vpixels = intval($inputVpixels) * $ratio . 'px';
	    $hpixels = intval($inputHpixels) * $ratio . 'px';
	}
	$content = "<img src=\"uploads/pics/$inputImage\" style=\"width:$vpixels;height:$hpixels;\" />";
	
	return $content;
    }

    function getSolrData($pid)
    {
	$scheme = 'http';
	$host = 'www2.lth.se';
	$port = '8080';
	$path = '/solr/typo3_sv/';

	$solrConnection = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnection($host, $port, $path, $scheme);
	if($solrConnection) {
	    $search = t3lib_div::makeInstance('tx_solr_Search', $solrConnection);

	    $query = t3lib_div::makeInstance('tx_solr_Query', '*');
	    //$query->useRawQueryString('true');
	    $query->addFilter("pid:$pid");
	    $query->setSorting('sorting_intS ASC');
	    $search->search($query);

	    $resultDocuments = $search->getResultDocuments();

	    //$this->debug($resultDocuments);
	    
	    return $resultDocuments;
	} 

	
    }

    function debug($debugContent)
    {
	print '<pre>';
	print_r($debugContent);
	print '</pre>';
    }
}



if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lth_news/pi1/class.tx_lthnews_pi1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/lth_news/pi1/class.tx_lthnews_pi1.php']);
}

?>