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
 * Attachment detector for fields using files from fileadmin.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_fileindexer_attachmentdetector_Fileadmin extends tx_solr_fileindexer_attachmentdetector_Abstract {

	/**
	 * (non-PHPdoc)
	 * @see tx_solr_fileindexer_attachmentdetector_Abstract::findFilesInField()
	 */
	protected function findFilesInField($fieldName) {
		$files     = array();
		$fieldType = $this->getFieldType($fieldName);

		switch ($fieldType) {
			case 'group:file':
				$files = $this->findFilesInGroupFileField($fieldName);
				break;
			case 'group:file_reference':
					// not implemented yet
				break;
			case 'group:folder':
					// not implemented yet
				break;
			case 'text':
				$files = $this->findFilesInTextField($fieldName);
				break;
		}

		return $files;
	}

	/**
	 * Finds files from field of type "group" and internal type "file".
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInGroupFileField($fieldName) {
		$files = array();

		$record = $this->indexQueueItem->getRecord();

		if (!empty($record[$fieldName])) {
			$uploadsFolder = $GLOBALS['TCA'][$this->indexQueueItem->getType()]['columns'][$fieldName]['config']['uploadfolder'];
			$filesInField  = t3lib_div::trimExplode(',', $record[$fieldName]);

			foreach ($filesInField as $file) {
				$files[] = $uploadsFolder . '/' . $file;
			}
		}

		return $files;
	}

	/**
	 * Finds files from the text field.
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInTextField($fieldName) {
		$files              = array();
		$fileTrimCharacters = ' <>';

		$record   = $this->indexQueueItem->getRecord();
		$linkTags = $this->getTypoLinkTags($record[$fieldName]);

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
	 * Finds typolink tags in a content string.
	 *
	 * @todo move to typo3 content extractor
	 * @param string HTML content
	 * @return array An array of <link> tags
	 */
	protected function getTypoLinkTags($content) {
		$typolinkTags = array();

			// Parse string for TYPO3 <link> tag
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$linkTags   = $htmlParser->splitTags('link', $content);

		foreach ($linkTags as $key => $value) {
			if ($key % 2) {
					// select every second element
				$typolinkTags[] = $value;
			}
		}

		return $typolinkTags;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_fileadmin.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_fileadmin.php']);
}

?>