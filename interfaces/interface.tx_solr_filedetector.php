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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * File detector interface.
 *
 * Part of the strategy pattern used to separate the different algorythms to
 * detect file links on pages, depending on the way how files are managed in a
 * TYPO3 installation, DAM or classic fileadmin.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
interface tx_solr_FileDetector {

	/**
	 * Gets files used in a content element or on a page.
	 *
	 * @return	array	An array of tx_solr_fileindexer_File objects.
	 */
	public function getFiles();

	/**
	 * Provides a list (array) of content element types. I.e., the types used
	 * in table tt_content's CType column.
	 *
	 * @return	array	List of content element types the file detector knows about.
	 */
	public function getObservedContentElementTypes();

	/**
	 * Gets a list of extensions required to be installed to use the file
	 * detector.
	 *
	 * @return	array	List of extension keys required to be installed.
	 */
	public function getRequiredExtensions();
}

?>