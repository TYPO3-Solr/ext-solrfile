<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

	// trigger loading of ext_autoload.php
tx_solrfile_ClassLoader::loadClasses();

   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

	// registering the file indexer to find (and later index) files attached to records

if (TYPO3_MODE == 'FE' && isset($_SERVER['HTTP_X_TX_SOLR_IQ'])) {
		// register file indexer as an additional document indexer, executed by the page indexer
	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['Indexer']['indexPageAddDocuments']['tx_solr_fileindexer_FileIndexer']    = 'tx_solr_fileindexer_FileIndexer';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']['tx_solr_Typo3PageFileExtractor'] = 'tx_solr_Typo3PageFileExtractor';
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['IndexQueueIndexer']['indexItemAddDocuments']['tx_solr_fileindexer_FileIndexer'] = 'tx_solr_fileindexer_FileIndexer';

   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

	// registering file indexer file detectors

$typo3Version = 0;
if (class_exists('t3lib_utility_VersionNumber')) {
	$typo3Version = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version);
} else {
	$typo3Version = t3lib_div::int_from_ver(TYPO3_version);
}

// Register traditional file detectors for TYPO3 < 6.0 only
if ($typo3Version < 6000000) {

	tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
		'tx_solr_fileindexer_filedetector_Fileadmin',
		array(
			'uploads' => 'media,select_key',
			'text'    => 'bodytext,header_link',
			'textpic' => 'bodytext,header_link',
			'table'   => 'bodytext,header_link'
		)
	);

	tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
		'tx_solr_fileindexer_filedetector_CssFileLinks',
		array('uploads' => 'media'),
		'css_filelinks'
	);

	tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
		'tx_solr_fileindexer_filedetector_Dam',
		array(
			'text'    => 'bodytext',
			'textpic' => 'bodytext'
		),
		'dam'
	);

	tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
		'tx_solr_fileindexer_filedetector_DamFileLinks',
		array('uploads' => 'tx_damfilelinks_filelinks'),
		'dam_filelinks'
	);

	#tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
	#	'tx_solr_fileindexer_filedetector_DamTtContent',
	#	array('image' => 'tx_damttcontent_files'),
	#	'dam_ttcontent'
	#);

	tx_solr_fileindexer_FileDetectorFactory::registerFileDetector(
		'tx_solr_fileindexer_filedetector_Templavoila',
		array(
			'templavoila_pi1' => 'tx_templavoila_flex',
		),
		'templavoila'
	);
}

	// records, fileamdin
tx_solr_fileindexer_AttachmentDetectorFactory::registerAttachmentDetector(
	'tx_solr_fileindexer_attachmentdetector_Fileadmin',
	array(
		'group:file',
		'group:file_reference',
		'text'
	)
);

	// records, dam
tx_solr_fileindexer_AttachmentDetectorFactory::registerAttachmentDetector(
	'tx_solr_fileindexer_attachmentdetector_Dam',
	array(
		'group:db',
		'text'
	),
	'dam'
);

   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

	// adding scheduler tasks

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_solr_scheduler_FileIndexQueueWorkerTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:solrfile/lang/locallang.xml:scheduler_fileindexqueueworker_title',
	'description'      => 'LLL:EXT:solrfile/lang/locallang.xml:scheduler_fileindexqueueworker_description',
	'additionalFields' => 'tx_solr_scheduler_FileIndexQueueWorkerTaskAdditionalFieldProvider'
);

   # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- # ----- #

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['postProcessIndexQueueInitialization']['fileIndexQueue'] = 'tx_solr_fileindexer_Queue';
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['solr']['postProcessGarbageCollector']['fileGarbageCollector']   = 'tx_solr_FileGarbageCollector';

?>