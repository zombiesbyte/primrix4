<?php namespace main\application\controllers\admin;

/**
 * SiteStructures
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
	use \main\application\models\admin\SiteStructuresModel;

	use \main\application\models\cms\RightsModel;

	class SiteStructures extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();

		}

		public function redirectionManager()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_redirects', 'id', $_POST['id']);
				Address::go('/admin/site-structures/redirection-manager');
			}

			if(Form::submit('add')) Address::go('/admin/site-structures/add-redirection');

			SiteStructuresModel::buildRedirectionTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'redirectionmanager.html', 'main/views/admin/modules/site_structures/');
		}

		public function addRedirection()
		{
			
			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'name' => '2|32|basic|text',
				'from_url' => '2|255|url|text',
				'to_url' => '2|255|url|text',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){

					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);

					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_users', 'id');
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_redirects', $_POST);

					Address::go('/admin/site-structures/redirection-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/site-structures/redirection-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'redirectionmanager_add.html', 'main/views/admin/modules/site_structures/');
		}

		public function editRedirection()
		{
			
			$id = Uri::$array[3];

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query('primrix_redirects');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/site-structures/redirection-manager');

			$db->close();

			$rules = [
				'name' => '2|32|basic|text',
				'from_url' => '2|255|url|text',
				'to_url' => '2|255|url|text',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			$data['rights'] = explode("\n", $data['rights']);
			RightsModel::generateRightsCheckboxes($data['rights'], 'checkboxRights');

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){

					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update('primrix_redirects', $_POST);

					Address::go('/admin/site-structures/redirection-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/site-structures/redirection-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'redirectionmanager_add.html', 'main/views/admin/modules/site_structures/');
		}

		public function siteManager()
		{
			
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['c'])){
					$db->deleteRowByArray('primrix_site_menu_3', 'id', $_POST['c']);
					Order::rebuildOrders('primrix_site_menu_3', 'order', 'group');
				}

				if(isset($_POST['b'])){
					$db->deleteRowByArray('primrix_site_menu_3', 'group', $_POST['b']);
					$db->deleteRowByArray('primrix_site_menu_2', 'id', $_POST['b']);
					Order::rebuildOrders('primrix_site_menu_2', 'order', 'group');
				}

				if(isset($_POST['a'])){
					$db->deleteRowByArray('primrix_site_menu_3', 'group', $_POST['a']);
					$db->deleteRowByArray('primrix_site_menu_2', 'group', $_POST['a']);
					$db->deleteRowByArray('primrix_site_menu_1', 'id', $_POST['a']);
					Order::rebuildOrders('primrix_site_menu_1', 'order');
				}

				//Address::go('/admin/site-structures/site-manager');

			}

			SiteStructuresModel::buildSiteManagerTable();

			if(Form::submit('add')) Address::go('/admin/site-structures/add-menu-item');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitemanager.html', 'main/views/admin/modules/site_structures/');	
		}

		public function addMenuItem()
		{
			$db1 = new DB;
			$db2 = new DB;

			$parentSelections = array();
			$db1->select("`id`, `name`");
			$db1->query('primrix_site_menu_1');
			while($sm1 = $db1->fetch()){
				$parentSelections['2-' . $sm1['id']] = $sm1['name'];
			
				$db2->select("`id`, `name`");
				$db2->where("`group` = '{$sm1['id']}'");
				$db2->query('primrix_site_menu_2');
				while($sm2 = $db2->fetch()){
					$parentSelections['3-' . $sm2['id']] = "&nbsp; &raquo; " . $sm2['name'];
				}
				$db2->close();
			}
			$db1->close();

			Doc::bind('parentSelections', Form::buildHTML('select', 'parentSelector', $parentSelections, $_POST));

			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'parentSelector' => '0|1|numds|select',
				'name' => '2|32|basic|text',
				'linkList' => '0|255|path|select',
				'link' => '0|255|url|text',
				'active' => '1|1|alpha|radio',
				'new_tab' => '0|1|alpha|check',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if($_POST['linkList'] != "" and $_POST['link'] == "") $_POST['link'] = $_POST['linkList'];
				if($_POST['parentSelector']	== "") $_POST['parentSelector'] = '1-1';				

				if(Form::validate($rules)){

					$db = new DB;

					if($_POST['new_tab'] == "") $_POST['new_tab'] = 'n';

					list($tableID, $parentID) = explode('-', $_POST['parentSelector']);

					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);

					$_POST['id'] = $db->nextIndex('primrix_site_menu_' . $tableID, 'id');
					if($tableID > 1){
						$_POST['group'] = $parentID;
						$_POST['order'] = $db->nextIndexGrp('primrix_site_menu_' . $tableID, 'order', 'group', $parentID);
					}
					else $_POST['order'] = $_POST['id'];
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_site_menu_' . $tableID, $_POST);
					Address::go('/admin/site-structures/site-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/site-structures/site-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitemanager_add.html', 'main/views/admin/modules/site_structures/');
		}

		public function editMenuItem()
		{
			$tableID = Uri::$array[3];
			$id = Uri::$array[4];

			$db = new DB;

			$db->where("`id` = '{$id}'");
			$db->query('primrix_site_menu_' . $tableID);
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/site-structures/site-manager');
			$db->close();

			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'parentSelector' => '0|1|numds|select',
				'name' => '2|32|basic|text',
				'linkList' => '0|255|path|select',
				'link' => '0|255|url|text',
				'active' => '1|1|alpha|radio',
				'new_tab' => '0|1|alpha|check',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if($_POST['linkList'] != "" and $_POST['link'] == "") $_POST['link'] = $_POST['linkList'];

				if(Form::validate($rules)){

					if($_POST['new_tab'] == "") $_POST['new_tab'] = 'n';
					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update('primrix_site_menu_' . $tableID, $_POST);
					Address::go('/admin/site-structures/site-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/site-structures/site-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitemanager_edit.html', 'main/views/admin/modules/site_structures/');
		}

	}

//!EOF : /main/application/controllers/admin/sitestructures.php