<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TYPO3_CONF_VARS['FE']['eID_include']['tx_lthnews_pi1'] = 'EXT:lth_news/res/ajax.php';

t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_lthnews_pi1.php', '_pi1', 'list_type', 1);
?>