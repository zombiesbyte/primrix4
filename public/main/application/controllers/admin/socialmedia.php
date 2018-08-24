<?php namespace main\application\controllers\admin;

/**
 * SocialMedia
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
	use \main\application\handlers\File;
	use \main\application\handlers\Order;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\SocialMediaModel;

	use \main\application\models\cms\RightsModel;

	use \site\config\def;

	class SocialMedia extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();

		}

		public function socialBadges()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_social_badges', 'id', $_POST['id']);#
				Order::rebuildOrders('primrix_social_badges', 'order');
				Address::go('/admin/social-media/social-badges');
			}

			if(Form::submit('add')) Address::go('/admin/social-media/add-badge');

			SocialMediaModel::buildSocialMediaTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'socialbadges.html', 'main/views/admin/modules/social_media/');
		}

		public function addBadge()
		{
			
			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');
			if(!isset($_SESSION['assetManager']['folder'])) $_SESSION['assetManager']['folder'] = Form::fromHTML(Def::$primrix->settings->defaultFolder);

			$directoryList = File::recursiveDirectoryList('site/files');
			$folderSelectList = Form::buildHTML('select', 'folder', $directoryList, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectList', $folderSelectList);
			
			if(Form::submit('viewSmall')) $_SESSION['assetManager']['viewStyle'] = 's';
			if(Form::submit('viewMedium')) $_SESSION['assetManager']['viewStyle'] = 'm';
			if(Form::submit('viewLarge')) $_SESSION['assetManager']['viewStyle'] = 'l';

			$rules = [
				'social_media_name' => '3|32|basic|text',
				'social_media_ref' => '3|32|username|text',
				'social_media_image' => '6|255|filepath|text',
				'social_media_link' => '2|64|url|text',
				'opens_new' => '0|1|alpha|radio',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_social_badges', 'id');
					$_POST['order'] = $_POST['id'];
					if($_POST['opens_new'] == '') $_POST['opens_new'] = 'n';
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_social_badges', $_POST);

					Address::go('/admin/social-media/social-badges');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/social-media/social-badges');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'socialbadges_add.html', 'main/views/admin/modules/social_media/');
		}

		public function editBadge()
		{
			$id = Uri::$array[3];

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query('primrix_social_badges');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/social-media/social-badges');
			
			RightsModel::generateRightsCheckboxes($data['rights'], 'checkboxRights');
			if(!isset($_SESSION['assetManager']['folder']))	$_SESSION['assetManager']['folder'] = Form::fromHTML(Def::$primrix->settings->defaultFolder);

			$directoryList = File::recursiveDirectoryList('site/files');
			$folderSelectList = Form::buildHTML('select', 'folder', $directoryList, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectList', $folderSelectList);
			
			if(Form::submit('viewSmall')) $_SESSION['assetManager']['viewStyle'] = 's';
			if(Form::submit('viewMedium')) $_SESSION['assetManager']['viewStyle'] = 'm';
			if(Form::submit('viewLarge')) $_SESSION['assetManager']['viewStyle'] = 'l';

			$rules = [
				'social_media_name' => '3|32|basic|text',
				'social_media_ref' => '3|32|username|text',
				'social_media_image' => '6|255|filepath|text',
				'social_media_link' => '2|64|url|text',
				'opens_new' => '0|1|alpha|radio',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){

					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];
					if($_POST['opens_new'] == '') $_POST['opens_new'] = 'n';
					$db->where("`id` = '{$id}'");
					$db->update('primrix_social_badges', $_POST, 'id,created_at,order');

					Address::go('/admin/social-media/social-badges');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/social-media/social-badges');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'socialbadges_add.html', 'main/views/admin/modules/social_media/');
		}


	}

//!EOF : /main/application/controllers/admin/socialmedia.php