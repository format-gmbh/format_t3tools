<?php
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



use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
* Class tx_formatt3tools_dbcheck
*
* @author	Andreas Kessel <typo3-dev@formatsoft.de>
* @package  TYPO3
* @subpackage	tx_formatt3tools
*/
class tx_formatt3tools_dbcheck extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Email address to send email notification to in case we find problems with
	 * the system.
	 *
	 * @var	string
	 */
	protected $notificationEmail = NULL;
	protected $maxDbSize = 1;


	/**
	 * Function executed from scheduler.
	 * Send the newsletter
	 * 
	 * @return	void
	 */
	function execute() {
		$GLOBALS['LANG']->includeLLFile('EXT:format_t3tools/tasks/locallang.xml'); 
		
        $gesamt = 0;
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $statement = $connection->query('SHOW TABLE STATUS');
        
        while ($row = $statement->fetch()) {
            $summe = $row["Index_length"] + $row["Data_length"];
            $gesamt += $summe;
        }

        $gesamtMByte = round($gesamt / (1024 * 1024),1);

        if($gesamtMByte > $this->maxDbSize){
          $this->sendNotificationEmail($gesamtMByte.' MByte');
        } 

		return true;
	} // end of 'function execute() {..}'



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
	public function getMaxDbSize() {
		return intval($this->maxDbSize);
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
	 * Sets the maxDbSize.
	 *
	 * @param	int	$maxDbSize 
	 */
	public function setMaxDbSize($maxDbSize) {
		$this->maxDbSize = intval($maxDbSize);
	}





	/**
	 * Sends a notification email, reporting system issues.
	 *
	 * @param	array	$systemStatus Array of statuses
	 */
	protected function sendNotificationEmail($groesse) {

		$subject = sprintf(
			$GLOBALS['LANG']->getLL('dbchecktask.email_subject'),
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']
		);
		$subject.= ': '.$groesse;

		$message = sprintf(
			$GLOBALS['LANG']->getLL('dbchecktask.email_message'),
			'',
			''
		);
		$message.= CRLF . CRLF;

		$from =  $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        
        /** @var $mail \TYPO3\CMS\Core\Mail\MailMessage */
        $mail = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
        $mail->setFrom($from)->setSubject($subject)->setBody($message);
		
		$arrAdr = explode(',', $this->notificationEmail);
		foreach($arrAdr as $adr){
            $mail->setTo($adr);
            $mail->send();
        }
	}

    
}

