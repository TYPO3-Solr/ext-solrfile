<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

	// TypoScript
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/solrfile/', 'Apache Solr - File Indexing');


if (TYPO3_MODE == 'BE') {

		// registering reports
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['solr'][] = 'tx_solr_report_FileIndexerStatus';

}


?>