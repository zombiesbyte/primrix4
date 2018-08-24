<?php namespace main\application\models\admin;

/**
 * UserModel
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;
	use \main\application\handlers\Doc;
	use \main\application\handlers\DB;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Text;
	use \main\application\handlers\Address;

	class UserModel
	{
		public static function login($username, $password, $table = 'primrix_users')
		{
			$db = new DB;
			$db->where("`username` = '{$username}'");
			$db->query($table);
			$totalUsers = $db->numRows();
			if($totalUsers == 1){
				$userRow = $db->fetch();
				$db->close();

				if(Auth::verifyHash($password, $userRow['password'])) return $userRow;
				else return false;
			}
			else return false;
		}

		public static function setLogin($userRow)
		{
			foreach($userRow as $field => $value){
				if($field != 'password') $_SESSION['primrix']['user'][$field] = $value;
			}

			//session time values
			$_SESSION['primrix']['start_time'] = date('Y-m-d H:i:s');
//pull in the required session time from our def /db			
			$_SESSION['primrix']['end_time'] = date('Y-m-d H:i:s', strtotime('+ 2 hours'));
		}

		public static function checkLoginSession()
		{
			if(isset($_SESSION['primrix']['start_time'])){
				
				$dateNow = date('Y-m-d H:i:s');

				list($hrs, $min, $sec, $hrsOnly, $minOnly, $secOnly) = Chronos::timeDifference($dateNow, $_SESSION['primrix']['end_time']);
				$percentage = Chronos::percent($_SESSION['primrix']['start_time'], $dateNow, $_SESSION['primrix']['end_time'], false);
				
				if($secOnly <= 0){
					self::logout();
					$percentage = 100; //makes sure we don't flood the screen with an out of control div length if the percentage happens to be more than 100.
				}

				$sessionCheck = [
					'sessionPercent' 	=> $percentage,
					'sessionHrs'		=> $hrs,
					'sessionMin'		=> $min,
					'sessionSec'		=> $sec,
					'sessionHrsOnly'	=> $hrsOnly,
					'sessionMinOnly'	=> $minOnly,
					'sessionSecOnly'	=> $secOnly
				];
				
				//grammar corrections
				if($hrs > 1) $hS = 's';
				else $hS = '';
				if($min > 1) $mS = 's';
				else $mS = '';

				if($hrs >= 1) {
					$sessionCheck['sessionRemaining'] = "{$hrs} Hour{$hS}, {$min} minutes remaining";
					$sessionCheck['sessionMinsRemaining'] = "{$minOnly} minutes remaining";
				}
				else if($min >= 1){
					$sessionCheck['sessionRemaining'] = "{$min} minute{$mS} remaining";
					$sessionCheck['sessionMinsRemaining'] = "{$minOnly} minute{$mS} remaining";
				}
				else {
					$sessionCheck['sessionRemaining'] = "<span class='fa fa-exclamation-circle'></span> {$sec} seconds remaining";
					$sessionCheck['sessionMinsRemaining'] = "<span class='fa fa-exclamation-circle'></span> {$sec} seconds remaining";
				}

				//lets bind our variables to the document
				foreach($sessionCheck as $key => $val){
					Doc::bind($key, $val);
				}

				return $sessionCheck;
			}
		}

		/**
		 * This method simply checks and returns true if the session is still
		 * active. This is handy for quick checks on AJAX calls or other processes
		 * that require checking that the user is still logged in.
		 * @return boolean returns true if user is logged in
		 */
		public static function getLoginSession()
		{
			
			if(isset($_SESSION['primrix']['start_time'])){
				
				$dateNow = date('Y-m-d H:i:s');

				list($hrs, $min, $sec, $hrsOnly, $minOnly, $secOnly) = Chronos::timeDifference($dateNow, $_SESSION['primrix']['end_time']);
				if($secOnly <= 0) return false;
				else return true;
			}
			else return false;

		}

		public static function setVariables()
		{
			Doc::bind('userFirstName', $_SESSION['primrix']['user']['first_name']);
		}

		public static function setAuthCode($email)
		{
			$key = Text::randWord(20, 'cryptic');
			$expires = date('YmdHi', strtotime('+1 hour'));
			$authCode = $expires . $key;
			$db = new DB;
			$db->where("`email` = '{$email}'");
			$db->update('primrix_users', ['auth_attempts' => '0', 'auth_code' => $authCode]);
			return $authCode;
		}

		public static function checkAuthCode($email, $key)
		{
			$expiry = strtotime(substr($key, 0, 12));
			$current = strtotime(date('YmdHi'));
			
			if($expiry > $current){

				$db = new DB;
				$db->where("`email` = '{$email}'");
				$db->query('primrix_users');
				if($db->numRows() == 1){

					$row = $db->fetch();
					$db->close();

					if($row['auth_attempts'] < 3){
						if($key == $row['auth_code']){
							//lets reset the auth_code again
							self::setAuthCode($email);
							return true;
						}
						else{
							$attempts = $row['auth_attempts'] + 1;
							$db->where("`email` = '{$email}'");
							$db->update('primrix_users', "auth_attempts,{$attempts}");
							return false; //can't match auth code
						}
					}
					else return false; //too many attempts
				}
				else return false; //email address not found
			}
			else return false; //expired link
		}

		public static function setPassword($email, $newPassword)
		{
			$newPassword = Auth::hash($_POST['password1']);
			
			$db = new DB;
			$db->where("`email` = '{$email}'");
			$db->update('primrix_users', ['password' => "{$newPassword}"]);
		}

		public static function logout()
		{
			$_SESSION = array();
			unset($_SESSION);
//if we can't save before being kicked then this will be the reason why! remove redirect			
			Address::go('/admin/login');
		}

	}

//!EOF : /main/application/models/admin/user.php