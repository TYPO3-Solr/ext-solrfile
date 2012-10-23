<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Markus Friedrich <markus.friedrich@dkd.de>
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
 * File detector to detect file links in TemplaVoila data structures
 *
 * @author Markus Friedrich <markus.friedrich@dkd.de
 * @package TYPO3
 * @subpackage solr
 */
class tx_solr_fileindexer_filedetector_Templavoila extends tx_solr_fileindexer_filedetector_Abstract {

	/**
	 * List of observed content element types and observed fields for each type.
	 *
	 * @var array
	 */
	protected $observedContentElementTypes = array(
		'templavoila_pi1' => 'tx_templavoila_flex'
	);

	/**
	 * List of extensions required to be installed for the file detector to work.
	 *
	 * @var array
	 */
	protected $requiredExtensions = array('templavoila');

	/**
	 * The configuration for the TemplaVoila file detector
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * The TemplaVoila data structure
	 *
	 * @var array
	 */
	protected $dataStructure;

	/**
	 * The field types defined in the data structure
	 *
	 * @var array
	 */
	protected $fieldTypes;


	/**
	 * Constructor for class tx_solr_fileindexer_filedetector_Templavoila
	 *
	 * @param array $contentElementRecord A tt_content record
	 * @return void
	 */
	public function __construct(array $contentElementRecord) {
		parent::__construct($contentElementRecord);

			// get configuration
		$configuration = tx_solr_Util::getSolrConfiguration();
		if (isset($configuration['index.']['files.']['detectors.']['templavoila.'])) {
			$this->configuration = $configuration['index.']['files.']['detectors.']['templavoila.'];
		}

			// initialize data structrue, if there is a configuration
		if (isset($this->configuration['datastructures.']) && isset($this->configuration['datastructures.'][$contentElementRecord['tx_templavoila_ds'] . '.'])) {
			$this->dataStructure = $this->getDatastructure($this->contentElementRecord['tx_templavoila_ds']);

			if (!is_null($this->dataStructure)) {
				$this->fieldTypes = $this->determineFieldTypes($this->dataStructure);
			}
		}
	}

	/**
	 * Finds the files used in a content element.
	 *
	 * @return array An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFiles() {
		$files = array();

		if (!is_null($this->dataStructure)) {
			$flexData = $this->getFlexData($this->dataStructure, $this->contentElementRecord['tx_templavoila_flex']);

			if (is_array($flexData)) {
				$fields = t3lib_div::trimExplode(
					',',
					$this->configuration['datastructures.'][$this->contentElementRecord['tx_templavoila_ds'] . '.']['fields']
				);

				foreach ($fields AS $field) {
					$files = $this->extractFiles($flexData, $field);
				}
			}
		}

		return $files;
	}

	/**
	 * Returns the datastructure
	 *
	 * @param string $dataStructureId Uid or filename of TemplaVoila data structure
	 * @return array The data structure
	 */
	protected function getDatastructure($dataStructureId) {
		$dataStructureRepository = t3lib_div::makeInstance('tx_templavoila_datastructureRepository');

		try {
			$dataStructure = $dataStructureRepository->getDatastructureByUidOrFilename($dataStructureId)->getDataprotArray();
			$dataStructure = $dataStructure['ROOT']['el'];
		} catch (InvalidArgumentException $e) {
			$dataStructure = NULL;

			t3lib_div::devLog(
				'TemplaVoila data structure not found',
				'solr',
				3,
				array(
					'content element record' => $this->contentElementRecord,
					'data structure id' => $dataStructureId
				)
			);
		}

		return $dataStructure;
	}

	/**
	 * Determines the field types from data structure
	 *
	 * @param array $dataStructure TemplaVoila data structure
	 * @param array $fieldTypes Already known field types
	 * @return array Array of field types found in the given data structure
	 */
	protected function determineFieldTypes($dataStructure, $fieldTypes = array()) {
			// go through the data structure and determine the field types
		foreach ($dataStructure as $field => $fieldData) {
			if (!isset($fieldTypes[$field])) {
				if ($fieldData['type'] == 'array') {
					if ($fieldData['section'] == 1) {
							// field mapping type is SC (Section of elements)
						$fieldTypes[$field] = 'section';
					} else {
							// field mapping type is CO (Container for elements)
						$fieldTypes[$field] = 'array';
					}
					$fieldTypes = $this->determineFieldTypes($fieldData['el'], $fieldTypes);
				} else {
						// field mapping type is EL (Element)
					$fieldTypes[$field] = 'field';
				}
			}
		}

		return $fieldTypes;
	}

	/**
	 * Returns the flexform data for current language
	 *
	 * @param array $dataStructure TemplaVoila data structure
	 * @param string $flexData The TemplaVoila flex data stored in tx_templavoila_flex
	 * @return array The flexform data
	 */
	protected function getFlexData(array $dataStructure, $flexData) {
		$data = NULL;

		$flexData = t3lib_div::xml2array($flexData);
		if (is_array($flexData)) {
			$sheetName   = 'sDEF';
			$languageKey = 'lDEF';

				// get sheet
			if ($dataStructure['meta']['sheetSelector']) {
					// <meta><sheetSelector> could be something like "EXT:user_extension/class.user_extension_selectsheet.php:&amp;user_extension_selectsheet"
				$sheetSelector = t3lib_div::getUserObj($dataStructure['meta']['sheetSelector']);
				$sheetName = $sheetSelector->selectSheet();
			}

				// get language
			if ($GLOBALS['TSFE']->sys_language_isocode && !$dataStructure['meta']['langDisable'] && !$dataStructure['meta']['langChildren']) {
				$languageKey = 'l' . $GLOBALS['TSFE']->sys_language_isocode;
			}

				// get required flex data part
			if (isset($flexData['data'][$sheetName][$languageKey])) {
				$data = $flexData['data'][$sheetName][$languageKey];
			}
		}

		return $data;
	}

	/**
	 * Extracts the files from the flexform data
	 *
	 * @param array $flexData Part of the TemplaVoila flex data stored in tx_templavoila_flex
	 * @param string $field Name of the field to extract data from
	 * @return array The files found in the flexform data
	 */
	protected function extractFiles($flexData, $field) {
		$files = array();

		foreach ($flexData as $fieldName => $fieldData) {
			switch ($this->fieldTypes[$fieldName]) {
				case 'section':
					foreach ($fieldData['el'] as $elKey => $elData) {
						$files = array_merge(
							$files,
							$this->extractFiles($elData, $field)
						);
					}

					break;
				case 'array':
					$files = array_merge(
						$files,
						$this->extractFiles($fieldData['el'], $field)
					);

					break;
				case 'field':
					if ($fieldName == $field) {
						$fieldDataParts = t3lib_div::trimExplode(' ', $fieldData['vDEF']);
						$files[] = rawurldecode($fieldDataParts[0]);
					}

					break;
			}
		}

		return $files;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_templavoila.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_templavoila.php']);
}

?>