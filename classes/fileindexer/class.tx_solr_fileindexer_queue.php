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
 * Queue of files to index.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Markus Goldbach <markus.goldbach@dkd.de>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_fileindexer_Queue extends tx_solr_indexqueue_Queue implements tx_solr_IndexQueueInitializationPostProcessor {

	/**
	 * Whether to use the referencing language during duplicate check.
	 *
	 * @var boolean
	 */
	protected $useLanguageForDuplicateCheck = FALSE;

	/**
	 * Whether to use the file checksum (sha1) during duplicate check.
	 *
	 * @var boolean
	 */
	protected $useFileChecksumForDuplicateCheck = FALSE;


	/**
	 * Updates a file's entry in the file indexer queue.
	 *
	 * Also serves to add a file to the queue to make the API consistent with
	 * the content indexing queue API and the Solr API.
	 *
	 * @param	tx_solr_fileindexer_File	$file The file to update the entry for.
	 * @param	boolean	$forceAdd Force adding the file to the File Index Queue, skipping the check whether it is already in the queue
	 * @param	boolean	$forceUpdate Force updating the file although it already exists in the queue and although it has not been touched in the file system
	 */
	public function updateFile(tx_solr_fileindexer_File $file, $forceAdd = FALSE, $forceUpdate = FALSE) {
		if ($forceAdd || !$this->containsFile($file)) {
				// add
			$this->addFile($file);
		} elseif ($this->containsFile($file)) {
				// update
			$existingFileDetectionClause = $this->getFileDuplicateDetectionClause($file);

			$exisitingFilesUidRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'uid, changed',
				'tx_solr_indexqueue_file',
				$existingFileDetectionClause
			);

			$fileUids = array();
			foreach ($exisitingFilesUidRecords as $exisitingFilesUidRecord) {
				if ($forceUpdate || $file->getFileLastChangedTime() > $exisitingFilesUidRecord['changed']) {
					$fileUids[] = $exisitingFilesUidRecord['uid'];
				}
			}

			if (!empty($fileUids)) {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tx_solr_indexqueue_file',
					'uid IN(' .implode(', ', $fileUids) . ')',
					array('changed' => time())
				);
			}
		}
	}

	/**
	 * Actually adds a file to the File Index Queue.
	 *
	 * Not meant for public use.
	 *
	 * @param	tx_solr_fileindexer_File	$file The file to add to the queue.
	 */
	private function addFile(tx_solr_fileindexer_File $file) {
		if ($this->isAllowedFileType($file)) {

			$fileRecord = array(
				'pid'     => $file->getReferencePageId(),
				'root'    => $file->getReferenceRootPageId(),
				'changed' => $file->getLastChanged(),

				'access'            => $file->getAccess(),
				'additional_fields' => serialize($file->getAdditionalFields()),

				'reference_type'             => $file->getReferenceType(),
				'reference_uid'              => $file->getReferenceUniqueId(),
				'reference_document'         => serialize($file->getReferencePageDocument()),
				'reference_document_id'      => $file->getReferencePageDocumentId(),
				'reference_sys_language_uid' => $file->getReferenceLanguage(),

				'file_path' => $file->getRelativePath(tx_solr_fileindexer_File::PATH_EXCLUDE_FILE_NAME),
				'file_name' => $file->getName(),
				'file_sha1' => $file->getSha1()
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_solr_indexqueue_file',
				$fileRecord
			);

		}
	}

	/**
	 * Marks a file as failed and causes the indexer to skip the item in the
	 * next run.
	 *
	 * @param int|tx_solr_fileindexer_File $file Either the file's Index Queue uid or the complete file object
	 * @param string Error message
	 */
	public function markFileAsFailed($file, $errorMessage = '') {
		$fileUid = 0;

		if ($file instanceof tx_solr_fileindexer_File) {
			$fileUid = $file->getFileIndexQueueId();
		} else {
			$fileUid = (int) $file;
		}

		if (empty($errorMessage)) {
				// simply set to "TRUE"
			$errorMessage = '1';
		}

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_solr_indexqueue_file',
			'uid = ' . $fileUid ,
			array(
				'errors' => $errorMessage
			)
		);
	}

	/**
	 * Checks a file is allowed to be indexed according to plugin.tx_solr.index.files.allowedTypes
	 *
	 * @param tx_solr_fileindexer_File $file File to check whether it may be indexed
	 * @return boolean TRUE if the $file may be indexed, FALSE otherwise
	 */
	protected function isAllowedFileType(tx_solr_fileindexer_File $file) {
		$isAllowedFileType = FALSE;

		$siteRootPageId        = $file->getSite()->getRootPageId();
		$siteSolrConfiguration = tx_solr_Util::getSolrConfigurationFromPageId($siteRootPageId);

		$allowedFileTypes = $siteSolrConfiguration['index.']['files.']['allowedTypes'];
		$allowedFileTypes = t3lib_div::trimExplode(',', $allowedFileTypes);

		if (in_array('*', $allowedFileTypes) || in_array($file->getExtension(), $allowedFileTypes)) {
			$isAllowedFileType = TRUE;
		}

		return $isAllowedFileType;
	}

	/**
	 * Removes a file from the File Index Queue.
	 *
	 * @param	integer	$fileId The file's File Index Queue ID to remove.
	 */
	public function deleteFile($fileId) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_solr_indexqueue_file',
			'uid = ' . intval($fileId)
		);
	}

	/**
	 * Deletes files associated with a referencing object from the File Index Queue.
	 *
	 * @param	integer	$pageId Reference page ID
	 * @param	string	$type Reference type
	 * @param	integer	$uid Reference unique ID
	 */
	public function deleteFileByReference($pageId, $type = '', $uid = 0) {
		$whereClause = 'pid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
			intval($pageId),
			'tx_solr_indexqueue_file'
		);

		if (!empty($type) && !empty($uid)) {
			$whereClause .= ' AND reference_type = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
				$type,
				'tx_solr_indexqueue_file'
			)
			. ' AND reference_uid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
				intval($uid),
				'tx_solr_indexqueue_file'
			);
		}

		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_solr_indexqueue_file',
			$whereClause
		);
	}

	/**
	 * Removes all files of a certain site from the File Index Queue.
	 *
	 * @param tx_solr_Site $site The site to remove items for.
	 */
	public function deleteFilesBySite(tx_solr_Site $site) {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'tx_solr_indexqueue_file',
			'root = ' . $site->getRootPageId()
		);
	}

	/**
	 * Gets files associated with a referencing object from the File Index Queue.
	 *
	 * @param integer $pageId Reference page ID
	 * @param string $type Reference type
	 * @param integer $uid Reference unique ID
	 * @return array An array of tx_solr_fileindexer_File objects
	 */
	public function getFilesByReference($pageId, $type = '', $uid = 0) {
		$files = array();

		$whereClause = 'pid = ' . intval($pageId);

		if (!empty($type) && !empty($uid)) {
			$whereClause .= ' AND reference_type = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
				$type,
				'tx_solr_indexqueue_file'
			)
			. ' AND reference_uid = ' . intval($uid);
		}

		$fileIndexQueueRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_solr_indexqueue_file',
			$whereClause
		);

		foreach ($fileIndexQueueRecords as $fileIndexQueueRecord) {
			$files[] = tx_solr_fileindexer_File::getFileFromFileIndexQueueRecord($fileIndexQueueRecord);
		}

		return $files;
	}

	/**
	 * Checks whether a given file is in the queue already.
	 *
	 * Due to the nature of the uploads folder we can not rely on the sha1 file
	 * checksum by default. Files might be linked multiple ties and thus we
	 * might have multiple copies of the same file.
	 *
	 * When using the API it is possible to force usage of the sha1 hash though.
	 *
	 * @param	tx_solr_fileindexer_File	$file File to check whether it's in the queue already.
	 */
	public function containsFile(tx_solr_fileindexer_File $file) {
		$fileDuplicateDetectionClause = $this->getFileDuplicateDetectionClause($file);

		$fileIsInQueue = (boolean) $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			'tx_solr_indexqueue_file',
			$fileDuplicateDetectionClause
		);

		return $fileIsInQueue;
	}

	/**
	 * Generates a SQL WHERE clause ready to use to detect whether a file is in
	 * the File Index Queue already.
	 *
	 * @param tx_solr_fileindexer_File $file File to check whether it's in the queue already.
	 * @return string SQL WHERE clause for file duplicate detection
	 */
	protected function getFileDuplicateDetectionClause(tx_solr_fileindexer_File $file) {
		$fileDuplicateDetectionClause = 'root = ' . intval($file->getReferenceRootPageId())

		. ' AND file_name = '. $GLOBALS['TYPO3_DB']->fullQuoteStr(
			$file->getName(),
			'tx_solr_indexqueue_file'
		)
		. ' AND file_path = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
			$file->getRelativePath(tx_solr_fileindexer_File::PATH_EXCLUDE_FILE_NAME),
			'tx_solr_indexqueue_file'
		)
		. ' AND reference_document_id = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
			$file->getReferencePageDocumentId(),
			'tx_solr_indexqueue_file'
		);

		if ($this->useLanguageForDuplicateCheck) {
			$fileDuplicateDetectionClause .= ' AND reference_sys_language_uid = '
				. intval($file->getReferenceLanguage());
		}

		if ($this->useFileChecksumForDuplicateCheck) {
			$fileDuplicateDetectionClause .= ' AND file_sha1 = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
				$file->getSha1(),
				'tx_solr_indexqueue_file'
			);
		}

		return $fileDuplicateDetectionClause;
	}

	/**
	 * Returns the uid of the last indexed file in the queue
	 *
	 * @return	integer	The last indexed item's ID.
	 */
	public function getLastIndexedFileId() {
		$lastIndexedFileId = 0;

		$lastIndexedFileRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid',
			'tx_solr_indexqueue_file',
			'',
			'',
			'indexed DESC',
			1
		);
		if ($lastIndexedFileRow[0]['uid']) {
			$lastIndexedFileId = $lastIndexedFileRow[0]['uid'];
		}

		return $lastIndexedFileId;
	}

	/**
	 * Returns the timestamp of the last indexing run.
	 *
	 * @return	integer	Timestamp of last index run.
	 */
	public function getLastIndexTime() {
		$lastIndexTime = 0;

		$lastIndexedRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'indexed',
			'tx_solr_indexqueue_file',
			'',
			'',
			'indexed DESC',
			1
		);

		if ($lastIndexedRow[0]['indexed']) {
			$lastIndexTime = $lastIndexedRow[0]['indexed'];
		}

		return $lastIndexTime;
	}

	/**
	 * Get next files to index.
	 *
	 * @return	array	Array of tx_solr_fileindexer_File objects to index
	 */
	public function getFilesToIndex($limit = 20) {
		$filesToIndex = array();

		$fileIndexQueueRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_solr_indexqueue_file',
			'changed > indexed'
				. ' AND errors = \'\'',
			'',
			'changed DESC, uid DESC',
			intval($limit)
		);

		foreach ($fileIndexQueueRecords as $fileIndexQueueRecord) {
				$file = tx_solr_fileindexer_File::getFileFromFileIndexQueueRecord($fileIndexQueueRecord);

				if (file_exists($file->getAbsolutePath())) {
					$filesToIndex[] = $file;
				} else {
					t3lib_div::devLog('File does not exist', 'solr', 0, array($file));

						// delete the file from the index queue
					$this->deleteFile($fileIndexQueueRecord['uid']);

						// delete the file from the solr index
					$documentId        = tx_solr_Util::getFileDocumentId($file);
					$connectionManager = t3lib_div::makeInstance('tx_solr_ConnectionManager');
					$connections       = $connectionManager->getConnectionsBySite($file->getSite());

					foreach ($connections as $connection) {
						$connection->deleteByQuery('id:' . $documentId);
					}
				}
		}

		return $filesToIndex;
	}

	/**
	 * Removes all files entries from tx_solr_indexqueue_file for a given site.
	 *
	 * @param tx_solr_Site $site The site to initialize
	 */
	public function initialize(tx_solr_Site $site) {
		$this->deleteFilesBySite($site);
	}

	/**
	 * Post process Index Queue initialization
	 *
	 * @param tx_solr_Site $site The site to initialize
	 * @param array $indexingConfigurations Initialized indexing configurations
	 * @param array $initializationStatus Results of Index Queue initializations
	 */
	public function postProcessIndexQueueInitialization(tx_solr_Site $site, array $indexingConfigurations, array $initializationStatus) {
		if (in_array('pages', $indexingConfigurations) && $initializationStatus['pages']) {
			$this->initialize($site);
		}
	}

	/**
	 * Sets whether to use the referencing language during duplicate check.
	 *
	 * @param boolean $useLanguage
	 */
	public function setUseLanguageForDuplicateCheck($useLanguage) {
		$this->useLanguageForDuplicateCheck = (boolean) $useLanguage;
	}

	/**
	 * Gets whether to use the referencing language during duplicate check.
	 *
	 * @return boolean
	 */
	public function getUseLanguageForDuplicateCheck() {
		return $this->useLanguageForDuplicateCheck;
	}

	/**
	 * Sets whether to use the file checksum (sha1) during duplicate check.
	 *
	 * @param boolean $useFileChecksum
	 */
	public function setUseFileChecksumForDuplicateCheck($useFileChecksum) {
		$this->useFileChecksumForDuplicateCheck = (boolean) $useFileChecksum;
	}

	/**
	 * Gets whether to use the file checksum (sha1) during duplicate check.
	 *
	 * @return boolean
	 */
	public function getUseFileChecksumForDuplicateCheck() {
		return $this->useFileChecksumForDuplicateCheck;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_queue.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_queue.php']);
}

?>