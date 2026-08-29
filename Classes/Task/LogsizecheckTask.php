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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
* Class tx_formatt3tools_dbcheck
*
* @author	Andreas Kessel <typo3-dev@formatsoft.de>
* @package  TYPO3
* @subpackage	tx_formatt3tools
*/
class LogsizecheckTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

    /**
     * Default language file of the extension
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:format_t3tools/Resources/Private/Language/locallang.xlf';

	/**
	 * Email address to send email notification to in case we find problems with
	 * the system.
	 *
	 * @var	string
	 */
	protected $notificationEmail = NULL;

    /**
     * Size of the database at which a mail is to be sent.
     *
     * @var int
     */
	protected $maxLogSize = 1;





	/**
	 * Function executed from scheduler. Sends a mail when the database size has been exceeded.
	 *
     * @return bool TRUE on successful execution, FALSE on error
	 */
	function execute() {

        $gesamt = 0;
        $dirname = Environment::getVarPath() . '/log';

        if (!is_dir($dirname)) {
            $this->sendNotificationEmail("The file $dirname does not exists", []);;
            return false;
        }

        $arrLogfiles = array_diff(scandir($dirname), ['..', '.']);
        $arrFileinfo = [];
        foreach ($arrLogfiles as $logfile) {
            $size = (int)filesize($dirname . '/' . $logfile);
            $gesamt += $size;
            $arrFileinfo[] = [
                'name' => $logfile,
                'size' => $size
            ];
        }

        $gesamtMByte = round($gesamt / (1024 * 1024),1);

        if($gesamtMByte > $this->getMaxLogSize()){
          $this->sendNotificationEmail($gesamtMByte.' MByte', $arrFileinfo);
        }

		return true;
	}



	/**
	 * Gets the notification email address.
	 *
	 * @return	string	Notification email address.
	 */
	public function getNotificationEmail(): ?string
    {
		return $this->notificationEmail;
	}


	/**
	 * Gets the maxLogSize.
	 *
	 * @return	int	$maxLogSize.
	 */
	public function getMaxLogSize(): int
    {
		return $this->maxLogSize;
	}





	/**
	 * Sets the notification email address.
	 *
	 * @param	string	$notificationEmail Notification email address.
	 */
	public function setNotificationEmail(string $notificationEmail): void {
		$this->notificationEmail = $notificationEmail;
	}


	/**
	 * Sets the maxLogSize.
	 *
	 * @param	int	$maxLogSize
	 */
	public function setMaxLogSize(int $maxLogSize): void {
		$this->maxLogSize = $maxLogSize;
	}





	/**
     * Sends a notification email, reporting size of log files in typo3temp/var/log
     *
     * @param string $groesse Gesamtgröße aller Log-Files
     * @param array $arrFileinfo Array mit Dateinamen und Größen
	 */
	/** @param array<array-key, mixed> $arrFileinfo */
	protected function sendNotificationEmail(string $groesse, array $arrFileinfo): void {

		$subject = sprintf(
            $this->getLanguageService()?->sL($this->languageFile . ':tasks.logsizecheck.email.subject') ?? '',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
		);
		$subject.= ': '.$groesse;

		$message = sprintf(
			$this->getLanguageService()?->sL($this->languageFile . ':tasks.email.message') ?? '',
			'',
			''
		);
		$message.= CRLF . CRLF;
		foreach ($arrFileinfo as $file) {
		    if($file['name'] !== '.htaccess') {
                $message.= substr($file['name'], 0 , 9) . '..... ';
                $message.= round($file['size'] / (1024 * 1024),1) . ' MB'. CRLF;
            }
        }
        $message.= CRLF . CRLF;

		$from =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];

        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        $mailerInterface = GeneralUtility::makeInstance(MailerInterface::class);

        $mail->setFrom($from)->setSubject($subject)->text($message);

		$arrAdr = GeneralUtility::trimExplode(',', $this->getNotificationEmail() ?? '', true);
		foreach($arrAdr as $adr){
            $mail->setTo($adr);
            $mailerInterface->send($mail);
        }
	}


    /**
     * Returns the most important properties of the task as a
     * slash separated string that will be displayed in the scheduler module.
     *
     * @return string
     */
    public function getAdditionalInformation() {
        $additionalInformation = [];

        $additionalInformation[] = 'TO: ' . $this->getNotificationEmail();
        $additionalInformation[] = 'Log files size: ' . $this->getMaxLogSize() . ' MB';

        return implode(' / ', $additionalInformation);
    }



    /** @return array<string, mixed> */
    public function getTaskParameters(): array
    {
        return [
            'tx_formatt3tools_notification_email' => $this->notificationEmail,
            'tx_formatt3tools_max_log_size' => $this->maxLogSize,
        ];
    }

    /**
     * The array keys are the TCA column names. The unprefixed keys are the ones used by
     * format_t3tools up to and including 14.0 and are kept as a fallback for tasks that
     * have not been migrated by the upgrade wizard yet.
     *
     * @param array<array-key, mixed> $parameters
     */
    public function setTaskParameters(array $parameters): void
    {
        $this->notificationEmail = (string)($parameters['tx_formatt3tools_notification_email'] ?? $parameters['notificationEmail'] ?? '');
        $this->maxLogSize = (int)($parameters['tx_formatt3tools_max_log_size'] ?? $parameters['maxLogSize'] ?? 1);
    }

    /** @param array<string, mixed> $parameters */
    public function validateTaskParameters(array $parameters): bool
    {
        $validInput = true;
        $notificationEmailList = (string)($parameters['tx_formatt3tools_notification_email'] ?? '');
        $notificationEmails = GeneralUtility::trimExplode(',', $notificationEmailList, true);
        foreach ($notificationEmails as $notificationEmail) {
            if (!GeneralUtility::validEmail($notificationEmail)) {
                $validInput = false;
                break;
            }
        }
        if (!$validInput || $notificationEmailList === '') {
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->addMessage(
                GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()?->sL($this->languageFile . ':tasks.validate.notificationEmail.invalid') ?? '', '', ContextualFeedbackSeverity::ERROR)
            );
            $validInput = false;
        }
        if ((int)($parameters['tx_formatt3tools_max_log_size'] ?? 0) <= 0) {
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->addMessage(
                GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()?->sL($this->languageFile . ':tasks.validate.maxLogSize.invalid') ?? '', '', ContextualFeedbackSeverity::ERROR)
            );
            $validInput = false;
        }
        return $validInput;
    }

    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

}

