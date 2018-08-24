<?php namespace main\application\controllers\admin;

/**
 * Login
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;

	use \main\application\controllers\controller;
	
	use \main\application\handlers\Uri;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Form;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Text;
	use \main\application\handlers\Mail;

	use \main\application\models\admin\UserModel;

	use \vendor\primrix\securimage\Securimage;

	class Login extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();
		}

		public function index()
		{
			$securImage = new Securimage();

			Doc::view('login.html', 'main/views/admin/login/');
			Doc::bind('login');
			
			$rules = [
				'username' => '3|32|basic|text',
				'password' => '6|128|password|password',
				'captcha' => '5|5|alphanum|text',
				'rememberMe' => '0|1|basic|check|Remember Me'
			];
			


			//lets get the cookie and set our form details if available
			if(Form::getCookie('username')){
				$data = [
					'username' => Form::getCookie('username'),
					'rememberMe' => 'yes'
				];
			}
			else $data = null;

			Form::setData($rules, $data);

			if(Form::submit('submit')){

				sleep(2); //cracker annoyance

				if(!$securImage->check($_POST['captcha'])) Form::setErrors('captcha', 'Captcha', null, 20);

				if(Form::validate($rules)){
					$userRow = UserModel::login($_POST['username'], $_POST['password']);
					if($userRow){
						UserModel::setLogin($userRow);
						if($_POST['rememberMe'] == 'yes') Form::setCookie('username', $_POST['username']);
						Address::go('/admin');
					}
					else Doc::bind('login', def::$errorCodes[51]);
				}				
			}

		}

		public function reminder()
		{
			$securImage = new Securimage();

			Doc::view('forgot-login.html', 'main/views/admin/login/');
			Doc::bind('login');

			$rules = [
				'email' => '3|128|email|text',
				'captcha' => '5|5|alphanum|text'
			];

			Form::setData($rules);

			if(Form::submit('submit')){

				if(!Form::lookupDNS($_POST['email'])) Form::setErrors('email', null, null, 7);
				if(!$securImage->check($_POST['captcha'])) Form::setErrors('captcha', 'Captcha', null, 20);

				if(Form::validate($rules)){

					$this->db->where("`email` = '{$_POST['email']}'");
					$this->db->query('primrix_users');
					if($this->db->numRows() == 1){

						Doc::bind('login', Def::$errorCodes[102]);
						$dataArray = $this->db->fetch();
						$this->db->close();

						$authCode = UserModel::setAuthCode($dataArray['email']);

						$dataArray['reset_link'] = "<a href='" . Address::url("/admin/login/reset/{$dataArray['email']}/{$authCode}") . "'>";
						$dataArray['reset_link'] .= Address::url("/admin/login/reset/{$dataArray['email']}/{$authCode}") . "</a>";

						$dataArray['email'] = html_entity_decode($dataArray['email'], ENT_COMPAT | ENT_HTML5, 'UTF-8');

						if(!Mail::sendMailResponse('1', $dataArray['email'], $dataArray)) Doc::bind('login', Def::$errorCodes[103]);;

					}
					else Doc::bind('login', Def::$errorCodes[8]);
				}
			}
		}

		public function reset()
		{
			$securImage = new Securimage();
			
			Doc::view('reset-login.html', 'main/views/admin/login/');
			Doc::bind('login');

			$rules = [
				'password1' => '6|128|password|password',
				'password2' => '6|128|password|password',
				'captcha' => '5|5|alphanum|text'
			];

			Form::setData($rules);

			if(Form::submit('submit')){

				sleep(1); //cracker annoyance

				$email = Form::charFilter(Def::$ruleSet['presets']['email'], Uri::$array[3], true);
				$authCode = Form::charFilter(Def::$ruleSet['presets']['cryptic'], Uri::$array[4], false);
				
				if($_POST['password1'] != $_POST['password2']) Form::setErrors('password1', 'Password', null, 9);
				if(!$securImage->check($_POST['captcha'])) Form::setErrors('captcha', 'Captcha', null, 20);

				if(Form::validate($rules)){
					
					if(UserModel::checkAuthCode($email, $authCode)){
						UserModel::setPassword($email, $_POST['password1']);
						Doc::bind('login', def::$errorCodes[52]);
					}
					else Doc::bind('login', def::$errorCodes[50]);
				}
			}
		}
	}

//!EOF : /main/application/controllers/admin/login.php