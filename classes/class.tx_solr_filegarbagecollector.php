<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Ingo Renner <ingo@typo3.org>
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


/**
 * File Garbage Collector, removes related file documents from the index
 * after regular garbage collection.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_FileGarbageCollector implements tx_solr_GarbageCollectorPostProcessor {

	/**
	 * Post processing of garbage collector.
	 *
	 * @param string $table The record's table name.
	 * @param integer $uid The record's uid.
	 * @see tx_solr_GarbageCollector->collectGarbage()
	 */
	public function postProcessGarbageCollector($table, $uid) {
		$this->collectFileGarbage($table, $uid);
	}

	/**
	 * Removes file index documents associated with a record.
	 *
	 * @param string $table The record's table name.
	 * @param integer $uid The record's uid.
	 */
	public function collectFileGarbage($table, $uid) {
		$referencingRecord = t3lib_BEfunc::getRecord($table, $uid, 'pid', '', FALSE);
		$pageId            = $referencingRecord['pid'];

		$referenceType     = $table;
		$referenceUid      = $uid;

			// build query, need to differentiate for the case when deleting whole pages
		$query = array('type:' . tx_solr_fileindexer_File::SOLR_DOCUMENT_TYPE);
		if ($table == 'pages') {
			$pageId = $uid;
		} else {
			$query[] = 'fileReferenceType:' . $table;
			$query[] = 'fileReferenceUid:'  . $uid;
		}
		$query[] = 'pid:' . $pageId;

		try {
			$files = t3lib_div::makeInstance('tx_solr_fileindexer_Queue')->getFilesByReference(
				$pageId,
				$referenceType,
				$referenceUid
			);

			foreach ($files as $file) {
				$solr = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnectionByRootPageId(
					$file->getReferenceRootPageId(),
					$file->getReferenceLanguage()
				);

				$query['uid'] = 'uid:' . $file->getFileIndexQueueId();

					// delete document(s) from index, directly commit
				$solr->deleteByQuery(implode(' AND ', $query));
				$solr->commit(FALSE, FALSE, FALSE);
			}

				// remove from file index queue
			t3lib_div::makeInstance('tx_solr_fileindexer_Queue')->deleteFileByReference(
				$pageId,
				$referenceType,
				$referenceUid
			);
		} catch (tx_solr_NoSolrConnectionFoundException $e) {
			if ($e->getRootPageId() != 0) {
					// only log if we have an item that belongs to a site
				t3lib_div::devLog(
					'Failed to get Solr connection while trying to collect garbage file documents.',
					'solr',
					3,
					array(
						'Exception Message'  => $e->getMessage(),
						'Exception Code'     => $e->getCode(),
						'Exception Trace'    => $e->getTrace(),
						'Record'             => $table . ':' . $uid,
						'Page ID used to get Solr Connection' => $pageId,
						'Query'              => implode(' AND ', $query)
					)
				);
			}
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/class.tx_solr_filegarbagecollector.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/class.tx_solr_filegarbagecollector.php']);
}

?>