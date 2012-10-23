<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2012 Ingo Renner <ingo@typo3.org>
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
 * Provides an status report about whether a connection to the Solr server can
 * be established.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_report_FileIndexerStatus implements tx_reports_StatusProvider {


	/**
	 * Checks whether the requirementsa for file indexing are met by the current
	 * web server.
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$reports  = array();
		$severity = tx_reports_reports_status_Status::OK;
		$value    = 'Using Fileinfo for file MIME type detection.';

		if (!extension_loaded('fileinfo')) {

				// values for the case of no Fileinfo, but having mime_content_type()
			$severity = tx_reports_reports_status_Status::NOTICE;
			$value    = 'Using mime_content_type() for file MIME type detection.';
			$message  = 'The file indexer is using mime_content_type() to
				detect the MIME type of files.<br />
				This function has been deprecated as the PECL extension
				<a href="http://www.php.net/manual/en/ref.fileinfo.php"
				target="_new">Fileinfo</a> provides the same functionality (and
				more) in a much cleaner way.';

			if (!function_exists('mime_content_type')) {
				$severity = tx_reports_reports_status_Status::ERROR;
				$value    = 'No method available to detect file MIME types.';
				$message  = 'Neither the PHP function mime_content_type() nor
					the PHP extension Fileinfo were found. Thus files can not be
					indexed.<br />
					For best performance it is recommended to install the PHP
					<a href="http://www.php.net/manual/en/ref.fileinfo.php"
					target="_new">Fileinfo</a> extension from PECL.';
			}
		}

		$reports[] = t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'File Indexer',
			$value,
			$message,
			$severity
		);

		return $reports;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/report/class.tx_solr_report_fileindexerstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/report/class.tx_solr_report_fileindexerstatus.php']);
}

?>