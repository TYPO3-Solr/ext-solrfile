<?php
$extensionPath = t3lib_extMgm::extPath('solrfile');
return array(

	'tx_solrfile_classloader' => $extensionPath . 'classes/class.tx_solrfile_classloader.php',

	'tx_solr_extractingquery' => $extensionPath . 'classes/class.tx_solr_extractingquery.php',
	'tx_solr_filegarbagecollector' => $extensionPath . 'classes/class.tx_solr_filegarbagecollector.php',
	'tx_solr_typo3pagefileextractor' => $extensionPath . 'classes/class.tx_solr_typo3pagefileextractor.php',

	'tx_solr_fileindexer_attachmentdetectorfactory' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_attachmentdetectorfactory.php',
	'tx_solr_fileindexer_file' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_file.php',
	'tx_solr_fileindexer_fileindexer' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_fileindexer.php',
	'tx_solr_fileindexer_queue' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_queue.php',
	'tx_solr_fileindexer_filedetectorfactory' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_filedetectorfactory.php',
	'tx_solr_fileindexer_unknownfiledetectorexception' => $extensionPath . 'classes/fileindexer/class.tx_solr_fileindexer_unknownfiledetectorexception.php',

	'tx_solr_fileindexer_attachmentdetector_abstract' => $extensionPath . 'classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_abstract.php',
	'tx_solr_fileindexer_attachmentdetector_dam' => $extensionPath . 'classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_dam.php',
	'tx_solr_fileindexer_attachmentdetector_fileadmin' => $extensionPath . 'classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_fileadmin.php',

	'tx_solr_fileindexer_filedetector_abstract' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_abstract.php',
	'tx_solr_fileindexer_filedetector_cssfilelinks' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_cssfilelinks.php',
	'tx_solr_fileindexer_filedetector_dam' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_dam.php',
	'tx_solr_fileindexer_filedetector_damfilelinks' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_damfilelinks.php',
	'tx_solr_fileindexer_filedetector_damttcontent' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_damttcontent.php',
	'tx_solr_fileindexer_filedetector_fileadmin' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_fileadmin.php',
	'tx_solr_fileindexer_filedetector_templavoila' => $extensionPath . 'classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_templavoila.php',

			// scheduler tasks

	'tx_solr_scheduler_fileindexqueueworkertask' => $extensionPath . 'scheduler/class.tx_solr_scheduler_fileindexqueueworkertask.php',
	'tx_solr_scheduler_fileindexqueueworkertaskadditionalfieldprovider' => $extensionPath . 'scheduler/class.tx_solr_scheduler_fileindexqueueworkertaskadditionalfieldprovider.php',

		// reports

	'tx_solr_report_fileindexerstatus' => $extensionPath . 'report/class.tx_solr_report_fileindexerstatus.php',

		// interfaces

	'tx_solr_attachmentdetector' => $extensionPath . 'interfaces/interface.tx_solr_attachmentdetector.php',
	'tx_solr_filedetector' => $extensionPath . 'interfaces/interface.tx_solr_filedetector.php',

);
?>