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
 * File detector to detect files on a page which are embeded using the regular
 * fileadmin and downloads content element.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_fileindexer_filedetector_Fileadmin extends tx_solr_fileindexer_filedetector_Abstract {

	/**
	 * List of observed content element types and observed fields for each type.
	 *
	 * @var array
	 */
	protected $observedContentElementTypes = array(
		'uploads' => 'media,select_key', // selected files, complete folder
		'text'    => 'bodytext,header_link',
		'textpic' => 'bodytext,header_link',
		'table'   => 'bodytext,header_link'
	);

	/**
	 * tt_content media field uploads folder as definied in TCA.
	 *
	 * @var	string
	 */
	protected $mediaFieldUploadsFolder = '';

	/**
	 * Constructor for class tx_solr_fileindexer_filedetector_Fileadmin
	 *
	 * @param	array	$contentElementRecord A tt_content record
	 */
	public function __construct(array $contentElementRecord) {
		parent::__construct($contentElementRecord);

		$this->mediaFieldUploadsFolder = $GLOBALS['TCA']['tt_content']['columns']['media']['config']['uploadfolder'];
	}

	/**
	 * Finds the files used in a content element.
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFiles() {
		$files = array();

		switch ($this->contentElementRecord['CType']) {
			case 'uploads':
				$filesFromPath = $this->findFilesInSelectKeyField();
				$filesSelected = $this->findFilesInMediaField();

				$files = array_merge($files, $filesFromPath, $filesSelected);
				break;
			case 'text':
			case 'textpic':
			case 'table':
				$bodytextFiles   = $this->findFilesInBodytextField();
				$headerLinkFiles = $this->findFilesInHeaderLinkField();

				$files = array_merge($bodytextFiles, $headerLinkFiles);
				break;
		}

		return $files;
	}

	/**
	 * Finds files from the select_key field which points to a folder and
	 * results in listing all the files in it.
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInSelectKeyField() {
		$files = array();

		if (!empty($this->contentElementRecord['select_key'])) {
			$typo3DocumentRoot = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT');
			if (substr($typo3DocumentRoot, -1) != '/') {
				$typo3DocumentRoot .= '/';
			}

			$fileListPath = $this->contentElementRecord['select_key'];
			// select_key can have additional configuration parameters, example:
			// fileadmin/path/ | | name |
			$parts = explode('|', $fileListPath);
			$fileListPath = trim($parts[0]);

			if (substr($fileListPath, -1) != '/') {
				$fileListPath .= '/';
			}

			$filesInDirectory = t3lib_div::getFilesInDir($typo3DocumentRoot . $fileListPath);

			foreach ($filesInDirectory as $file) {
				$files[] = $fileListPath . $file;
			}
		}

		return $files;
	}

	/**
	 * Finds files from the media field.
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInMediaField() {
		$files = array();

		if (!empty($this->contentElementRecord['media'])) {
			$filesInMediaField = t3lib_div::trimExplode(',', $this->contentElementRecord['media']);
			foreach ($filesInMediaField as $file) {
				$files[] = $this->mediaFieldUploadsFolder . '/' . $file;
			}
		}

		return $files;
	}

	/**
	 * Finds files from the bodytext field.
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInBodytextField() {
		$files              = array();
		$fileTrimCharacters = ' <>';

		$linkTags = $this->getTypoLinkTags();

		foreach ($linkTags as $linkTag) {
			$linkParts  = explode(' ', $linkTag);
			$linkTarget = $linkParts[1];

			if (t3lib_div::isFirstPartOfStr($linkTarget, $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])
			|| t3lib_div::isFirstPartOfStr($linkTarget, 'uploads')) {
				$files[] = trim($linkTarget, $fileTrimCharacters);
			}
		}

		return $files;
	}

	/**
	 * Finds files from the header_link field.
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInHeaderLinkField() {
		$files = array();

		if (!empty($this->contentElementRecord['header_link'])) {
			$linkTarget = $this->contentElementRecord['header_link'];
			if (t3lib_div::isFirstPartOfStr($linkTarget, $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'])) {
					// remove typolink options
				$linkTargetOptions = explode(' ', $linkTarget);
				$files[] = rawurldecode($linkTargetOptions[0]);
			}
		}

		return $files;
	}

	/**
	 * Finds typolink tags in tt_content's bodytext field.
	 *
	 * @todo move to typo3 content extractor
	 * @return	array	An array of <link> tags
	 */
	protected function getTypoLinkTags() {
		$typolinkTags = array();

			// Parse string for TYPO3 <link> tag
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$linkTags   = $htmlParser->splitTags('link', $this->contentElementRecord['bodytext']);

		foreach ($linkTags as $key => $value) {
			if ($key % 2) {
					// select every second element
				$typolinkTags[] = $value;
			}
		}

		return $typolinkTags;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_fileadmin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_fileadmin.php']);
}

?>