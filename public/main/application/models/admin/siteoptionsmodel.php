<?php namespace main\application\models\admin;

/**
 * SiteOptionsModel
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;
	use \main\application\handlers\DB;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Text;
	use \main\application\handlers\Uri;
	use \main\application\handlers\Form;
	use \main\application\handlers\Address;
	use \main\application\handlers\File;

	use \main\application\models\cms\RightsModel;

	class SiteOptionsModel
	{
		public static function buildGeneralSettingsTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`order` ASC");
			$db->query('primrix_settings');
			while($row = $db->fetch()){
				$html .= "<tr>\n";
				$html .= "<td class='small'><a data-table='primrix_settings' data-id='{$row['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small'><a data-table='primrix_settings' data-id='{$row['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft' alt='{$row['notes']}'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>" . Text::teaser($row['value'], 300) . "</td>\n";

				$returnURL = Address::encode("/admin/site-options/general-settings");

				$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_settings/{$row['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></a></td>\n";
				$html .= "<td class='small'><a href='/admin/primrix/editor/primrix_settings/{$row['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		public static function buildBackupsTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`created_at` ASC");
			$db->query('primrix_backup');
			$totalRows = $db->numRows();
			while($row = $db->fetch()){
				$html .= "<tr>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['description']}</td>\n";
				$html .= "<td class='txtLeft'>" . date('jS M Y (H:i)', strtotime($row['created_at'])) . "</td>\n";

				$returnURL = Address::encode("/admin/site-options/backup-and-restore");
				$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_backup/{$row['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></a></td>\n";

				if($row['type'] == 'database'){
					$html .= "<td class='small'><span class='fa fa-check' alt='Included'></span></td>\n";
					$html .= "<td class='small'><span class='fa fa-close' alt='Excluded'></span></td>\n";
				}
				else if($row['type'] == 'files'){
					$html .= "<td class='small'><span class='fa fa-close' alt='Excluded'></span></td>\n";
					$html .= "<td class='small'><span class='fa fa-check' alt='Included'></span></td>\n";
				}
				else{
					$html .= "<td class='small'><span class='fa fa-check' alt='Included'></span></td>\n";
					$html .= "<td class='small'><span class='fa fa-check' alt='Included'></span></td>\n";
				}

				$html .= "<td class='small'><a href='/admin/site-options/restore/{$row['id']}' class='fa fa-cloud-upload' alt='Restore using this backup'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
			return $totalRows;
		}

		public static function buildRestoreTable($id)
		{
			$db = new DB;
			$html = "";

			$db->where("`id` = '{$id}'");
			$db->query('primrix_backup');
			$found = $db->numRows();
			if($found == 1){
				$row = $db->fetch();

				if($row['type'] == 'database' or $row['type'] == 'both'){
					$html .= "<tr>\n";
					$html .= "<td class='small primary'><span class='fa fa-database' alt='Database'></span></td>\n";
					$html .= "<td class='txtLeft primary'>database.sql</td>\n";
					$html .= "</tr>\n";
				}

				if($row['type'] == 'files' or $row['type'] == 'both'){

					$env = Def::$primrix->environment;
					$rootPath = Def::$primrix->settings->$env->rootpath;
					$siteDir = Def::$primrix->settings->$env->site_dir;
					$publicDir = Def::$primrix->settings->$env->public;

					$restoreFrom = $rootPath . $siteDir . 'backups/' . $row['name'] . '/files';

					$dirArray = File::recursiveDirectoryList($restoreFrom);

					foreach($dirArray as $dir){
						$fullDirPath = $restoreFrom . $dir . '/';
						$fileList = File::getFileList($fullDirPath);
						if(isset($fileList['filename_ext'])){
							foreach($fileList['filename_ext'] as $fn){
								$html .= "<tr>\n";
								$html .= "<td class='small'><span class='fa fa-file-o' alt='File'></span></td>\n";
								$html .= "<td class='txtLeft'>../backups/{$row['name']}/files{$dir}/{$fn}</td>\n";
								$html .= "</tr>\n";
							}
						}
					}
					
				}

			}

			if($found == 1){
				Doc::bind('table_rows', $html);
				return true;
			}
			else return false;
		}

		public static function restoreBackup($id)
		{
			$db = new DB;

			$db->where("`id` = '{$id}'");
			$db->query('primrix_backup');
			$found = $db->numRows();
			if($found == 1){
				$row = $db->fetch();

				$env = Def::$primrix->environment;
				$rootPath = Def::$primrix->settings->$env->rootpath;
				$siteDir = Def::$primrix->settings->$env->site_dir;
				$publicDir = Def::$primrix->settings->$env->public;

				if($row['type'] == 'database' or $row['type'] == 'both'){

					$restoreDB = $rootPath . $siteDir . 'backups/' . $row['name'] . '/database/backup.sql';
					$db->restore($restoreDB);
					
				}

				if($row['type'] == 'files' or $row['type'] == 'both'){

					$restoreFrom = $rootPath . $siteDir . 'backups/' . $row['name'] . '/files';
					$restoreTo = $rootPath . $publicDir . 'site/files';

					$dirArray = File::recursiveDirectoryList($restoreFrom);

					foreach($dirArray as $dir){
						$fullDirPath = $restoreFrom . $dir . '/';
						File::createDirectory($restoreTo . $dir);
						$fileList = File::getFileList($fullDirPath);
						if(isset($fileList['filename_ext'])){
							foreach($fileList['filename_ext'] as $fn){
								File::copy($fullDirPath . $fn, $restoreTo . $dir . '/' . $fn);
							}
						}
					}
					
				}

			}
		}
	}

//!EOF : /main/application/models/admin/siteoptionsmodel.php