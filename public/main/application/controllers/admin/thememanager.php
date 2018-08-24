<?php namespace main\application\controllers\admin;

/**
 * ThemeManager
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
	use \main\application\handlers\Upload;

	use \site\config\def;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\ThemeManagerModel;

	use \main\application\models\cms\RightsModel;

	class ThemeManager extends Controller
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
			$env = Def::$primrix->environment;
			$rootPath = Def::$primrix->settings->$env->rootpath;
			$siteDir = Def::$primrix->settings->$env->site_dir;
			$publicDir = Def::$primrix->settings->$env->public;

			$idError = "";

			Form::setData(['file' => '0|0|url|text']);

			ThemeManagerModel::buildThemeTable();

			if(Form::submit('add')) Address::go('/admin/theme-manager/add');

			if(Form::submit('delete')){
				if(isset($_POST['id'])){
					$db = new DB;
					$db->deleteRowByArray('primrix_themes', 'id', $_POST['id']);
				}
				Address::go('/admin/theme-manager');
			}

			if(Form::submit('export')){
				if(!isset($_POST['id'])) $idError = "Please select 1 checkbox to export a theme";
				else if(count($_POST['id']) > 1) $idError = "You cannot export multiple themes, please select only 1 checkbox to export a theme";
				else {
					$db = new DB;
					$db->where("`id` = '{$_POST['id'][0]}'");
					$db->query('primrix_themes');
					$row = $db->fetch();
					$db->close();

					$fileData = "";
					$fileData .= $row['name'] . ",";
					$fileData .= $row['author'] . ",";
					$fileData .= $row['created_at'] . ",";

					$row['colours'] = Form::fromHTML($row['colours']);
					$coloursArray = explode("\n", $row['colours']);
					$colourCSV = "";
					foreach($coloursArray as $colour){
						$colourCSV .= $colour . ',';
					}

					$fileData .= $colourCSV;
					$fileData = Text::groom(',', $fileData);

					$filename = $row['name'] . '.ptm';
					$filepath = $rootPath . $siteDir . 'temp/';
					file_put_contents($filepath . $filename, $fileData);
					File::offerDownload($filepath . $filename);
				}
			}

			if(Form::submit('import')){
				$filename = 'last.ptm';
				$filepath = $rootPath . $siteDir . 'temp/';
				Upload::file('file', $filepath, $filename, 500, ['ptm']);
				Form::validate(['file' => '0|0|url|text']);
				$fileData = file_get_contents($filepath . $filename);
				$fileDataArray = explode(",", $fileData);

				if(count($fileDataArray) == 16){
					$db = new DB;
					$insert['id'] = $db->nextIndex('primrix_themes', 'id');
					$insert['name'] = $fileDataArray[0];
					$insert['author'] = $fileDataArray[1];
					$insert['created_at'] = $fileDataArray[2];
					$insert['updated_at'] = date('Y-m-d H:i:s');
					$insert['updated_by'] = $_SESSION['primrix']['user']['id'];

					$insert['colours'] = "";
					for($n = 3; $n < 17; $n++){
						$insert['colours'] .= Form::toHTML($fileDataArray[$n]) . "\n";
					}
					$insert['colours'] = Text::strip("\n", $insert['colours']);

					$db->insert('primrix_themes', $insert);
					Address::go('/admin/theme-manager');
				}

			}

			if(Form::submit('apply')){
				if(!isset($_POST['id'])) $idError = "Please select 1 checkbox to apply a theme";
				else if(count($_POST['id']) > 1) $idError = "You cannot apply multiple themes, please select only 1 checkbox to apply a theme";
				else {
					$id = $_POST['id'][0];
					$db = new DB;

					$db->select("`colours`");
					$db->where("`id` = '{$id}'");
					$db->query('primrix_themes');
					$row = $db->fetch();
					$db->close();

					$colourArray = explode("\n", $row['colours']);

					$scssDoc = $this->setSCSSConfig($colourArray);

					file_put_contents($rootPath . $siteDir . 'public/main/css/scss/config/_config.scss', $scssDoc);

					$scss = new \scssc();

					$config = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/config/_config.scss');
					$prepare = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/partials/_prepare.scss');
					$core = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/partials/_primrix_core.scss');
					$sundry = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/partials/_primrix_sundry.scss');

					$brand = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/brand.scss');
					$fileview = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/primrix_fileview.scss');
					$forms = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/primrix_forms.scss');
					$login = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/primrix_login.scss');
					$main = file_get_contents($rootPath . $siteDir . 'public/main/css/scss/primrix_main.scss');
					
					$scssBrand = $scss->compile($config . $prepare . $brand);
					file_put_contents($rootPath . $siteDir . 'public/main/css/brand.css', $scssBrand);

					$scssPrimrixFileview = $scss->compile($config . $prepare . $fileview);
					file_put_contents($rootPath . $siteDir . 'public/main/css/primrix_fileview.css', $scssPrimrixFileview);

					$scssPrimrixForms = $scss->compile($config . $prepare . $forms);
					file_put_contents($rootPath . $siteDir . 'public/main/css/primrix_forms.css', $scssPrimrixForms);

					$scssPrimrixLogin = $scss->compile($config . $prepare . $core . $sundry . $login);
					file_put_contents($rootPath . $siteDir . 'public/main/css/primrix_login.css', $scssPrimrixLogin);
					
					$scssPrimrixMain = $scss->compile($config . $prepare . $core . $sundry . $main);
					file_put_contents($rootPath . $siteDir . 'public/main/css/primrix_main.css', $scssPrimrixMain);
					
					Address::go('/admin/theme-manager');
				}
			}

			Doc::bind('idError', $idError);
			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'thememanager.html', 'main/views/admin/modules/primrix_settings/');
		}

		public function add()
		{
			//load current theme colours
			$data = [
				'pBgColour1' => '#464745',
				'pBgColour2' => '#373935',
				'pBgColour3' => '#232422',
				'pFgColour1' => '#D73F22',
				'pFgColour2' => '#FFFFFF',
				'pFtColour1' => '#D73F22',
				'pFt1Link_h' => '#FFFFFF',
				'pFt1Link_d' => '#FFFFFF',
				'pFtColour2' => '#FFFFFF',
				'pToolTipBg' => '#0F0F0F',
				'MenuSelect' => '#D73F22',
				'tableRow_h' => '#191919',
				'pFormBtnUp' => '#D73F22'
			];

			$rules = [
				'name' => '3|32|varname|text',
				'author' => '3|128|basic|text',
				'pBgColour1' => '7|7|hexhash|text',
				'pBgColour2' => '7|7|hexhash|text',
				'pBgColour3' => '7|7|hexhash|text',
				'pFgColour1' => '7|7|hexhash|text',
				'pFgColour2' => '7|7|hexhash|text',
				'pFtColour1' => '7|7|hexhash|text',
				'pFt1Link_h' => '7|7|hexhash|text',
				'pFt1Link_d' => '7|7|hexhash|text',
				'pFtColour2' => '7|7|hexhash|text',
				'pToolTipBg' => '7|7|hexhash|text',
				'MenuSelect' => '7|7|hexhash|text',
				'tableRow_h' => '7|7|hexhash|text',
				'pFormBtnUp' => '7|7|hexhash|text'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_themes', 'id');
					
					$_POST['colours'] = "";
					$_POST['colours'] .= $_POST['pBgColour1'] . "\n";
					$_POST['colours'] .= $_POST['pBgColour2'] . "\n";
					$_POST['colours'] .= $_POST['pBgColour3'] . "\n";
					$_POST['colours'] .= $_POST['pFgColour1'] . "\n";
					$_POST['colours'] .= $_POST['pFgColour2'] . "\n";
					$_POST['colours'] .= $_POST['pFtColour1'] . "\n";
					$_POST['colours'] .= $_POST['pFt1Link_h'] . "\n";
					$_POST['colours'] .= $_POST['pFt1Link_d'] . "\n";
					$_POST['colours'] .= $_POST['pFtColour2'] . "\n";
					$_POST['colours'] .= $_POST['pToolTipBg'] . "\n";
					$_POST['colours'] .= $_POST['MenuSelect'] . "\n";
					$_POST['colours'] .= $_POST['tableRow_h'] . "\n";
					$_POST['colours'] .= $_POST['pFormBtnUp'];

					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_themes', $_POST);
					Address::go('/admin/theme-manager');
				}				
			}

			if(Form::submit('cancel')) Address::go('/admin/theme-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'thememanager_add.html', 'main/views/admin/modules/primrix_settings/');
		}

		public function edit()
		{
			$id = Uri::$array[3];

			$db = new DB;

			$db->where("`id` = '{$id}'");
			$db->query('primrix_themes');
			if($db->numRows() == 1) $row = $db->fetch();
			else Address::go('/admin/theme-manager');
			$db->close();

			$colours = explode("\n", $row['colours']);
			//load current theme colours
			$data = [
				'name' => $row['name'],
				'author' => $row['author'],
				'pBgColour1' => $colours[0],
				'pBgColour2' => $colours[1],
				'pBgColour3' => $colours[2],
				'pFgColour1' => $colours[3],
				'pFgColour2' => $colours[4],
				'pFtColour1' => $colours[5],
				'pFt1Link_h' => $colours[6],
				'pFt1Link_d' => $colours[7],
				'pFtColour2' => $colours[8],
				'pToolTipBg' => $colours[9],
				'MenuSelect' => $colours[10],
				'tableRow_h' => $colours[11],
				'pFormBtnUp' => $colours[12]
			];

			$rules = [
				'name' => '3|32|varname|text',
				'author' => '3|128|basic|text',
				'pBgColour1' => '7|7|hexhash|text',
				'pBgColour2' => '7|7|hexhash|text',
				'pBgColour3' => '7|7|hexhash|text',
				'pFgColour1' => '7|7|hexhash|text',
				'pFgColour2' => '7|7|hexhash|text',
				'pFtColour1' => '7|7|hexhash|text',
				'pFt1Link_h' => '7|7|hexhash|text',
				'pFt1Link_d' => '7|7|hexhash|text',
				'pFtColour2' => '7|7|hexhash|text',
				'pToolTipBg' => '7|7|hexhash|text',
				'MenuSelect' => '7|7|hexhash|text',
				'tableRow_h' => '7|7|hexhash|text',
				'pFormBtnUp' => '7|7|hexhash|text'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){

				if(Form::validate($rules)){
									
					$_POST['colours'] = "";
					$_POST['colours'] .= $_POST['pBgColour1'] . "\n";
					$_POST['colours'] .= $_POST['pBgColour2'] . "\n";
					$_POST['colours'] .= $_POST['pBgColour3'] . "\n";
					$_POST['colours'] .= $_POST['pFgColour1'] . "\n";
					$_POST['colours'] .= $_POST['pFgColour2'] . "\n";
					$_POST['colours'] .= $_POST['pFtColour1'] . "\n";
					$_POST['colours'] .= $_POST['pFt1Link_h'] . "\n";
					$_POST['colours'] .= $_POST['pFt1Link_d'] . "\n";
					$_POST['colours'] .= $_POST['pFtColour2'] . "\n";
					$_POST['colours'] .= $_POST['pToolTipBg'] . "\n";
					$_POST['colours'] .= $_POST['MenuSelect'] . "\n";
					$_POST['colours'] .= $_POST['tableRow_h'] . "\n";
					$_POST['colours'] .= $_POST['pFormBtnUp'];

					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update('primrix_themes', $_POST);
					Address::go('/admin/theme-manager');
				}				
			}

			if(Form::submit('cancel')) Address::go('/admin/theme-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'thememanager_add.html', 'main/views/admin/modules/primrix_settings/');
		}

		public function setSCSSConfig($colourArray)
		{
			$scss = "";

			for($n = 0; $n < count($colourArray); $n++){
				$colourArray[$n] = Form::fromHTML($colourArray[$n]);
			}

			if(count($colourArray) == 13){
				$scss .= "// Main theme variables\n";
				$scss .= "\$pBgColour1: " . $colourArray[0] . "; //The primary background colour i.e. body background colour\n";
				$scss .= "\$pBgColour2: " . $colourArray[1] . "; //The secondary background colour used on highlights, dialogues and boxes\n";
				$scss .= "\$pBgColour3: " . $colourArray[2] . "; //The tertiary background colour used on shadows and other touches\n";
				$scss .= "\$pFgColour1: " . $colourArray[3] . "; //The primary foreground colour i.e. the first line colour\n";
				$scss .= "\$pFgColour2: " . $colourArray[4] . "; //The secondary foreground colour i.e. the second line colour\n";
				$scss .= "\n";
				$scss .= "//font colours\n";
				$scss .= "\$pFtColour1: " . $colourArray[5] . "; //The primary font colour i.e. headings font colour\n";
				$scss .= "\$pFt1Link_h: " . $colourArray[6] . "; //The primary font colour hover\n";
				$scss .= "\$pFt1Link_d: " . $colourArray[7] . "; //The primary font colour active (down)\n";
				$scss .= "\$pFtColour2: " . $colourArray[8] . "; //The secondary font colour i.e paragraphs font colour\n";
				$scss .= "\n";
				$scss .= "\$pToolTipBg: " . $colourArray[9] . "; //The tool tips background colour (95% opacity will be used)\n";
				$scss .= "\$MenuSelect: " . $colourArray[10] . "; //The colour of a menu item when it is selected as currently active\n";
				$scss .= "\$tableRow_h: " . $colourArray[11] . "; //The colour of a hovered row\n";
				$scss .= "\n";
				$scss .= "//forms\n";
				$scss .= "\$pFormBtnUp: " . $colourArray[12] . "; //The colour of our buttons (rest state)\n";
			}
			else {
				$scss .= "// Main theme variables\n";
				$scss .= "\$pBgColour1: #464745; //The primary background colour i.e. body background colour\n";
				$scss .= "\$pBgColour2: #373935; //The secondary background colour used on highlights, dialogues and boxes\n";
				$scss .= "\$pBgColour3: #232422; //The tertiary background colour used on shadows and other touches\n";
				$scss .= "\$pFgColour1: #D73F22; //The primary foreground colour i.e. the first line colour\n";
				$scss .= "\$pFgColour2: #FFFFFF; //The secondary foreground colour i.e. the second line colour\n";
				$scss .= "\n";
				$scss .= "//font colours\n";
				$scss .= "\$pFtColour1: #D73F22; //The primary font colour i.e. headings font colour\n";
				$scss .= "\$pFt1Link_h: #FFFFFF; //The primary font colour hover\n";
				$scss .= "\$pFt1Link_d: #FFFFFF; //The primary font colour active (down)\n";
				$scss .= "\$pFtColour2: #FFFFFF; //The secondary font colour i.e paragraphs font colour\n";
				$scss .= "\n";
				$scss .= "\$pToolTipBg: #0F0F0F; //The tool tips background colour (95% opacity will be used)\n";
				$scss .= "\$MenuSelect: #D73F22; //The colour of a menu item when it is selected as currently active\n";
				$scss .= "\$tableRow_h: #191919; //The colour of a hovered row\n";
				$scss .= "\n";
				$scss .= "//forms\n";
				$scss .= "\$pFormBtnUp: #D73F22; //The colour of our buttons (rest state)\n";
			}
			return $scss;
		}
	}

//!EOF : /main/application/controllers/admin/thememanager.php