<?php namespace main\application\controllers\admin;

/**
 * Test
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
	use \main\application\handlers\XML;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\ContentCentreModel;

	use \main\application\models\cms\RightsModel;

	use \site\config\def;

	class Test extends Controller
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

			$db = new DB;

			if(isset(Uri::$array[2])){
				$cIndex = Uri::$array[2];
				$direction = Uri::$array[3];

				$db->where("`a` = '1' AND `b` = '1'");
				$db->reorderField('primrix_test1', 'c', $cIndex, $direction);
				//$db->rebuildOrders('primrix_test1', 'c');
				$db->close();
			}
			
			echo "ID\t\tA\t\tB\t\tC<br>";
			$db->where("`a` = '1' AND `b` = '1'");
			$db->orderBy("`id` ASC");
			$db->query('primrix_test1');
			while($row = $db->fetch()){
				echo $row['id'] . "\t\t" . $row['a'] . "\t\t" .$row['b'] . "\t\t" .$row['c'] . "\t\t" . "<a href='/admin/test/{$row['c']}/up'>up</a> | <a href='/admin/test/{$row['c']}/down'>down</a><br>";
			}

			Doc::view('index.html', 'main/views/admin/');

		}

	}

//!EOF : /main/application/controllers/admin/test.php