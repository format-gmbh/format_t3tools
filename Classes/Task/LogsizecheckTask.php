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
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

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
            $size = filesize($dirname . '/' . $logfile);
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
		return (int)$this->maxLogSize;
	}
	
	
	
	

	/**
	 * Sets the notification email address.
	 *
	 * @param	string	$notificationEmail Notification email address.
	 */
	public function setNotificationEmail($notificationEmail) {
		$this->notificationEmail = $notificationEmail;
	}


	/**
	 * Sets the maxLogSize.
	 *
	 * @param	int	$maxLogSize
	 */
	public function setMaxLogSize($maxLogSize) {
		$this->maxLogSize = (int)$maxLogSize;
	}





	/**
     * Sends a notification email, reporting size of log files in typo3temp/var/log
     *
     * @param string $groesse Gesamtgröße aller Log-Files
     * @param array $arrFileinfo Array mit Dateinamen und Größen
	 */
	protected function sendNotificationEmail($groesse, $arrFileinfo) {

        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

		$subject = sprintf(
            $this->getLanguageService()->sL($this->languageFile . ':tasks.logsizecheck.email.subject'),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
		);
		$subject.= ': '.$groesse;

		$message = sprintf(
			$this->getLanguageService()->sL($this->languageFile . ':tasks.email.message'),
			'',
			''
		);
		$message.= CRLF . CRLF;
		foreach ($arrFileinfo as $file) {
		    if($file['name'] !== '.htaccess') {
		        // Only send shortened file names by mail.
                $message.= substr($file['name'], 0 , 9) . '..... ';
                // Size of the file in MByte
                $message.= round($file['size'] / (1024 * 1024),1) . ' MB'. CRLF;
            }
        }
        $message.= CRLF . CRLF;

		$from =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        
        /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);

        if ($versionInformation->getMajorVersion() === 10) {
            $mail->setFrom($from)->setSubject($subject)->text($message);
        } else {
            $mail->setFrom($from)->setSubject($subject)->setBody($message);
        }

		$arrAdr = explode(',', $this->getNotificationEmail());
		foreach($arrAdr as $adr){
            $mail->setTo($adr);
            $mail->send();
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



    /**
     * @return LanguageService|null
     */
    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

}

