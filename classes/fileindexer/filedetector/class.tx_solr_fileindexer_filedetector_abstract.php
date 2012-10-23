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
 * Abstract file detector implementing common methods.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
abstract class tx_solr_fileindexer_filedetector_Abstract implements tx_solr_FileDetector {

	/**
	 * List of observed content element types and observed fields for each type.
	 *
	 * A map of content element type => comma separated list of fields analyzed
	 * for that type.
	 *
	 * array(
	 *     'uploads' => 'media, otherField1, otherField2',
	 *     'otherContentType' => 'listOfFields'
	 * )
	 *
	 * @var	array
	 */
	protected $observedContentElementTypes = array();

	/**
	 * List of extensions required to be installed for a file detector to work.
	 *
	 * @var	array
	 */
	protected $requiredExtensions = array();

	/**
	 * Content element record to detect files from.
	 *
	 * @var	array
	 */
	protected $contentElementRecord;


	/**
	 * Constructor.
	 *
	 * @param	array	$contentElementRecord A tt_content record
	 */
	public function __construct(array $contentElementRecord) {
		$this->contentElementRecord = $contentElementRecord;
	}

	/**
	 * Provides a list (array) of content element types. I.e., the types used
	 * in table tt_content's CType column.
	 *
	 * @return	array	List of content element types the file detector knows about.
	 */
	public function getObservedContentElementTypes() {
		return $this->observedContentElementTypes;
	}

	/**
	 * Gets a list of extensions required to be installed to use the file
	 * detector.
	 *
	 * @return	array	List of extension keys required to be installed.
	 */
	public function getRequiredExtensions() {
		return $this->requiredExtensions;
	}

	/**
	 * Gets files used in a content element or on a page.
	 *
	 * @return	array	An array of tx_solr_fileindexer_File objects.
	 */
	public function getFiles() {
		$files    = array();
		$rawFiles = $this->findFiles();

		if (!empty($rawFiles)) {
			foreach ($rawFiles as $rawFile) {
				$file = t3lib_div::makeInstance('tx_solr_fileindexer_File', $rawFile);
				$file->setReference(
					'tt_content',
					$this->contentElementRecord['pid'],
					$this->contentElementRecord['uid'],
					$GLOBALS['TSFE']->sys_language_content
				);
				$file->setReferenceRootPageId(tx_solr_Util::getRootPageId(
					$this->contentElementRecord['pid']
				));
				$file->setLastChanged(time()); // TODO fix naming, it's the index time actually

				$accessGroups = '0';
				if (!empty($this->contentElementRecord['fe_group'])) {
					$accessGroups = $this->contentElementRecord['fe_group'];
				}
				$file->setAccess('c:' . $accessGroups);

				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Finds the files used in a content element.
	 *
	 * @todo move into tx_solr_FileDetector interface
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	abstract protected function findFiles();
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_abstract.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_abstract.php']);
}

?>