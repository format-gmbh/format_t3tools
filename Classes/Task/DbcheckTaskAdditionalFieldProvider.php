<?php
namespace Formatsoft\FormatT3tools\Task;
/**
 * This file is part of the "format_t3tools" Extension for TYPO3 CMS.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * This class provides Scheduler Additional Field plugin implementation
 *
 */
class DbcheckTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider {

    /**
     * Default language file of the extension
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf';

	/**
	 * Additional fields
	 *
	 * @var	array
	 */
	protected $fields = ['notificationEmail',
                         'maxDbSize'];

    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array $taskInfo Array information of task to return
     * @param DbcheckTask|null $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return array Additional fields
     * @see AdditionalFieldProviderInterface->getAdditionalFields($taskInfo, $task, $schedulerModule)
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule) {
        $additionalFields = [];
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();
        $lang = $this->getLanguageService();

        if (empty($taskInfo['notificationEmail'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                if (isset($taskInfo['dbcheck']) && isset($taskInfo['dbcheck']['notificationEmail'])) {
                    $taskInfo['notificationEmail'] = $taskInfo['dbcheck']['notificationEmail'];
                }
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['notificationEmail'] = $task->getNotificationEmail();
            } else {
                $taskInfo['notificationEmail'] = $task->getNotificationEmail();
            }
        }

        if (empty($taskInfo['maxDbSize'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                if (isset($taskInfo['dbcheck']) && isset($taskInfo['dbcheck']['maxDbSize'])) {
                    $taskInfo['maxDbSize'] = $taskInfo['dbcheck']['maxDbSize'];
                }
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['maxDbSize'] = $task->getMaxDbSize();
            } else {
                $taskInfo['maxDbSize'] = $task->getMaxDbSize();
            }
        }

		foreach ($this->fields as $field) {
            $fieldId = 'task_'.$field;
            $fieldValue = $taskInfo[$field] ?? '';
            $fieldCode = '<input class="form-control" type="text"  name="tx_scheduler[dbcheck]['.$field.']" '
            . 'id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars($fieldValue)
            . '">';
            $label = $lang->sL($this->languageFile . ':tasks.validate.'.$field);
            $additionalFields[$fieldId] = [
                'code' => $fieldCode,
                'label' => $label,
                'cshKey' => '',
                'cshLabel' => $fieldId,
            ];
        }

        return $additionalFields;
    }



    /**
     * This method checks any additional data that is relevant to the specific task.
     * If the task class is not relevant, the method is expected to return TRUE.
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule) {
        $isValid = true;
        $lang = $this->getLanguageService();
        if (!empty($submittedData['dbcheck']['notificationEmail'])) {
            if (strpos($submittedData['dbcheck']['notificationEmail'], ',') !== false) {
                $notificationEmailList = GeneralUtility::trimExplode(',', $submittedData['dbcheck']['notificationEmail']);
            } else {
                $notificationEmailList = GeneralUtility::trimExplode(LF, $submittedData['dbcheck']['notificationEmail']);
            }
            foreach ($notificationEmailList as $emailAdd) {
                if (!GeneralUtility::validEmail($emailAdd)) {
                    $isValid = false;
                    $this->addMessage(
                        $lang->sL($this->languageFile . ':tasks.validate.notificationEmail.invalid'),
                        ContextualFeedbackSeverity::ERROR
                    );
                }
            }
        }

        if ($submittedData['dbcheck']['maxDbSize'] <= 0) {
            $isValid = false;
            $this->addMessage(
                $lang->sL($this->languageFile . ':tasks.validate.maxDbSize.invalid'),
                ContextualFeedbackSeverity::ERROR
            );
        }
        return $isValid;
    }



    /**
     * This method is used to save any additional input into the current task object
     * if the task class matches.
     *
     * @param array $submittedData Array containing the data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task) {
        /** @var DbcheckTask $task */
        $task->setNotificationEmail($submittedData['dbcheck']['notificationEmail']);
        $task->setMaxDbSize($submittedData['dbcheck']['maxDbSize']);
    }


    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

}
