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
 * Indexer to create documents from files.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_fileindexer_FileIndexer
	extends tx_solr_indexqueue_AbstractIndexer
	implements tx_solr_AdditionalPageIndexer, tx_solr_AdditionalIndexQueueItemIndexer {


	public function __construct() {
		$this->type = 'tx_solr_file';
	}

	/**
	 * Provides additional documents that should be indexed together with a page.
	 *
	 * Actually used to "append" the file indexer to the regular page indexer
	 * and have it executed after that as the order of execution is important
	 * here. No additional documents are provided.
	 *
	 * @param Apache_Solr_Document $pageDocument The original page document.
	 * @param array $allDocuments An array containing all the documents collected until here, including the page document
	 * @return array Returns an array of additional Apache_Solr_Document objects
	 */
	public function getAdditionalPageDocuments(Apache_Solr_Document $pageDocument, array $allDocuments) {
		if (t3lib_div::makeInstance('tx_solr_Typo3Environment')->isFileIndexingEnabled()) {
			$pageDocument = tx_solr_Typo3PageIndexer::getPageSolrDocument();

			$fileExtractor = t3lib_div::makeInstance('tx_solr_Typo3PageFileExtractor');
			$files         = $fileExtractor->getFiles();

				// adding additional information
			foreach ($files as $file) {
				$file->setReferencePageDocument($pageDocument);
				$file->setReferencePageDocumentId($pageDocument->id);
				$file->setAccess($pageDocument->access);
			}

			$this->addFilesToIndexQueue($files);
		}
	}

	/**
	 * Provides additional documents that should be indexed together with an Index Queue item.
	 *
	 * @param tx_solr_indexqueue_Item $item The item currently being indexed.
	 * @param integer $language The language uid of the documents
	 * @param Apache_Solr_Document $itemDocument The original item document.
	 * @return array An array of additional Apache_Solr_Document objects
	 */
	public function getAdditionalItemDocuments(tx_solr_indexqueue_Item $item, $language, Apache_Solr_Document $itemDocument) {
		$files = array();

		$attachmentDetectors = t3lib_div::makeInstance('tx_solr_fileindexer_AttachmentDetectorFactory')
			->getAttachmentDetectorsForItem($item);

		foreach ($attachmentDetectors as $attachmentDetector) {
			$detectedFiles = $attachmentDetector->getFiles();

			$files = array_merge(
				$files,
				$detectedFiles
			);
		}

		if (!empty($files)) {

				// adding reference information
			foreach ($files as $file) {
				$file->setReference(
					$item->getType(),
					$item->getRecordPageId(),
					$item->getRecordUid()
				);
				$file->setReferenceRootPageId($item->getRootPageUid());

				$file->setReferencePageDocument($itemDocument); // FIXME page vs. item naming: setReferenceDocument()
				$file->setReferencePageDocumentId($itemDocument->id);
				$file->setReferenceLanguage($itemDocument->language);
				$file->setAccess($itemDocument->access);
			}

			$this->addFilesToIndexQueue($files);
		}
	}

	/**
	 * Adds each file to the file indexing queue.
	 *
	 * File are indexed asynchronously as they take more time to index than a
	 * simple page. Indexing files synchronously would block page generation
	 * and delivery.
	 *
	 * @param array $files An array of tx_solr_fileindexer_File objects.
	 */
	protected function addFilesToIndexQueue(array $files) {
		$fileIndexQueue = t3lib_div::makeInstance('tx_solr_fileindexer_Queue');

		foreach ($files as $file) {
			$fileIndexQueue->updateFile($file);
		}
	}


	// Indexing


	/**
	 * Index the given file to Solr
	 *
	 * @param tx_solr_fileindexer_File $file The file to index
	 * @return boolean TRUE if the file has been indexed successfully, FALSE otherwise
	 */
	public function index(tx_solr_fileindexer_File $file) {
		$fileIndexed = FALSE;

		$fileDocument = $this->getFileDocument($file);

		$solr = t3lib_div::makeInstance('tx_solr_ConnectionManager')->getConnectionByRootPageId(
			$file->getReferenceRootPageId(),
			$file->getReferenceLanguage()
		);
		$response = $solr->addDocument($fileDocument);

		if ($response->getHttpStatus() == 200) {
			$fileIndexed = TRUE;
		}

		return $fileIndexed;
	}

	/**
	 * Converts a tx_solr_fileindexer_File to an Apache Solr Document
	 *
	 * @param tx_solr_fileindexer_File The file to convert
	 * @return Apache_Solr_Document An Apache Solr Document
	 */
	protected function getFileDocument(tx_solr_fileindexer_File $file) {
		$document = t3lib_div::makeInstance('Apache_Solr_Document');
		$site     = $file->getSite();

		$document->setField('id',       tx_solr_Util::getFileDocumentId($file));
		$document->setField('site',     $site->getDomain());
		$document->setField('siteHash', $site->getSiteHash());
		$document->setField('appKey',   'EXT:solr');
		$document->setField('type',     'tx_solr_file');

			// system fields
		$document->setField('uid',      $file->getFileIndexQueueId());
		$document->setField('pid',      $file->getReferencePageId());
		$document->setField('changed',  tx_solr_Util::timestampToIso(
			filemtime($file->getAbsolutePath())
		)); // @see page indexer / TS processing for timestamp->ISO conversion

			// content
		$document->setField('title',    $file->getName());
		$document->setField('content',  $file->getContent());

			// access
		$document->setField('access',   $file->getAccess());
		// TODO add endtime of content element / page (fallback chain)

			// file meta data, reference
		$document->setField('fileMimeType',            $file->getMimeType());
		$document->setField('fileName',                $file->getName());
		$document->setField('fileRelativePath',        $file->getRelativePath());
		$document->setField('fileRelativePathOnly',    $file->getRelativePath(tx_solr_fileindexer_File::PATH_EXCLUDE_FILE_NAME));
		$document->setField('fileExtension',           $file->getExtension());
		$document->setField('fileSha1',                $file->getSha1());
		$document->setField('fileReferenceDocumentId', $file->getReferencePageDocumentId());
		$document->setField('fileReferenceType',       $file->getReferenceType());
		$document->setField('fileReferenceUid',        $file->getReferenceUniqueId());

		$referencePageDocument = $file->getReferencePageDocument();
		if (!is_null($referencePageDocument)) {
			$document->setField('fileReferenceTitle',  $referencePageDocument->title);
			$document->setField('fileReferenceUrl',    $referencePageDocument->url);
		}

		$document = $this->addDocumentFieldsFromPhpApi($document, $file);
		$document = $this->addDocumentFieldsFromTyposcript($document, $file);

		return $document;
	}

	/**
	 * Adds fields added to a file through the PHP API.
	 *
	 * @param Apache_Solr_Document $document Document to add fields to
	 * @param tx_solr_fileindexer_File $file File to take the fields from
	 * @return Apache_Solr_Document Modified document with added fields
	 */
	protected function addDocumentFieldsFromPhpApi(Apache_Solr_Document $document, tx_solr_fileindexer_File $file) {
		$additionalFields = $file->getAdditionalFields();

		foreach ($additionalFields as $fieldName => $fieldValue) {
			$document->setField($fieldName, $fieldValue);
		}

		return $document;
	}

	/**
	 * Reads the TypoScript configuration and adds fields to the document as
	 * defined in plugin.tx_solr.index.queue.tx_solr_file.fields
	 *
	 * @param Apache_Solr_Document $document Document to add fields to
	 * @param tx_solr_fileindexer_File $file File to use to retrieve the fields from TypoScript
	 * @return Apache_Solr_Document Modified document with added fields
	 */
	protected function addDocumentFieldsFromTyposcript(Apache_Solr_Document $document, tx_solr_fileindexer_File $file) {
		$site = $file->getSite();
		$solrConfiguration = $site->getSolrConfiguration();

		if (!empty($solrConfiguration['index.']['queue.']['tx_solr_file.']['fields.'])) {
			$indexingConfiguration = $solrConfiguration['index.']['queue.']['tx_solr_file.']['fields.'];

				// provide the existing document fields as data for the content object
			$data = array();
			foreach ($document as $fieldName => $fieldValue) {
				$data[$fieldName] = $fieldValue;
			}

				// need to instantiate TSFE to make content objects work properly
			$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $site->getRootPageId(), 0);
			$document = parent::addDocumentFieldsFromTyposcript($document, $indexingConfiguration, $data);
			unset($GLOBALS['TSFE']);
		}

		return $document;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_fileindexer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_fileindexer.php']);
}

?>