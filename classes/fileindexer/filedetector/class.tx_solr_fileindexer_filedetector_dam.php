<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011-2012 Ingo Renner <ingo@typo3.org>
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
 * File detector to detect files on a page which are linked using the RTE and
 * DAM's media tag
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_fileindexer_filedetector_Dam extends tx_solr_fileindexer_filedetector_Abstract {

	/**
	 * List of observed content element types and observed fields for each type.
	 *
	 * @var	array
	 */
	protected $observedContentElementTypes = array(
		'text'    => 'bodytext',
		'textpic' => 'bodytext'
	);

	/**
	 * Finds the files used in a content element.
	 *
	 * @return	array	An array of files with path relative to the TYPO3 site root.
	 */
	protected function findFiles() {
		$files = array();

		$mediaTags = $this->getMediaTags();

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
	 * Finds media tags in tt_content's bodytext field.
	 *
	 * @todo move to typo3 content extractor
	 * @return	array	An array of <media> tags
	 */
	protected function getMediaTags() {
		$mediaTags = array();

			// Parse string for DAM <media> tag
		$htmlParser    = t3lib_div::makeInstance('t3lib_parsehtml');
		$parsedContent = $htmlParser->splitTags('media', $this->contentElementRecord['bodytext']);

		foreach ($parsedContent as $key => $value) {
			if ($key % 2) {
					// select every second element
				$mediaTags[] = $value;
			}
		}

		return $mediaTags;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_dam.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/classes/fileindexer/filedetector/class.tx_solr_fileindexer_filedetector_dam.php']);
}

?>