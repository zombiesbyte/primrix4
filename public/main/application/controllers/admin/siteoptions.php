<?php namespace main\application\controllers\admin;

/**
 * SiteOptions
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
	use \main\application\handlers\File;

	use \site\config\Def;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\SiteOptionsModel;

	use \main\application\models\cms\RightsModel;


	class SiteOptions extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();
		}

		public function generalSettings()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_settings', 'id', $_POST['id']);
				Order::rebuildOrders('primrix_settings', 'order');
				Address::go('/admin/site-options/general-settings');
			}

			if(Form::submit('add')){
				$returnURL = Address::encode("/admin/site-options/general-settings");
				Address::go('/admin/primrix/new-variable/primrix_settings/' . $returnURL);
			}

			SiteOptionsModel::buildGeneralSettingsTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'generalsettings.html', 'main/views/admin/modules/site_options/');
		}

		public function backupAndRestore()
		{
			if(Form::submit('delete')){
				if(isset($_POST['id'])){
					
					$db = new DB;

					$env = Def::$primrix->environment;
					$rootPath = Def::$primrix->settings->$env->rootpath;
					$siteDir = Def::$primrix->settings->$env->site_dir;
					$publicDir = Def::$primrix->settings->$env->public;

					foreach($_POST['id'] as $id){
						$db->where("`id` = '{$id}'");
						$db->query('primrix_backup');
						if($db->numRows() == 1){
							$backupRow = $db->fetch();
							$db->close();
							File::removeDirectory($rootPath . $siteDir . 'backups/' . $backupRow['name']);
							$db->where("`id` = '{$id}'");
							$db->deleteRow('primrix_backup');
						}
					}

					Address::go('/admin/site-options/backup-and-restore');
				}
			}

			$totalBackups = SiteOptionsModel::buildBackupsTable();

			$maxBackupSlots = Def::$primrix->settings->maxBackupSlots;
			$remainingBackups = $maxBackupSlots - $totalBackups;

			Doc::bind('totalBackups', $totalBackups);
			Doc::bind('maxBackupSlots', $maxBackupSlots);
			Doc::bind('remainingBackups', $remainingBackups);

			if($remainingBackups > 0) Doc::bind('showAdd', '');
			else Doc::bind('showAdd', 'hide');

			if(Form::submit('add')) Address::go('/admin/site-options/new-backup');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'backupandrestore.html', 'main/views/admin/modules/site_options/');
		}

		public function newBackup()
		{
			//we need to check to see if the user has available backup slots
			$db = new DB;

			$db->query('primrix_backup');
			$usedBackupSlots = $db->numRows();
			$db->close();

			$maxBackupSlots = Def::$primrix->settings->maxBackupSlots;
			if($usedBackupSlots >= $maxBackupSlots) Address::go('/admin/site-options/backup-and-restore');

			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'type' => '1|1|alpha|select',
				'name' => '2|32|varname|text',
				'description' => '0|0|basic|textarea',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				$db->where("`name` = '{$_POST['name']}'");
				$db->query('primrix_backup');
				if($db->numRows() > 0) Form::setErrors('name', 'Name', null, 23);

				if(Form::validate($rules)){

					$env = Def::$primrix->environment;
					$rootPath = Def::$primrix->settings->$env->rootpath;
					$siteDir = Def::$primrix->settings->$env->site_dir;
					$publicDir = Def::$primrix->settings->$env->public;

					File::createDirectory($rootPath . $siteDir . 'backups/' . $_POST['name'] . '/');
					File::createDirectory($rootPath . $siteDir . 'backups/' . $_POST['name'] . '/database');
					File::createDirectory($rootPath . $siteDir . 'backups/' . $_POST['name'] . '/files');

					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);
					$_POST['id'] = $db->nextIndex('primrix_backup', 'id');
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_backup', $_POST);

					if($_POST['type'] == 'files' or $_POST['type'] == 'both'){
						$dirArray = File::recursiveDirectoryList($rootPath . $publicDir . 'site/files/');

						foreach($dirArray as $dir){
							$fullDirPath = $rootPath . $publicDir . 'site/files' . $dir . '/';
							$backupDirPath = $rootPath . $siteDir . 'backups/' . $_POST['name'] . '/files' . $dir . '/';
							File::createDirectory($rootPath . $siteDir . 'backups/' . $_POST['name'] . '/files' . $dir);
							$fileList = File::getFileList($fullDirPath);
							if(isset($fileList['filename_ext'])){
								foreach($fileList['filename_ext'] as $fn){
									//echo "copy: " . $fullDirPath . $fn . " [to] " . $backupDirPath . $fn . "<br>\n"; 
									copy($fullDirPath . $fn, $backupDirPath . $fn);
								}
							}
						}
					}
					
					if($_POST['type'] == 'database' or $_POST['type'] == 'both'){
						$db->backup($rootPath . $siteDir . 'backups/' . $_POST['name'] . '/database/backup.sql');
					} 
					
					Address::go('/admin/site-options/backup-and-restore');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/site-options/backup-and-restore');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'backupandrestore_add.html', 'main/views/admin/modules/site_options/');
		}

		public function restore()
		{
			$id = Uri::$array[3];

			$found = SiteOptionsModel::buildRestoreTable($id);
			if(!$found) Address::go('/admin/site-options/backup-and-restore');

			if(Form::submit('cancel')) Address::go('/admin/site-options/backup-and-restore');

			if(Form::submit('restore')){
				SiteOptionsModel::restoreBackup($id);
				Address::go('/admin/site-options/restored');
			}

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'restore.html', 'main/views/admin/modules/site_options/');
		}

		public function restored()
		{
			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'restored.html', 'main/views/admin/modules/site_options/');
		}
	}

//!EOF : /main/application/controllers/admin/siteoptions.php