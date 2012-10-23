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
 * Factory for file link detectors
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_fileindexer_FileDetectorFactory implements t3lib_Singleton {

	/**
	 * A mapping of extension keys to file detector classes to use for finding
	 * their files.
	 *
	 * @var	array
	 */
	protected static $extensionToDetectorMap = array();

	/**
	 * Constructor.
	 *
	 * Loads the TCA for table tt_content, which is needed to check which file
	 * detector is available for which content elements.
	 */
	public function __construct() {
		$GLOBALS['TSFE']->includeTCA();
	}

	/**
	 * Tries to find and instantiate file detectors for a given content
	 * element.
	 *
	 * @param	array	$contentRecord A tt_content record.
	 * @return	tx_solr_FileDetector	A file detector that can handle the given content element
	 * @throws	UnexpectedValueException	if a registered file detector does not implement the tx_solr_FileDetector interface
	 * @throws	RuntimeException	if no matching file detector for a content element was found
	 */
	public function getFileDetectorsForContentElement(array $contentRecord) {
		$fileDetectors         = array();
		$matchingFileDetectors = array();

		$contentElementType        = $contentRecord['CType'];
		$typeMatchingFileDetectors = $this->getRegisteredFileDetectorsByContentElementType($contentElementType);

		foreach ($typeMatchingFileDetectors as $fileDetectorDescription) {
			$contentElementTypeSupported = $this->fileDetectorFieldsMatchContentElementFields(
				$contentElementType,
				$fileDetectorDescription['contentElementTypes'][$contentElementType]
			);

			$recordHasAnalyzableField = $this->hasAnalyzableFieldInContentRecord(
				$contentRecord,
				$fileDetectorDescription['contentElementTypes'][$contentElementType]
			);

			$requiredExtensionsLoaded = $this->requiredExtensionsLoaded(
				$fileDetectorDescription['requiredExtensions']
			);

			if ($contentElementTypeSupported && $recordHasAnalyzableField && $requiredExtensionsLoaded) {
				$matchingFileDetectors[] = $fileDetectorDescription;
			}
		}

		foreach ($matchingFileDetectors as $matchingFileDetector) {
			$fileDetector = t3lib_div::makeInstance($matchingFileDetector['className'], $contentRecord);

			if (!($fileDetector instanceof tx_solr_FileDetector)) {
				throw new UnexpectedValueException(
					$matchingFileDetector['class'] . ' is not an implementation of tx_solr_FileDetector',
					'1291234453'
				);
			}

			$fileDetectors[] = $fileDetector;
		}

		return $fileDetectors;
	}

	/**
	 * Gets the file detectors registered for a certain content element type.
	 *
	 * @param	string	$contentElementType The content element type.
	 * @return	array	An array of file detector registrations.
	 */
	public function getRegisteredFileDetectorsByContentElementType($contentElementType) {
		$matchingFileDetectors = array();

		foreach (self::$extensionToDetectorMap as $registrationInformation) {
			if (array_key_exists($contentElementType, $registrationInformation['contentElementTypes'])) {
				$matchingFileDetectors[] = $registrationInformation;
			}
		}

		return $matchingFileDetectors;
	}

	/**
	 * Gets all registered file detectors.
	 *
	 * @return	array	An array of file detector registrations.
	 */
	public static function getRegisteredFileDetectors() {
		return self::$extensionToDetectorMap;
	}

	/**
	 * Checks whether a file detector can detect files from a content element.
	 *
	 * When registering a file detector, it defines fields it is going to look
	 * at to detect files. This method checks whether a content element type has
	 * all the defined fields the file detector needs to look at.
	 *
	 * @param	string	$contentElementType The content element type
	 * @param	string	$fileDetectorCheckedFields Comma separated list of fields to check
	 */
	protected function fileDetectorFieldsMatchContentElementFields($contentElementType, $fileDetectorCheckedFields) {
		$contentElementFields      = $this->getFieldsByContentElementType($contentElementType);
		$fileDetectorCheckedFields = t3lib_div::trimExplode(',', $fileDetectorCheckedFields);

		$missingFields = array_diff($fileDetectorCheckedFields, $contentElementFields);

			// $missingFields must not contain any fields to make the file detector work
		return !((boolean) count($missingFields));
	}

	/**
	 * Checks whether at least one of the fields required for file detection
	 * have values.
	 *
	 * @param	array	$contentElement The content element record
	 * @param	string	$analyzedFields Fields required for file detection
	 */
	protected function hasAnalyzableFieldInContentRecord(array $contentElement, $analyzedFields) {
		$analyzedFields     = t3lib_div::trimExplode(',', $analyzedFields);
		$hasAnalyzableField = FALSE;

		foreach ($analyzedFields as $analyzedField) {
				// not using empty() as 0 would be considered empty, too
			if (!($contentElement[$analyzedField] === '')) {
				$hasAnalyzableField = TRUE;
				break;
			}
		}

		return $hasAnalyzableField;
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
	 * Determines which fields a content element actually uses.
	 *
	 * A lot of fields are defined in TCA for the tt_content table. Each content
	 * element though uses only a few of them.
	 *
	 * @param	string	$contentElementType The content element type to get the fields for
	 */
	protected function getFieldsByContentElementType($contentElementType) {
		$resolvedPaletteContentElementFields = array();
		$rawContentElementFields             = explode(
			',',
			$GLOBALS['TCA']['tt_content']['types'][$contentElementType]['showitem']
		);

			// resolve palettes
		foreach ($rawContentElementFields as $contentElementField) {
			$fieldElements = explode(';', trim($contentElementField));

			if ($fieldElements[0] == '--div--') {
				continue;
			}

			if ($fieldElements[0] == '--palette--') {
				$palette = $GLOBALS['TCA']['tt_content']['palettes'][$fieldElements[2]]['showitem'];
				$resolvedPaletteContentElementFields[] = $palette;
				continue;
			}

				// regular field
			$resolvedPaletteContentElementFields[] = $contentElementField;
		}

			// some clean up
		$resolvedPaletteContentElementFields = implode(',', $resolvedPaletteContentElementFields);
		$resolvedPaletteContentElementFields = explode(',', $resolvedPaletteContentElementFields);

			// extract field names
		foreach ($resolvedPaletteContentElementFields as $key => $contentElementField) {
			$contentElementField = trim($contentElementField);

			if ($contentElementField == '--linebreak--') {
				unset($resolvedPaletteContentElementFields[$key]);
				continue;
			}

			$fieldElements = explode(';', trim($contentElementField));
			$resolvedPaletteContentElementFields[$key] = $fieldElements[0];
		}

		return $resolvedPaletteContentElementFields;
	}

	/**
	 * Registers a file detector with various information about when it can be
	 * used.
	 *
	 * @param	string	$fileDetectorClassName The class to use for detecting files.
	 * @param	array	$contentElementTypes An array of content element types as key and a comma-separated list of their fields the file detector can handle.
	 * @param	string	$requiredExtensions List of extensions required to be installed.
	 */
	public static function registerFileDetector($fileDetectorClassName, array $contentElementTypes, $requiredExtensions = '') {
		if (!array_key_exists($fileDetectorClassName, self::$extensionToDetectorMap)) {
			self::$extensionToDetectorMap[$fileDetectorClassName] = array(
				'className'           => $fileDetectorClassName,
				'contentElementTypes' => $contentElementTypes,
				'requiredExtensions'  => $requiredExtensions
			);
		}
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_filedetectorfactory.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/class.tx_solr_fileindexer_filedetectorfactory.php']);
}

?>