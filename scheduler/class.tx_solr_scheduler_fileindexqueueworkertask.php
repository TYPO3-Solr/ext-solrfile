<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2012 Ingo Renner <ingo@typo3.org>
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
 * Scheduler Task to index files from the file Index Queue
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Markus Goldbach <markus.goldbach@dkd.de>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_scheduler_FileIndexQueueWorkerTask extends tx_scheduler_Task implements tx_scheduler_ProgressProvider {

	protected $filesToIndexLimit = 10;


	/**
	 * Works through the file Index Queue and indexes the queued files into Solr.
	 *
	 * @return	boolean	Returns TRUE on success
	 * @see	typo3/sysext/scheduler/tx_scheduler_Task#execute()
	 */
	public function execute() {
		$executionSucceeded  = FALSE;

		$limit          = $this->filesToIndexLimit;
		$fileIndexQueue = t3lib_div::makeInstance('tx_solr_fileindexer_Queue');
		$indexer        = t3lib_div::makeInstance('tx_solr_fileindexer_FileIndexer');

		$filesToIndex = $fileIndexQueue->getFilesToIndex($limit);
		foreach ($filesToIndex as $fileToIndex) {
			$fileIndexed = FALSE;

			try {
				$fileIndexed = $indexer->index($fileToIndex);

					// update IQ file so that the IQ can determine what's been indexed already
				if ($fileIndexed) {
					$fileToIndex->updateIndexedTime();
				}
			} catch (Exception $e) {
				$fileIndexQueue->markFileAsFailed(
					$fileToIndex,
					$e->getCode() . ': ' . $e->__toString()
				);

				t3lib_div::devLog(
					'Failed indexing Index Queue file ' . $fileToIndex->getFileIndexQueueId(),
					'solr',
					3,
					array(
						'code'    => $e->getCode(),
						'message' => $e->getMessage(),
						'trace'   => $e->getTrace(),
						'file'    => (array) $fileToIndex
					)
				);
			}
		}
		$executionSucceeded = TRUE;

		return $executionSucceeded;
	}

	/**
	 * Returns some additional information about indexing progress, shown in
	 * the scheduler's task overview list.
	 *
	 * @return	string	Information to display
	 */
	public function getAdditionalInformation() {
		$fileIndexQueue = t3lib_div::makeInstance('tx_solr_fileindexer_Queue');

		$totalFilesCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tx_solr_indexqueue_file'
		);

		if ($totalFilesCount == 0) {
			$filesIndexedPercentage = 100;
		} else {
			$remainingFilesCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
				'uid',
				'tx_solr_indexqueue_file',
				'changed > indexed'
			);
			$filesIndexedCount = $totalFilesCount - $remainingFilesCount;

			$filesIndexedPercentage = $filesIndexedCount * 100 / $totalFilesCount;
			$filesIndexedPercentage = round($filesIndexedPercentage, 2);
		}

		$message = 'Indexed ' . $filesIndexedPercentage . '%.';

		$failedFilesCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tx_solr_indexqueue_file',
			'errors != \'\''
		);
		if ($failedFilesCount) {
			$message .= ' Failures: ' . $failedFilesCount;
		}

		return $message;
	}

	/**
	 * Gets the indexing progress.
	 *
	 * @return float Indexing progress as a two decimal precision float. f.e. 44.87
	 */
	public function getProgress() {
		$itemsIndexedPercentage = 0.0;

		$totalItemsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tx_solr_indexqueue_file'
		);
		$remainingItemsCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tx_solr_indexqueue_file',
			'changed > indexed'
		);
		$itemsIndexedCount = $totalItemsCount - $remainingItemsCount;

		if ($totalItemsCount > 0) {
			$itemsIndexedPercentage = $itemsIndexedCount * 100 / $totalItemsCount;
			$itemsIndexedPercentage = round($itemsIndexedPercentage, 2);
		}

		return $itemsIndexedPercentage;
	}

	public function getFilesToIndexLimit() {
		return $this->filesToIndexLimit;
	}

	public function setFilesToIndexLimit($limit) {
		$this->filesToIndexLimit = intval($limit);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/scheduler/class.tx_solr_scheduler_fileindexqueueworkertask.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/scheduler/class.tx_solr_scheduler_fileindexqueueworkertask.php']);
}
?>