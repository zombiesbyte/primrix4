<?php namespace main\application\handlers;

/**
 * Mail
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;

	class Mail
	{
		protected static $lineEnd;

		/**
		 * Using the database for sending an email, this method looks up the id of the
		 * response and collates the options from the database row and calls the protected
		 * method _setupEmail to finally send the email.
		 * @param int $responseID the id of the response within the mail_response table
		 * @param string $toEmail the email recipient
		 * @param array $dataArray the data array used to inject our placeholders
		 * @return boolean response from mail
		 */
		public static function sendMailResponse($responseID, $toEmail, $dataArray)
		{
			
			$domainID = Def::$primrix->settings->production->domain_id;
			$domainExt = Def::$primrix->settings->production->domain_ext;
			$domainName = $domainID . $domainExt;

			$db = new DB;
			$db->where("`id` = '{$responseID}'");
			$db->query('primrix_mail_response');
			if($db->numRows() == 1){
				
				$rRow = $db->fetch();
				$db->close();

				if($rRow['reply_to'] == "") $rRow['reply_to'] = "no-reply@" . $domainName;

				$bccArray = "";
				if($rRow['bcc'] != ''){
					$rRow['bcc'] = str_replace(' ', '', $rRow['bcc']); //replace all spaces within bcc list
					$bccArray = explode(',', $rRow['bcc']);
				}

				$dataArray['subject'] = $rRow['subject'];
				$dataArray = self::_defineCommonValues($dataArray);

				if($rRow['send_as'] == 'html'){
					$bodyContent = file_get_contents($rRow['template']);
					$dataArray['content'] = nl2br($rRow['content'], false);
					$bodyContent = self::injectEmail($bodyContent, $dataArray);
					//we now do this again to replace the content within the content
					$bodyContent = self::injectEmail($bodyContent, $dataArray);
				}
				else{
					$rRow['content'] = $rRow['content'] . $dataArray['email_footer_plain'];
					$dataArray['content'] = $rRow['content'];
					$bodyContent = self::injectEmail($rRow['content'], $dataArray);
				}

				if(self::_setupEmail($toEmail, $bccArray, $rRow['reply_to'], $rRow['reply_to'], $rRow['subject'], $bodyContent, $rRow['send_as'])) return true;
				else return false;

			}
			else return false;

		}

		public static function injectEmail($content, $dataArray)
		{
			foreach($dataArray as $key => $value){
				$var = Def::$primrix->settings->prefix . $key . Def::$primrix->settings->suffix;
				$content = preg_replace('/' . $var . '/', $value, $content);
			}
			return $content;
		}

		/**
		 * Setup and send an email
		 * @param string $toEmail to email recipient
		 * @param array $bccList and array of bcc's
		 * @param string $fromEmail from email
		 * @param string $replyEmail the reply recipient
		 * @param string $subjectLine the subject line
		 * @param string $bodyContent the content of the email
		 * @param string $type text or html
		 * @return boolean response from mail
		 */
		protected static function _setupEmail($toEmail, $bccList, $fromEmail, $replyEmail, $subjectLine, $bodyContent, $type = 'text')
		{
			$headers = array();

			$headers[] = "MIME-Version: 1.0";
			
			if($type == 'text') $headers[] = "Content-type: text/plain; charset=UTF-8";
			else $headers[] = "Content-type: text/html; charset=UTF-8";

			$headers[] = "From: {$fromEmail}";
			
			if(is_array($bccList)){
				foreach($bccList as $bcc){
					$headers[] = "Bcc: {$bcc}";
				}
			}
			
			$headers[] = "Reply-To: {$replyEmail}";
			$headers[] = "X-Mailer: PHP/" . phpversion();

			return mail($toEmail, $subjectLine, $bodyContent, implode("\r\n", $headers));			
		}

		protected static function _defineCommonValues($dataArray)
		{
			$environment = Def::$primrix->environment;
			$domainID = Def::$primrix->settings->{$environment}->domain_id;
			$domainExt = Def::$primrix->settings->{$environment}->domain_ext;
			$domainName = $domainID . $domainExt;

			$commonValues = [
				'environment' 		=> $environment,
				'default_protocol' 	=> Def::$primrix->settings->{$environment}->default_protocol,
				'default_www'   	=> Def::$primrix->settings->{$environment}->default_www,
				'domain_id' 		=> $domainID,
				'domain_ext' 		=> $domainExt,
				'domain_name'		=> $domainName,
				'timezone' 			=> Def::$primrix->datetime->timezone,
				'sdate'				=> Def::$primrix->datetime->sdate,
				'stime'				=> Def::$primrix->datetime->stime
			];

			//database variables
			$db = new DB;
			$db->query('primrix_settings');
			while($row = $db->fetch()){
				$commonValues[$row['name']] = $row['value'];
			}
			$db->close();

			//we don't want to overwrite any of the values so we check that
			//they are either not set or have no value before we include the
			//common key and value pair.
			foreach($commonValues as $key => $value){
				if(!isset($dataArray[$key]) or $dataArray[$key] == ''){
					$dataArray[$key] = $value;
				}
			}
			return $dataArray;
		}
	}

//!EOF : /main/application/handlers/mail.php		