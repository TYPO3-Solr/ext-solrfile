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
 * Factory for attachment detectors - to find files attached to records.
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_fileindexer_AttachmentDetectorFactory implements t3lib_Singleton {

	/**
	 * Registration information of attachment detectors.
	 *
	 * @var array
	 */
	protected static $attachmentDetectors = array();


	/**
	 * Finds attachment detectors by checking Index Queue item information.
	 *
	 * @todo Split up into separate methods
	 * @param tx_solr_indexqueue_Item $item Index Queue item to find appropriate attachment detectors for
	 * @return array An array of attachment detectors matching the TCA field configuration requirements
	 */
	public function getAttachmentDetectorsForItem(tx_solr_indexqueue_Item $item) {
		$attachmentDetectors = array();

		$site = $item->getSite();
		$solrConfiguration = $site->getSolrConfiguration();
		$itemTypeIndexQueueConfiguration = $solrConfiguration['index.']['queue.'][$item->getIndexingConfigurationName() . '.'];

		$attachmentFields = array();
		if (isset($itemTypeIndexQueueConfiguration['attachments.'])
		&& !empty($itemTypeIndexQueueConfiguration['attachments.']['fields'])) {
			$attachmentFieldsConfiguration = $itemTypeIndexQueueConfiguration['attachments.']['fields'];
			$attachmentFields = t3lib_div::trimExplode(',', $attachmentFieldsConfiguration);
		}

		if (!empty($attachmentFields)) {
			$typeColumnsTca = $GLOBALS['TCA'][$item->getType()]['columns'];

			$fieldTypeMatchingAttachmentDetectors = array();
			foreach ($attachmentFields as $attachmentField) {
				$tcaFieldConfiguration = $typeColumnsTca[$attachmentField]['config'];

				if (!empty($tcaFieldConfiguration)) {
					$fieldTypeMatchingAttachmentDetectors = array_merge(
						$fieldTypeMatchingAttachmentDetectors,
						$this->getAttachmentDetectorsByTcaFieldConfiguration($tcaFieldConfiguration)
					);
				}
			}

			$extensionRequirementsMatchingAttachmentDetectors = array();
			foreach ($fieldTypeMatchingAttachmentDetectors as $attachmentDetectorClassName => $attachmentDetectorRegistration) {
				if ($this->requiredExtensionsLoaded($attachmentDetectorRegistration['requiredExtensions'])) {
					$extensionRequirementsMatchingAttachmentDetectors[$attachmentDetectorClassName] = $attachmentDetectorRegistration;
				}
			}

			foreach ($extensionRequirementsMatchingAttachmentDetectors as $attachmentDetectorRegistration) {
				$attachmentDetector = t3lib_div::makeInstance($attachmentDetectorRegistration['className'],
					$item,
					$attachmentFields
				);

				if (!($attachmentDetector instanceof tx_solr_AttachmentDetector)) {
					throw new UnexpectedValueException(
						$attachmentDetectorRegistration['className'] . ' is not an implementation of tx_solr_AttachmentDetector',
						'1326823681'
					);
				}

				$attachmentDetectors[] = $attachmentDetector;
			}
		}

		return $attachmentDetectors;
	}

	/**
	 * Finds attachment detectors by comparing available attachment detector
	 * registration information with information from TCA.
	 *
	 * @param array $tcaFieldConfiguration TCA field configuration
	 * @return array An array of attachment detectors matching the TCA field configuration requirements
	 */
	protected function getAttachmentDetectorsByTcaFieldConfiguration(array $tcaFieldConfiguration) {
		$fieldTypeMatchingAttachmentDetectors = array();

		$fieldType = $tcaFieldConfiguration['type'];
		if ($fieldType == 'group') {
			$fieldType .= ':' . $tcaFieldConfiguration['internal_type'];
		}

		foreach (self::$attachmentDetectors as $attachmentDetectorClassName => $attachmentDetectorRegistration) {
			if (in_array($fieldType, $attachmentDetectorRegistration['fieldTypes'])) {
				$fieldTypeMatchingAttachmentDetectors[$attachmentDetectorClassName] = $attachmentDetectorRegistration;
			}
		}

		return $fieldTypeMatchingAttachmentDetectors;
	}

	/**
	 * Checks whether all extensions required for a file detector to work are
	 * installed and loaded.
	 *
	 * @param	string	$requiredExtensions comma separated list of extension keys
	 */
	protected function requiredExtensionsLoaded($requiredExtensions) {
		$requiredExtensionsLoaded = TRUE;
		$extensionKeys = t3lib_div::trimExplode(',', $requiredExtensions, TRUE);

		if (!empty($extensionKeys)) {
			foreach ($extensionKeys as $extensionKey) {
				if (!t3lib_extMgm::isLoaded($extensionKey)) {
					$requiredExtensionsLoaded = FALSE;
					break;
				}
			}
		}

		return $requiredExtensionsLoaded;
	}

	/**
	 * Gets all registered attachment detectors.
	 *
	 * @return array An array of attachment detector registrations.
	 */
	public static function getRegisteredAttachmentDetectors() {
		return self::$attachmentDetectors;
	}

	/**
	 * Registers an attachement detector for a specific TCA field type.
	 *
	 * When indexing a record with file attachments using one of the field
	 * types, the given attachment detector will be used to search for files
	 * in these fields.
	 *
	 * Attachment detectors have to implement tx_solr_AttachmentDetector interface.
	 *
	 * @param string $fileDetectorClassName The class to use for detecting attachments.
	 * @param array $fieldTypes An array of field types supported by the attachement detector
	 * @param string $requiredExtensions Comma-separated list of extensions that is required for this file detector
	 */
	public static function registerAttachmentDetector($attachmentDetectorClassName, array $fieldTypes, $requiredExtensions = '') {
		if (!array_key_exists($attachmentDetectorClassName, self::$attachmentDetectors)) {
			self::$attachmentDetectors[$attachmentDetectorClassName] = array(
				'className'          => $attachmentDetectorClassName,
				'fieldTypes'         => $fieldTypes,
				'requiredExtensions' => $requiredExtensions
			);
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_attachmentdetectorfactory.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_attachmentdetectorfactory.php']);
}

?>
