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

use Doctrine\DBAL\Exception;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
* Class tx_formatt3tools_dbcheck
*
* @author	Andreas Kessel <typo3-dev@formatsoft.de>
* @package  TYPO3
* @subpackage	tx_formatt3tools
*/
class DbcheckTask extends AbstractTask {

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
	protected int $maxDbSize = 1;


    /**
     * Function executed from scheduler. Sends a mail when the database size has been exceeded.
     *
     * @return bool TRUE on successful execution, FALSE on error
     * @throws Exception
     */
	public function execute() {

        $gesamt = 0;
        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $result = $connection->executeQuery('SHOW TABLE STATUS');

        while ($row = $result->fetchAssociative()) {
            $summe = $row["Index_length"] + $row["Data_length"];
            $gesamt += $summe;
        }

        $gesamtMByte = round($gesamt / (1024 * 1024),1);

        if($gesamtMByte > $this->getMaxDbSize()){
          $this->sendNotificationEmail($gesamtMByte.' MByte');
        }

		return true;
	}



	/**
	 * Gets the notification email address.
	 *
	 * @return	string	Notification email address.
	 */
	public function getNotificationEmail() {
		return $this->notificationEmail;
	}


	/**
	 * Gets the maxDbSize.
	 *
	 * @return	int	maxDbSize.
	 */
	public function getMaxDbSize(): int
    {
		return $this->maxDbSize;
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
	 * Sets the maxDbSize.
	 *
	 * @param	int	$maxDbSize
	 */
	public function setMaxDbSize(int $maxDbSize): void {
		$this->maxDbSize = $maxDbSize;
	}


    /**
     * Sends a notification email, reporting size of database
     *
     * @param string $groesse
     * @throws TransportExceptionInterface
     */
	protected function sendNotificationEmail(string $groesse): void {

		$subject = sprintf(
            $this->getLanguageService()?->sL($this->languageFile . ':tasks.email.subject') ?? '',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
		);
		$subject.= ': '.$groesse;

		$message = sprintf(
			$this->getLanguageService()?->sL($this->languageFile . ':tasks.email.message') ?? '',
			'',
			''
		);
		$message.= CRLF . CRLF;

		$from =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];

        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);

        /** @var MailerInterface $mailerInterface */
        $mailerInterface = GeneralUtility::makeInstance(MailerInterface::class);

        $mail->setFrom($from)->setSubject($subject)->text($message);

		$arrAdr = GeneralUtility::trimExplode(',', $this->getNotificationEmail(), true);
		foreach($arrAdr as $adr){
            GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->addMessage(
                GeneralUtility::makeInstance(FlashMessage::class, $adr, '', ContextualFeedbackSeverity::INFO)
            );
            $mail->setTo([new Address($adr)]);
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
        $additionalInformation[] = 'db size: ' . $this->getMaxDbSize() . ' MB';

        return implode(' / ', $additionalInformation);
    }


    /** @return array<string, mixed> */
    public function getTaskParameters(): array
    {
        return [
            'tx_formatt3tools_notification_email' => $this->notificationEmail,
            'tx_formatt3tools_max_db_size' => $this->maxDbSize,
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
        $this->maxDbSize = (int)($parameters['tx_formatt3tools_max_db_size'] ?? $parameters['maxDbSize'] ?? 1);
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
        return $validInput;
    }
}

