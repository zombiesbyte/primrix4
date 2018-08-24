<?php namespace main\application\controllers\admin;

/**
 * PrimrixUsers
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\controllers\controller;
	
	use \main\application\handlers\Uri;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Form;
	use \main\application\handlers\DB;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Captcha;
	use \main\application\handlers\Text;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\PrimrixUsersModel;

	use \main\application\models\cms\RightsModel;

	class PrimrixUsers extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();

		}

		public function userManager()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_users', 'id', $_POST['id']);
				Address::go('/admin/primrix-users/user-manager');
			}

			if(Form::submit('add')) Address::go('/admin/primrix-users/add-user');

			PrimrixUsersModel::buildPrimrixUsersTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixusers.html', 'main/views/admin/modules/primrix_users/');
		}

		public function addUser()
		{
			
			RightsModel::generateRightsRadioBtn(null, 'checkboxRights');

			$rules = [
				'username' => '3|32|username|text',
				'first_name' => '2|64|basic|text',
				'last_name' => '2|64|basic|text',
				'email' => '3|128|email|text',
				'password_1' => '6|64|password|password',
				'password_2' => '6|64|password|password',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsRadioBtn($_POST['rights'], 'checkboxRights');

				if($_POST['password_1'] != $_POST['password_2']) Form::setErrors('password_1', 'Password', null, 9);
				if(!Form::lookupDNS($_POST['email'], 'MX')) Form::setErrors('password_1', 'Password', null, 53);
				if(PrimrixUsersModel::newUsername($_POST['username'])) Form::setErrors('username', 'Username', null, 54);

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_users', 'id');
					$_POST['order'] = $_POST['id'];
					$_POST['password'] = Auth::hash($_POST['password_1']);
					$_POST['auth_code'] = '';
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_users', $_POST);

					Address::go('/admin/primrix-users/user-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/primrix-users/user-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixusers_add.html', 'main/views/admin/modules/primrix_users/');
		}

		public function editUser()
		{
			$id = Uri::$array[3];

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query('primrix_users');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/primrix-users/user-manager');
			
			if($data['rights'] < $_SESSION['primrix']['user']['rights']) Address::go('/admin/primrix-users/user-manager');

			RightsModel::generateRightsRadioBtn($data['rights'], 'checkboxRights');

			$rules = [
				'username' => '3|32|username|text',
				'first_name' => '2|64|basic|text',
				'last_name' => '2|64|basic|text',
				'email' => '3|128|email|text',
				'password_1' => '0|64|password|password',
				'password_2' => '0|64|password|password',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsRadioBtn($_POST['rights'], 'checkboxRights');

				if($_POST['password_1'] != ''){
					if(strlen($_POST['password_1']) < 6) Form::setErrors('password_1', 'Password', null, 2);
					else if($_POST['password_1'] != $_POST['password_2']) Form::setErrors('password_1', 'Password', null, 9);
				}
				if(!Form::lookupDNS($_POST['email'], 'MX')) Form::setErrors('password_1', 'Password', null, 53);
				
				if($_POST['username'] != $data['username']){
					if(PrimrixUsersModel::newUsername($_POST['username'])) Form::setErrors('username', 'Username', null, 54);
				}

				if(Form::validate($rules)){

					if($_POST['password_1'] != '') $_POST['password'] = Auth::hash($_POST['password_1']);
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update('primrix_users', $_POST, 'id,password,created_at,order,auth_code');

					Address::go('/admin/primrix-users/userManager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/primrix-users/user-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixusers_add.html', 'main/views/admin/modules/primrix_users/');
		}

		public function permissions()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_rights', 'id', $_POST['id']);
				Address::go('/admin/primrix-users/permissions');
			}

			if(Form::submit('add')) Address::go('/admin/primrix-users/add-permission');

			PrimrixUsersModel::buildPermissionsTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixpermissions.html', 'main/views/admin/modules/primrix_users/');
		}

		public function addPermission()
		{
			$rules = [
				'group_name' => '3|16|usernamesp|text',
				'group_colour' => '6|7|hexhash|text',
				'compulsory' => '1|1|alpha|radio',
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db = new DB;

					//we don't want to write our html entity to the table so we must convert it back again
					$_POST['group_colour'] = str_replace('&num;', '#', $_POST['group_colour']);

					if($_POST['group_colour'][0] != '#'){
						$_POST['group_colour'] = '#' . $_POST['group_colour'];
						$_POST['group_colour'] = substr($_POST['group_colour'], 0, 7);
					}

					$_POST['group_colour'] = strtoupper($_POST['group_colour']);

					$_POST['id'] = $db->nextIndex('primrix_rights', 'id');
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];				

					$db->insert('primrix_rights', $_POST);

					Address::go('/admin/primrix-users/permissions');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/primrix-users/permissions');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixpermissions_add.html', 'main/views/admin/modules/primrix_users/');
		}

		public function editPermission()
		{
			$db = new DB;
			$id = Uri::$array[3];

			$db->where("`id` = '{$id}'");
			$db->query('primrix_rights');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/primrix-users/permissions');

			$db->close();

			$rules = [
				'group_name' => '3|16|usernamesp|text',
				'group_colour' => '6|7|hexhash|text',
				'compulsory' => '1|1|alpha|radio',
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){

					//we don't want to write our html entity to the table so we must convert it back again
					$_POST['group_colour'] = str_replace('&num;', '#', $_POST['group_colour']);

					if($_POST['group_colour'][0] != '#'){
						$_POST['group_colour'] = '#' . $_POST['group_colour'];
						$_POST['group_colour'] = substr($_POST['group_colour'], 0, 7);
					}

					$_POST['group_colour'] = strtoupper($_POST['group_colour']);

					//Primrix Admin - some options cannot be changed
					if($id == 1){
						$_POST['group_name'] = 'Primrix Admin';
						$_POST['compulsory'] = 'y';
					}
					
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];				

					$db->where("`id` = '{$id}'");
					$db->update('primrix_rights', $_POST);

					Address::go('/admin/primrix-users/permissions');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/primrix-users/permissions');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'primrixpermissions_add.html', 'main/views/admin/modules/primrix_users/');
		}
	}

//!EOF : /main/application/controllers/admin/primrixusers.php