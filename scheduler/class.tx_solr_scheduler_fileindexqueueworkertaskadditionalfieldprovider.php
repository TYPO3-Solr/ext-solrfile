<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Markus Goldbach <markus.goldbach@dkd.de>
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
 * Additional field provider for the file index queue worker task
 *
 * @author	Markus Goldbach <markus.goldbach@dkd.de>
 * @package	TYPO3
 * @subpackage	solr
 */
class tx_solr_scheduler_FileIndexQueueWorkerTaskAdditionalFieldProvider implements tx_scheduler_AdditionalFieldProvider {

	/**
	 * Used to define fields to provide the Solr server address and processing
	 * limit when adding or editing a task.
	 *
	 * @param	array	$taskInfo reference to the array containing the info used in the add/edit form
	 * @param	tx_scheduler_Task	$task when editing, reference to the current task object. Null when adding.
	 * @param	tx_scheduler_Module	$schedulerModule reference to the calling object (Scheduler's BE module)
	 * @return	array	Array containg all the information pertaining to the additional fields.
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule) {
		$additionalFields = array();

		if ($schedulerModule->CMD == 'add') {
			$taskInfo['filesToIndexLimit'] = 10;
		}

		if ($schedulerModule->CMD == 'edit') {
			$taskInfo['filesToIndexLimit'] = $task->getFilesToIndexLimit();
		}

		$additionalFields['filesToIndexLimit'] = array(
			'code'     => '<input type="text" name="tx_scheduler[filesToIndexLimit]" value="' . $taskInfo['filesToIndexLimit'] . '" />',
			'label'    => 'LLL:EXT:solr/lang/locallang.xml:scheduler_fileindexqueueworker_field_filesToIndexLimit',
			'cshKey'   => '',
			'cshLabel' => ''
		);

		return $additionalFields;
	}

	/**
	 * Checks any additional data that is relevant to this task. If the task
	 * class is not relevant, the method is expected to return true
	 *
	 * @param	array	$submittedData reference to the array containing the data submitted by the user
	 * @param	tx_scheduler_module1	$parentObject reference to the calling object (Scheduler's BE module)
	 * @return	boolean	TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule) {

			// check limit
		$submittedData['filesToIndexLimit'] = intval($submittedData['filesToIndexLimit']);

		return TRUE;
	}

	/**
	 * Saves any additional input into the current task object if the task
	 * class matches.
	 *
	 * @param	array	$submittedData array containing the data submitted by the user
	 * @param	tx_scheduler_Task	$task reference to the current task object
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task) {
		$task->setFilesToIndexLimit($submittedData['filesToIndexLimit']);
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/scheduler/class.tx_solr_scheduler_fileindexqueueworkertaskadditionalfieldprovider.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/solr/scheduler/class.tx_solr_scheduler_fileindexqueueworkertaskadditionalfieldprovider.php']);
}

?>