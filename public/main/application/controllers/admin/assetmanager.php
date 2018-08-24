<?php namespace main\application\controllers\admin;

/**
 * AssetManager
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
	use \main\application\handlers\Bunch;
	use \main\application\handlers\Text;
	use \main\application\handlers\Upload;
	use \main\application\handlers\File;
	use \main\application\handlers\Image;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\SiteStructuresModel;

	use \main\application\models\cms\RightsModel;
	use \main\application\models\cms\AssetsModel;

	use \site\config\def;

	class AssetManager extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();

		}

		public function siteFiles()
		{

			$rules = [
				'file' => '0|0|url|text',
				'newFolderName' => '0|32|uri|text|New Folder Name',
				'fileAction' => '0|64|username|text|File Name',
				'resizeWidth' => '0|4|num|text|Resize Width',
				'resizeHeight' => '0|4|num|text|Resize Height'
			];

			Form::setData($rules);


			if(Form::submit('upload')){
				
				$fileDestination = 'site/files' . $_SESSION['assetManager']['folder'] . '/';
				Upload::file('file', $fileDestination, null, 33554432);

				if(Form::validate($rules)){
					
				}
			}

			if(!isset($_SESSION['assetManager']['folder'])) $_SESSION['assetManager']['folder'] = Form::fromHTML(Def::$primrix->settings->defaultFolder);

			if(Form::submit('folder')) $_SESSION['assetManager']['folder'] = $_POST['folder'];

			$directoryList = File::recursiveDirectoryList('site/files');
			$folderSelectList = Form::buildHTML('select', 'folder', $directoryList, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectList', $folderSelectList);

			$directoryListExcludingCurrent = Bunch::killValue($_SESSION['assetManager']['folder'], $directoryList);
			$directoryListExcludingCurrent = Form::buildHTML('select', 'folder', $directoryListExcludingCurrent, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectListExcludingCurrent', $directoryListExcludingCurrent);


			//Create a folder
			if(Form::submit('createFolder')){
				if(Form::validate(['newFolderName' => '0|32|uri|text|New Folder Name'])){
					$_POST['newFolderName'] = strtolower($_POST['newFolderName']);
					File::createDirectory('site/files' . $_POST['folder'] . '/' . $_POST['newFolderName']);
					Address::go('/admin/asset-manager/site-files');
				}				
			}

			if(Form::submit('deleteFolder')){
				File::removeDirectory('site/files' . $_POST['folder']);
				Address::go('/admin/asset-manager/site-files');
			}


			if(Form::submit('move')){
				$_POST['selectedFiles'] = explode(',', $_POST['selectedFiles']);
				foreach($_POST['selectedFiles'] as $filename){
					$fileSource = 'site/files' . $_SESSION['assetManager']['folder'] . '/' . $filename;
					$fileDestination = 'site/files' . $_POST['moveTo'] . '/' . $filename;
					$thumbsPath = 'site/files' . $_SESSION['assetManager']['folder'] . '/thumbs/';
					if(is_file($fileSource)){
						File::move($fileSource, $fileDestination);
						File::delete($thumbsPath . 's_' . $filename);
						File::delete($thumbsPath . 'm_' . $filename);
						File::delete($thumbsPath . 'l_' . $filename);						
					}
				}
				Address::go('/admin/asset-manager/site-files');
			}

			if(Form::submit('delete')){
				$_POST['selectedFiles'] = explode(',', $_POST['selectedFiles']);
				foreach($_POST['selectedFiles'] as $filename){
					$file = 'site/files' . $_SESSION['assetManager']['folder'] . '/' . $filename;
					$thumbsPath = 'site/files' . $_SESSION['assetManager']['folder'] . '/thumbs/';
					File::delete($file);
					File::delete($thumbsPath . 's_' . $filename);
					File::delete($thumbsPath . 'm_' . $filename);
					File::delete($thumbsPath . 'l_' . $filename);
				}
			}

			if(Form::submit('viewSmall')) $_SESSION['assetManager']['viewStyle'] = 's';
			if(Form::submit('viewMedium')) $_SESSION['assetManager']['viewStyle'] = 'm';
			if(Form::submit('viewLarge')) $_SESSION['assetManager']['viewStyle'] = 'l';
			if(Form::submit('refresh')) Address::go('/admin/asset-manager/site-files');

			if(Form::submit('rename') or Form::submit('copy')){
				if(Form::validate($rules)){
					
					$_POST['fileAction'] = Form::fromHTML($_POST['fileAction']); //convert our characters

					if($_POST['fileAction'] != '' and strlen($_POST['fileAction']) > 4){
						if(!is_array($_POST['selectedFiles'])){
							
							$fileSource = 'site/files' . $_SESSION['assetManager']['folder'] . '/' . $_POST['selectedFiles'];

							if(is_file($fileSource)){

								$oFileExt = File::extension($_POST['selectedFiles']);
								$nFileExt = File::extension($_POST['fileAction']);

								$nFileName = substr($_POST['fileAction'], 0, - strlen($nFileExt));
								$nFileName .= $oFileExt;
								$nFileName = strtolower($nFileName);

								$filePath = 'site/files' . $_SESSION['assetManager']['folder'] . '/';
								$thumbsPath = 'site/files' . $_SESSION['assetManager']['folder'] . '/thumbs/';
								
								if(Form::submit('rename')){
									if(strlen($nFileName) > 4){
										File::rename($fileSource, $filePath . $nFileName);
										File::delete($thumbsPath . 's_' . $_POST['selectedFiles']);
										File::delete($thumbsPath . 'm_' . $_POST['selectedFiles']);
										File::delete($thumbsPath . 'l_' . $_POST['selectedFiles']);
									}
								}
								else if(Form::submit('copy')){
									if(strlen($nFileName) > 4){
										File::copy($fileSource, $filePath . $nFileName);
										File::delete($thumbsPath . 's_' . $_POST['selectedFiles']);
										File::delete($thumbsPath . 'm_' . $_POST['selectedFiles']);
										File::delete($thumbsPath . 'l_' . $_POST['selectedFiles']);
									}
								}
							}
						}
					}
				}
			}

			if(Form::submit('rotateImage')){
				if(!is_array($_POST['selectedFiles'])){
					$filePath = 'site/files' . $_SESSION['assetManager']['folder'] . '/';
					$thumbsPath = 'site/files' . $_SESSION['assetManager']['folder'] . '/thumbs/';
					Image::rotate($filePath . $_POST['selectedFiles'], 270); //rotate clockwise
					File::delete($thumbsPath . 's_' . $_POST['selectedFiles']);
					File::delete($thumbsPath . 'm_' . $_POST['selectedFiles']);
					File::delete($thumbsPath . 'l_' . $_POST['selectedFiles']);
				}
			}

			if(Form::submit('resizeImage')){
				
				if(Form::validate($rules)){

					if($_POST['resizeWidth'] == '' and $_POST['resizeHeight'] == '') Form::setErrors('resizeWidth', 'Resize Width', null, 22);
					else if($_POST['resizeWidth'] != '' and $_POST['resizeHeight'] != '') Form::setErrors('resizeWidth', 'Resize Width', null, 22);
					else{
						if(!is_array($_POST['selectedFiles'])){
							$filename = 'site/files' . $_SESSION['assetManager']['folder'] . '/' . $_POST['selectedFiles'];
							Image::resize($filename, $_POST['resizeWidth'], $_POST['resizeHeight']);
						}
					}
				}
			}

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'site_files.html', 'main/views/admin/modules/asset_manager/');
		}

		/**
		 * AJAX change view style
		 * @return [type] [description]
		 */
		public function changeViewStyle()
		{
			if(isset($_SESSION['primrix']['user'])){ //security check
				if(UserModel::getLoginSession()){ //session time check			
					$vS = Uri::$array[3];
					if($vS == 's') $_SESSION['assetManager']['viewStyle'] = 's';
					else if($vS == 'm') $_SESSION['assetManager']['viewStyle'] = 'm';
					else if($vS == 'l') $_SESSION['assetManager']['viewStyle'] = 'l';
					echo '';
				}
			}
		}

		/**
		 * Ajex method for counting the total files.
		 * @return [type] [description]
		 */
		public function getFileList()
		{
			if(isset($_SESSION['primrix']['user'])){ //security check
				if(UserModel::getLoginSession()){ //session time check
					$folder = 'site/files' . $_SESSION['assetManager']['folder']; //'site/files/images/'
					$fileInfoArray = File::getFileList($folder . '/');
					echo $fileInfoArray['totalFiles'];
				}
			}
		}

		/**
		 * This is used as an AJAX request
		 * @return [type] [description]
		 */
		public function getFiles()
		{
			if(isset($_SESSION['primrix']['user'])){ //security check			
				if(UserModel::getLoginSession()){ //session time check
					if(!isset($_SESSION['assetManager']['viewStyle'])) $_SESSION['assetManager']['viewStyle'] = Def::$primrix->settings->thumbViewStyleDefault;
					$folder = 'site/files' . $_SESSION['assetManager']['folder'] . '/';
					echo AssetsModel::getAssetTiles($folder, $_SESSION['assetManager']['viewStyle'], Uri::$array[3]);
				}
			}
		}

		//AJAX
		public function getDirSize()
		{
			if(isset($_SESSION['lastFileInfo']['totalDirSize_f'])) echo $_SESSION['lastFileInfo']['totalDirSize_f'];
			else echo 0;
		}

		/**
		 * This is actually only used for our dialogue fileViewer as the changing of
		 * directories via AJAX is not supported in our main fileViewer
		 * @return [type] [description]
		 */
		public static function changeDir()
		{
			if(is_dir('site/files' . $_POST['switchTo'])){
				$_SESSION['assetManager']['folder'] = $_POST['switchTo'];
			}
		}



	}

//!EOF : /main/application/controllers/admin/assetmanager.php