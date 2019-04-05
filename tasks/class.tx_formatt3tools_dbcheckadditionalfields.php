<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011  Andreas Kessel <typo3-dev@formatsoft.de>
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

if (version_compare(TYPO3_branch, '6.2', '>')) {
} else {
}

/**
 * Additional field to set the notification email address(es)
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @author	Andreas Kessel <typo3-dev@formatsoft.de>
 * @package	TYPO3
 * @subpackage  format_t3tools
 */
class tx_formatt3tools_dbcheckadditionalfields implements TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {


	/**
	 * Additional fields
	 *
	 * @var	array
	 */
	protected $fields = array('notificationEmail',
                            'maxDbSize');

	/**
	 * Field prefix.
	 *
	 * @var	string
	 */
	protected $fieldPrefix = 'dbcheck';

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param	array	$taskInfo Values of the fields from the add/edit task form
	 * @param	tx_scheduler_Task	$task The task object being eddited. Null when adding a task!
	 * @param	tx_scheduler_Module	$schedulerModule Reference to the scheduler backend module
	 * @return	array	A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$fields = $this->fields;

		if ($schedulerModule->CMD == 'edit') {
			$taskInfo[$this->fieldPrefix . 'NotificationEmail'] = $task->getNotificationEmail();
			$taskInfo[$this->fieldPrefix . 'MaxDbSize']         = $task->getMaxDbSize();
		}

		$additionalFields = array();
		foreach ($fields as $field) {
			$fieldName = $this->getFullFieldName($field);
			$fieldId   = 'task_' . $fieldName;
			$fieldHtml = '<input type="text" '
				. 'name="tx_scheduler[' . $fieldName . ']" '
				. 'id="' . $fieldId . '" '
				. 'value="' . htmlspecialchars($taskInfo[$fieldName]) . '" />';

			$additionalFields[$fieldId] = array(
				'code'     => $fieldHtml,
				'label'    => 'LLL:EXT:format_t3tools/tasks/locallang.xml:dbchecktask.field.' . $field,
				'cshKey'   => '',
				'cshLabel' => $fieldId
			);
		}
    //t3lib_div::debug($additionalFields);
    
		return $additionalFields;
	}

	
  
  
  /**
	 * Validates the additional fields' values
	 *
	 * @param	array	$submittedData An array containing the data submitted by the add/edit task form
	 * @param	tx_scheduler_Module	$schedulerModule Reference to the scheduler backend module
	 * @return	boolean	True if validation was ok (or selected class is not relevant), false otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		$validInput = TRUE;
		$submittedData[$this->fieldPrefix . 'NotificationEmail'] = trim($submittedData[$this->fieldPrefix . 'NotificationEmail']);
		$submittedData[$this->fieldPrefix . 'MaxDbSize']         = trim($submittedData[$this->fieldPrefix . 'MaxDbSize']);
		
		//t3lib_div::debug($submittedData);

		$arrAdr = explode(',', $submittedData[$this->fieldPrefix . 'NotificationEmail']);
		$err = false;
		foreach($arrAdr as $adr){
		  if(!filter_var($adr, FILTER_VALIDATE_EMAIL)) $err = true;
    }
    if (
			empty($submittedData[$this->fieldPrefix . 'NotificationEmail'])
			|| $err
		) {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:format_t3tools/tasks/locallang.xml:dbchecktask.field.notificationEmail.invalid'),
				t3lib_FlashMessage::ERROR
			);
			$validInput = FALSE;
		}

		
    $int_options = array("options"=>array("min_range"=>1, "max_range"=>99999));
    if (
			empty($submittedData[$this->fieldPrefix . 'MaxDbSize'])
			|| !filter_var($submittedData[$this->fieldPrefix . 'MaxDbSize'], FILTER_VALIDATE_INT, $int_options)
		) {
			$schedulerModule->addMessage(
				$GLOBALS['LANG']->sL('LLL:EXT:format_t3tools/tasks/locallang.xml:dbchecktask.field.maxDbSize.invalid'),
				t3lib_FlashMessage::ERROR
			);
			$validInput = FALSE;
		}


		return $validInput;
	}

	
  
  
  
  /**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param	array	$submittedData An array containing the data submitted by the add/edit task form
	 * @param	tx_scheduler_Task	$task Reference to the scheduler backend module
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {

		if (!($task instanceof tx_formatt3tools_dbcheck)) {
			throw new InvalidArgumentException(
				'Expected a task of type tx_formatt3tools_dbcheck, but got ' . get_class($task),
				1295012802
			);
		}

		$task->setNotificationEmail($submittedData[$this->fieldPrefix . 'NotificationEmail']);
		$task->setMaxDbSize($submittedData[$this->fieldPrefix . 'MaxDbSize']);
	}

	/**
	 * Constructs the full field name which can be used in HTML markup.
	 *
	 * @param	string	$fieldName A raw field name
	 * @return	string Field name ready to use in HTML markup
	 */
	protected function getFullFieldName($fieldName) {
		return $this->fieldPrefix . ucfirst($fieldName);
	}

}
