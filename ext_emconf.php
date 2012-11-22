<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "solrfile".
 *
 * Auto generated 22-11-2012 16:40
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Apache Solr for TYPO3 - File Indexing',
	'description' => 'File Indexing',
	'category' => 'plugin',
	'author' => 'Ingo Renner',
	'author_email' => 'ingo@typo3.org',
	'shy' => '',
	'dependencies' => 'solr',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'dkd Internet Service GmbH',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'solr' => '2.8.0',
			'typo3' => '4.5.5-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'tika' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:33:{s:9:"ChangeLog";s:4:"d9bb";s:16:"ext_autoload.php";s:4:"3158";s:12:"ext_icon.gif";s:4:"11e4";s:17:"ext_localconf.php";s:4:"0607";s:14:"ext_tables.php";s:4:"207c";s:14:"ext_tables.sql";s:4:"4ee9";s:41:"classes/class.tx_solr_extractingquery.php";s:4:"0639";s:46:"classes/class.tx_solr_filegarbagecollector.php";s:4:"a2fb";s:48:"classes/class.tx_solr_typo3pagefileextractor.php";s:4:"d388";s:41:"classes/class.tx_solrfile_classloader.php";s:4:"ff7c";s:75:"classes/fileindexer/class.tx_solr_fileindexer_attachmentdetectorfactory.php";s:4:"4faa";s:54:"classes/fileindexer/class.tx_solr_fileindexer_file.php";s:4:"ca93";s:69:"classes/fileindexer/class.tx_solr_fileindexer_filedetectorfactory.php";s:4:"6f9e";s:61:"classes/fileindexer/class.tx_solr_fileindexer_fileindexer.php";s:4:"3469";s:55:"classes/fileindexer/class.tx_solr_fileindexer_queue.php";s:4:"67ba";s:78:"classes/fileindexer/class.tx_solr_fileindexer_unknownfiledetectorexception.php";s:4:"c62b";s:96:"classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_abstract.php";s:4:"0b38";s:91:"classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_dam.php";s:4:"d581";s:97:"classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_fileadmin.php";s:4:"0dee";s:84:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_abstract.php";s:4:"3f19";s:88:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_cssfilelinks.php";s:4:"628f";s:79:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_dam.php";s:4:"73cd";s:88:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_damfilelinks.php";s:4:"da66";s:88:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_damttcontent.php";s:4:"1920";s:85:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_fileadmin.php";s:4:"b9f1";s:87:"classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_templavoila.php";s:4:"c1c6";s:51:"interfaces/interface.tx_solr_attachmentdetector.php";s:4:"7f79";s:45:"interfaces/interface.tx_solr_filedetector.php";s:4:"e021";s:18:"lang/locallang.xml";s:4:"e6b0";s:49:"report/class.tx_solr_report_fileindexerstatus.php";s:4:"7a0c";s:62:"scheduler/class.tx_solr_scheduler_fileindexqueueworkertask.php";s:4:"515f";s:85:"scheduler/class.tx_solr_scheduler_fileindexqueueworkertaskadditionalfieldprovider.php";s:4:"d797";s:25:"static/solrfile/setup.txt";s:4:"6f80";}',
	'suggests' => array(
	),
);

?>