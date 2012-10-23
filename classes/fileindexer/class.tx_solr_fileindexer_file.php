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
 * An object representation for files.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_fileindexer_File {

	const PATH_INCLUDE_FILE_NAME = TRUE;
	const PATH_EXCLUDE_FILE_NAME = FALSE;

	const SOLR_DOCUMENT_TYPE = 'tx_solr_file';

	/**
	 * The file name.
	 *
	 * @var	string
	 */
	protected $name = '';

	/**
	 * File extension.
	 *
	 * @var	string
	 */
	protected $extension = '';

	/**
	 * Relative path of the file within the TYPO3 site root.
	 *
	 * @var	string
	 */
	protected $relativePath = '';

	/**
	 * Absolute path of the file from the file system root.
	 *
	 * @var	string
	 */
	protected $absolutePath = '/';

	/**
	 * The file's sha1 hash.
	 *
	 * @var	string
	 */
	protected $sha1Hash = '';

	/**
	 * The file's text content.
	 *
	 * @var	string
	 */
	protected $content = '';

	/**
	 * Frontend user group access restrictions as Access Rootline notation.
	 *
	 * Usually inheritet by the file's referencing object.
	 *
	 * @var	string
	 */
	protected $access = 'c:0';

	/**
	 * Unix timestamp of last change.
	 *
	 * Corresponds to the last change timestamp in tx_solr_indexqueue_file,
	 * inherited from the referencing object.
	 *
	 * Does NOT reflect the physical file's last modification time.
	 *
	 * @var	integer
	 */
	protected $lastChanged = 0;

	/**
	 * Type of the referencing object.
	 *
	 * Usally a table name.
	 *
	 * @var	string
	 */
	protected $referenceType = '';

	/**
	 * Unique ID (uid) of the object referencing / linking to this file.
	 *
	 * @var	integer
	 */
	protected $referenceUniqueId = 0;

	/**
	 * Page ID (pid) of the object referencing / linking to this file.
	 *
	 * @var	integer
	 */
	protected $referencePageId = 0;

	/**
	 * Site of this file.
	 *
	 * @var tx_solr_Site
	 */
	protected $site = NULL;

	/**
	 * Root page ID (pid) of the object referencing / linking to this file.
	 *
	 * @var	integer
	 */
	protected $referenceRootPageId = 0;

	/**
	 * System language of the object referencing / linking to this file.
	 *
	 * @var	integer
	 */
	protected $referenceLanguage = 0;

	/**
	 * Solr document of the referencing page.
	 *
	 * @var	Apache_Solr_Document
	 */
	protected $referencePageDocument = NULL;

	/**
	 * Solr document ID of the referencing page.
	 *
	 * @var	string
	 */
	protected $referencePageDocumentId = '';

	/**
	 * The ID of this file in the File Index Queue.
	 *
	 * @var	integer
	 */
	protected $fileIndexQueueId = 0;

	/**
	 * Additional fields to use when indexing the file to Solr.
	 *
	 * The array is a map of Solr field names with their values.
	 *
	 * @var array
	 */
	protected $additionalFields = array();


	/**
	 * constructor for class tx_solr_fileindexer_File
	 *
	 * @param	string	relative path to the file including the file name itself
	 * @return	void
	 */
	public function __construct($filePath) {
		$this->name         = basename($filePath);
		$this->relativePath = $filePath;
		$this->absolutePath = PATH_site . $filePath;

		$this->lastChanged  = time();

		$fileInfo = t3lib_div::split_fileref($this->absolutePath);
		$this->extension = $fileInfo['fileext'];
	}

	/**
	 * Gets a file object from the File Index Queue
	 *
	 * @param	integer	$indexQueueId Unique ID of the file record in tx_solr_indexqueue_file
	 * @return	tx_solr_fileindexer_File	A file object
	 * @throws	RuntimeException	when no file record with the given id can be found
	 */
	public static function getFileByFileIndexQueueId($indexQueueId) {
		$fileRecord = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'*',
			'tx_solr_indexqueue_file',
			'uid = ' . intval($indexQueueId)
		);

		if (!is_array($fileRecord)) {
			throw new RuntimeException(
				'No file found for the given File Index Queue ID',
				1296744910
			);
		}

		return self::getFileFromFileIndexQueueRecord($fileRecord);
	}

	/**
	 * Turns a File Index Queue record into a file object.
	 *
	 * @param	array	$fileRecord A record from tx_solr_indexqueue_file
	 * @return	tx_solr_fileindexer_File	A file object
	 */
	public static function getFileFromFileIndexQueueRecord(array $fileRecord) {
		$file = t3lib_div::makeInstance('tx_solr_fileindexer_File',
			$fileRecord['file_path'] . $fileRecord['file_name']
		);

		$file->setFileIndexQueueId($fileRecord['uid']);
		$file->setLastChanged($fileRecord['changed']);

		$file->setAccess($fileRecord['access']);
		$file->setAdditionalFields(unserialize($fileRecord['additional_fields']));

		$file->setReference(
			$fileRecord['reference_type'],
			$fileRecord['pid'],
			$fileRecord['reference_uid'],
			$fileRecord['reference_sys_language_uid']
		);

		$file->setReferencePageDocumentId($fileRecord['reference_document_id']);
		$file->setReferenceRootPageId($fileRecord['root']);

		$referencePageDocument = unserialize($fileRecord['reference_document']);
		if ($referencePageDocument instanceof Apache_Solr_Document) {
			$file->setReferencePageDocument($referencePageDocument);
		}

		return $file;
	}

	/**
	 * Gets the file name.
	 *
	 * @return	string	File name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Gets the file's relative path.
	 *
	 * @param	boolean	$includeFileName Whether to include the file name or not, use constants PATH_INCLUDE_FILE_NAME and PATH_EXCLUDE_FILE_NAME
	 * @return	string	relative path to the file.
	 */
	public function getRelativePath($includeFileName = TRUE) {
		$relativePath = $this->relativePath;

		if (!$includeFileName) {
			$relativePath = substr($relativePath, 0, (strlen($this->name) * -1));
		}

		return $relativePath;
	}

	/**
	 * Gets the file's absolute path.
	 *
	 * @param	boolean	$includeFileName Whether to include the file name or not, use constants PATH_INCLUDE_FILE_NAME and PATH_EXCLUDE_FILE_NAME
	 * @return	string	absolute path to the file.
	 */
	public function getAbsolutePath($includeFileName = TRUE) {
		$absolutePath = $this->absolutePath;

		if (!$includeFileName) {
			$absolutePath = substr($absolutePath, 0, (strlen($this->name) * -1));
		}

		return $absolutePath;
	}

	/**
	 * Gets the (unix)time the file was last modified.
	 *
	 * @return integer Unix timestamp of the last modification of the file
	 */
	public function getFileLastChangedTime() {
		return filemtime($this->absolutePath);
	}

	/**
	 * Gets the file's sha1 hash.
	 *
	 * @return	string	The file's sha1 hash.
	 */
	public function getSha1() {
		if (empty($this->sha1Hash)) {
			$this->sha1Hash = sha1_file($this->absolutePath);
		}

		return $this->sha1Hash;
	}

	/**
	 * Gets a file's textual content or if it is not a text file it's textual
	 * representation.
	 *
	 * @return	string	The file's string content.
	 */
	public function getContent() {

		if (empty($this->content)) {
			$fileContent = '';
			$mimeType    = $this->getMimeType();

			if ($mimeType == 'text/plain') {
					// we can read text files directly
				$fileContent = file_get_contents($this->absolutePath);
			} elseif ($this->canExtractText()) {
					// other subtypes should be handled by the text service
				$service = t3lib_div::makeInstanceService('textExtract', $this->extension);
				if (!is_object($service) || is_array($service)) {
					throw new RuntimeException(
						'Failed to initialize a text extraction service.',
						'1291394251'
					);
				}

				$service->setInputFile($this->absolutePath, $this->extension);
				$serviceConfiguration = array('wantedCharset' => 'utf-8');
				$service->process('', '', $serviceConfiguration);

				$fileContent = $service->getOutput();
			} else {
					// return an empty string
				$fileContent = '';
			}

			$this->content = tx_solr_HtmlContentExtractor::cleanContent($fileContent);
		}

		return $this->content;
	}

	/**
	 * Checks if textual content can be extracted from the file.
	 *
	 * @return	boolean	TRUE if the file can be used to extract text from it, FALSE otherwise
	 */
	public function canExtractText() {
		$canExtractText = FALSE;

		$subtypes = '';
		if ($GLOBALS['T3_SERVICES']['textExtract']) {
				// get the subtypes (allowed file extensions)
			foreach($GLOBALS['T3_SERVICES']['textExtract'] as $key => $info) {
				$subtypes .= $info['subtype'] . ',';
			}
			$subtypes = t3lib_div::trimExplode(',', $subtypes, TRUE);

			$canExtractText = in_array($this->extension, $subtypes);
		}

		return $canExtractText;
	}

	/**
	 * Determines the Internet Media Type, or MIME type.
	 *
	 * @return	string	The file's MIME type.
	 */
	public function getMimeType() {
		$mimeType = '';

		if (function_exists('finfo_file')) {
			$fileInfo = new finfo(FILEINFO_MIME);
			if ($fileInfo) {
				$mimeTypeAndCharset = $fileInfo->file($this->absolutePath);
				$mimeType = array_shift(t3lib_div::trimExplode(';', $mimeTypeAndCharset, 1));
			}
		} else {
			$mimeType = mime_content_type($this->absolutePath);
		}

		return $mimeType;
	}

	/**
	 * Sets the referencing object for this file.
	 *
	 * @param string $type The type of object referencing this file, usually a TYPO3 table lie tt_content
	 * @param integer $pageId The referencing object's page ID, might be 0 for non-TYPO3 references.
	 * @param integer $uid The referencing object's unique ID, might be 0 for non-TYPO3 references.
	 * @param integer $language The referencing object's language ID
	 */
	public function setReference($type, $pageId, $uid, $language = 0) {
		$this->setReferenceType($type);
		$this->setReferencePageId($pageId);
		$this->setReferenceUniqueId($uid);
		$this->setReferenceLanguage($language);
	}

	/**
	 * Sets the referencing page's Solr document ID.
	 *
	 * @param	string	Solr document ID
	 */
	public function setReferencePageDocumentId($referencePageDocumentId) {
		$this->referencePageDocumentId = $referencePageDocumentId;
	}

	/**
	 * Gets the referencing page's Solr document ID.
	 *
	 * @return	string	Solr document ID
	 */
	public function getReferencePageDocumentId() {
		return $this->referencePageDocumentId;
	}

	/**
	 * Gets the referencing object for this file.
	 *
	 * @return	array	An array with keys type, pid, and uid.
	 */
	public function getReference() {
		return array(
			'type'     => $this->referenceType,
			'pid'      => $this->referencePageId,
			'uid'      => $this->referenceUniqueId,
			'language' => $this->referenceLanguage
		);
	}

	/**
	 * Sets the reference type.
	 *
	 * Typically the referencing object's table name.
	 *
	 * @param	string	$referenceType	The referencing object's type.
	 */
	public function setReferenceType($referenceType) {
		$this->referenceType = $referenceType;
	}

	/**
	 * Gets the referencing object's type.
	 *
	 * @return	string	The file referrencing's object's type.
	 */
	public function getReferenceType() {
		return $this->referenceType;
	}

	/**
	 * Sets the referencing object's page ID (pid).
	 *
	 * @param	integer	$referencePageId The referencing object's page ID (pid)
	 */
	public function setReferencePageId($referencePageId) {
		$this->referencePageId = intval($referencePageId);
	}

	/**
	 * Gets the page id of the referring object.
	 *
	 * Distinguishes between pages and other objects. If a page is referring to
	 * this file, the page's uid is returned, otherwise returning the object's
	 * page id.
	 *
	 * @return	integer	The referring object's page id.
	 */
	public function getReferencePageId() {
		$pageId = $this->referencePageId;

		if ($this->referenceType == 'pages') {
			$pageId = $this->referenceUniqueId;
		}

		return $pageId;
	}

	/**
	 * Sets the referencing object's root page ID (pid).
	 *
	 * @param	integer	$referencePageId The referencing object's root page ID (pid)
	 */
	public function setReferenceRootPageId($referenceRootPageId) {
		$this->referenceRootPageId = intval($referenceRootPageId);
	}


	/**
	 * Gets the root page id of the referring object.
	 *
	 * @return	integer	The referring object's root page id.
	 */
	public function getReferenceRootPageId() {
		return $this->referenceRootPageId;
	}

	/**
	 * Sets the referencing object's language (sys_language_uid).
	 *
	 * @param	integer	$referenceLanguage The referencing object's language (sys_language_uid)
	 */
	public function setReferenceLanguage($referenceLanguage) {
		$this->referenceLanguage = intval($referenceLanguage);
	}

	/**
	 * Gets the referencing object's language ID.
	 *
	 * @return	integer	The referencing object's language ID.
	 */
	public function getReferenceLanguage() {
		return $this->referenceLanguage;
	}

	/**
	 * Sets the referencing object's unique ID (uid).
	 *
	 * @param	integer	$referenceUniqueId The referencing object's unique ID (uid)
	 */
	public function setReferenceUniqueId($referenceUniqueId) {
		$this->referenceUniqueId = intval($referenceUniqueId);
	}

	/**
	 * Get's the referencing object's unique Id (uid).
	 *
	 * @return	integer	The referencing object's uid.
	 */
	public function getReferenceUniqueId() {
		return $this->referenceUniqueId;
	}

	/**
	 * Gets the site of this file.
	 *
	 * @return	tx_solr_Site	The file's site.
	 */
	public function getSite() {
		if ($this->site == NULL) {
			$this->site = t3lib_div::makeInstance('tx_solr_Site', $this->getReferenceRootPageId());
		}

		return $this->site;
	}

	/**
	 * Sets the frontend user group access restrictions for this file.
	 *
	 * @param	string	$access Access rootline
	 */
	public function setAccess($access) {
		$this->access = $access;
	}

	/**
	 * Gets the frontend user group access restrictions for this file.
	 *
	 * @return	string	Access rootline
	 */
	public function getAccess() {
		return $this->access;
	}

	/**
	 * Gets the file's extension.
	 *
	 * @return	string	File extension
	 */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 * Sets the referencing page's Solr document.
	 *
	 * @param	Apache_Solr_Document	The referencing page's Solr document
	 * @return	void
	 */
	public function setReferencePageDocument(Apache_Solr_Document $referencePageDocument){
		$this->referencePageDocument = $referencePageDocument;
	}

	/**
	 * Gets the referencing page's Solr document.
	 *
	 * @return	Apache_Solr_Document	The referencing page's Solr document
	 */
	public function getReferencePageDocument() {
		return $this->referencePageDocument;
	}

	/**
	 * Gets the file's File Index Queue ID.
	 *
	 * @return	integer	File Index Queue ID.
	 */
	public function getFileIndexQueueId() {
		return $this->fileIndexQueueId;
	}

	/**
	 * Sets the file's File Index Queue ID.
	 *
	 * @param	integer	File Index Queue ID.
	 */
	public function setFileIndexQueueId($fileIndexQueueId) {
		$this->fileIndexQueueId = intval($fileIndexQueueId);
	}

	/**
	 * Sets the file object's last changed time.
	 *
	 * @param	integer	$lastChanged Unix timestamp
	 */
	public function setLastChanged($lastChanged) {
		$this->lastChanged = intval($lastChanged);
	}

	/**
	 * Gets the file object's last changed time.
	 *
	 * @return	integer	Unix timestamp
	 */
	public function getLastChanged() {
		return $this->lastChanged;
	}

	/**
	 * Sets the file's additional fields to take into account when indexing the
	 * file to Solr.
	 *
	 * @param array $additionalFields A map of Solr field names and their values
	 */
	public function setAdditionalFields(array $additionalFields) {
		$this->additionalFields = $additionalFields;
	}

	/**
	 * Gets the file's additional Solr fields.
	 *
	 * @return array A map of Solr field names and their values
	 */
	public function getAdditionalFields() {
		return $this->additionalFields;
	}


	/**
	 * Sets the timestamp of when an item has been indexed.
	 *
	 * @return	void
	 */
	public function updateIndexedTime() {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_solr_indexqueue_file',
			'uid = ' . (int) $this->fileIndexQueueId,
			array('indexed' => time())
		);
	}

	/**
	 * Override to show some informations in the log.
	 * TODO: Only show what is needed.
	 *
	 * @return	string Information about this object.
	 */
	function __toString() {
		return print_r($this, TRUE);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_file.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_file.php']);
}

?>