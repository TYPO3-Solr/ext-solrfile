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
 * Attachment detector for fields using files from DAM.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_fileindexer_attachmentdetector_Dam extends tx_solr_fileindexer_attachmentdetector_Abstract {

	/**
	 * (non-PHPdoc)
	 * @see tx_solr_fileindexer_attachmentdetector_Abstract::findFilesInField()
	 */
	protected function findFilesInField($fieldName) {
		$files     = array();
		$fieldType = $this->getFieldType($fieldName);

		switch ($fieldType) {
			case 'group:db':
				$files = $this->findFilesInGroupDbField($fieldName);
				break;
			case 'text':
				$files = $this->findFilesInTextField($fieldName);
				break;
		}

		return $files;
	}

	/**
	 * Finds files from field of type "group" and internal type "db".
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInGroupDbField($fieldName) {
		$damFiles = tx_dam_db::getReferencedFiles(
			$this->indexQueueItem->getType(),
			$this->indexQueueItem->getRecordUid(),
			$fieldName
		);

		return $damFiles['files'];
	}

	/**
	 * Finds files from the text field.
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFilesInTextField($fieldName) {
		$files = array();

		$record    = $this->indexQueueItem->getRecord();
		$mediaTags = $this->getMediaTags($record[$fieldName]);

		foreach ($mediaTags as $mediaTag) {
			$mediaTagParts = explode(' ', $mediaTag);
			$mediaId = filter_var($mediaTagParts[1], FILTER_SANITIZE_NUMBER_INT);

			$metaData = tx_dam::meta_getDataByUid($mediaId);
			if (is_array($metaData)) {
				$files[] = $metaData['file_path'] . $metaData['file_name'];
			}
		}

		return $files;
	}

	/**
	 * Finds media tags in a text field.
	 *
	 * @todo move to typo3 content extractor
	 * @param string HTML content
	 * @return array An array of <media> tags
	 */
	protected function getMediaTags($content) {
		$mediaTags = array();

			// Parse string for DAM <media> tag
		$htmlParser    = t3lib_div::makeInstance('t3lib_parsehtml');
		$parsedContent = $htmlParser->splitTags('media', $content);

		foreach ($parsedContent as $key => $value) {
			if ($key % 2) {
					// select every second element
				$mediaTags[] = $value;
			}
		}

		return $mediaTags;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_dam.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_dam.php']);
}

?>