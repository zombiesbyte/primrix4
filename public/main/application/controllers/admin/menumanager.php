<?php namespace main\application\controllers\admin;

/**
 * MenuManager
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
	use \main\application\handlers\Order;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;

	use \main\application\models\cms\RightsModel;

	class MenuManager extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();
		}

		public function index()
		{

			if(Form::submit('add')) Address::go('/admin/menu-manager/add');

			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['a'])) $db->deleteRowByArray('primrix_menu', 'id', $_POST['a']);
				if(isset($_POST['b'])) $db->deleteRowByArray('primrix_menu_items', 'id', $_POST['b']);

				//remove children
				if(isset($_POST['a'])) $db->deleteRowByArray('primrix_menu_items', 'group', $_POST['a']);

				Order::rebuildOrders('primrix_menu', 'order');
				Order::rebuildOrders('primrix_menu_items', 'order', 'group');
				Address::go('/admin/menu-manager');
			}

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'menumanager.html', 'main/views/admin/modules/primrix_settings/');

			MenuManagerModel::buildMenuTable();
		}

		public function add()
		{
			
			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'group' => '0|6|num|select',
				'name' => '2|32|basic|text',
				'menu_ref' => '2|32|alphanum|text',
				'menu_icon' => '0|32|usernamesp|text',
				'menu_tip' => '0|245|basic|text',
				'menu_link' => '0|255|url|text',
				'active' => '1|1|a|radio',
				'opens_new' => '0|1|a|check',
				'dash' => '0|1|a|check',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);
					
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					if($_POST['group'] > 0){
						$_POST['id'] = $db->nextIndex('primrix_menu_items', 'id');
						$_POST['order'] = $db->nextIndexGrp('primrix_menu_items', 'order', 'group', $_POST['group']);
						$db->insert('primrix_menu_items', $_POST);
					}
					else{
						$_POST['id'] = $db->nextIndex('primrix_menu', 'id');
						$_POST['order'] = $db->nextIndex('primrix_menu', 'order');
						$db->insert('primrix_menu', $_POST);
					}

					Address::go('/admin/menu-manager');
				}				
			}

			if(Form::submit('cancel')) Address::go('/admin/menu-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'menumanager_add.html', 'main/views/admin/modules/primrix_settings/');

			MenuManagerModel::buildMenuSelectList();
		}

		public function edit()
		{
			$db = new DB;

			$editTable = URI::$array[3];
			$editID = URI::$array[4];

			$db->where("`id` = '{$editID}'");
			$db->query($editTable);
			if($db->numRows() <= 0) Address::go('/admin/menu-manager');

			$data = $db->fetch();
			$db->close();

			$rightsArray = explode("\n", $data['rights']);
			RightsModel::generateRightsCheckboxes($rightsArray, 'checkboxRights');

			$rules = [
				'name' => '2|32|basic|text',
				'menu_ref' => '2|32|alphanum|text',
				'menu_icon' => '0|32|usernamesp|text',
				'menu_tip' => '0|245|basic|text',
				'menu_link' => '0|255|url|text',
				'active' => '1|1|a|radio',
				'opens_new' => '0|1|a|check',
				'dash' => '0|1|a|check',
				'rights' => '1|0|num|check'				
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					
					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);

					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];
					$db->where("`id` = '{$editID}'");
					$db->update($editTable , $_POST, 'id');
					Address::go('/admin/menu-manager');
				}				
			}

			if(Form::submit('cancel')) Address::go('/admin/menu-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'menumanager_edit.html', 'main/views/admin/modules/primrix_settings/');
		}
	}

//!EOF : /main/application/controllers/admin/menumanager.php