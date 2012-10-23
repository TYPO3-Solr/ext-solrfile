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
 * Abstract Attachment Detector.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
abstract class tx_solr_fileindexer_attachmentdetector_Abstract implements tx_solr_AttachmentDetector {

	/**
	 * Index Queue item to detect attachments from.
	 *
	 * @var tx_solr_indexqueue_Item
	 */
	protected $indexQueueItem;

	/**
	 * Record fields containing attachments.
	 *
	 * @var array
	 */
	protected $attachmentFields = array();


	/**
	 * Constructor
	 *
	 * @param tx_solr_indexqueue_Item $item Index Queue item
	 */
	public function __construct(tx_solr_indexqueue_Item $item, array $attachmentFields) {
		$this->indexQueueItem   = $item;
		$this->attachmentFields = $attachmentFields;
	}

	/**
	 * Gets files used in a record.
	 *
	 * @return array An array of tx_solr_fileindexer_File objects.
	 */
	public function getFiles() {
		$files = array();

		$rawFiles = $this->findFiles();
		if (!empty($rawFiles)) {
			foreach ($rawFiles as $rawFile) {
				$files[] = t3lib_div::makeInstance(
					'tx_solr_fileindexer_File',
					$rawFile
				);
			}
		}

		return $files;
	}

	/**
	 * Finds the files used in a record.
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFiles() {
		$files = array();

		foreach ($this->attachmentFields as $attachmentFieldName) {
			$foundFiles = $this->findFilesInField($attachmentFieldName);
			$files = array_merge($files, $foundFiles);
		}

		return $files;
	}

	/**
	 * Gets a field's TCA type. The table is retrieved from th current
	 * Index Queue item.
	 *
	 * @param string $fieldName Field name
	 * @return string The field's TCA type.
	 */
	protected function getFieldType($fieldName) {
		$table = $this->indexQueueItem->getType();
		$type  = $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['type'];

		if ($type == 'group') {
			$type .= ':' . $GLOBALS['TCA'][$table]['columns'][$fieldName]['config']['internal_type'];
		}

		return $type;
	}

	/**
	 * Delegates finding files to different methods, depending on the TCA field
	 * type.
	 *
	 * @param string $fieldName Field name
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	abstract protected function findFilesInField($fieldName);

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_abstract.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/attachmentdetector/class.tx_solr_fileindexer_attachmentdetector_abstract.php']);
}

?>