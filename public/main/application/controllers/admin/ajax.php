<?php namespace main\application\controllers\admin;

/**
 * Ajax
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
	use \main\application\handlers\DB;
	use \main\application\handlers\Form;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Captcha;
	use \main\application\handlers\Text;
	use \main\application\handlers\Mail;
	use \main\application\handlers\Upload;
	use \main\application\handlers\Order;


	use \main\application\models\admin\UserModel;

	class Ajax extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();
		}

		public function index()
		{
			echo "Hello World";
		}

		public function test()
		{
			echo "testing";
		}

		public static function toggleactive()
		{
			$db = new DB;
			if($db->toggleValue($_POST['table'], 'id', $_POST['id'], 'active', ['y','n'])) return;
			else echo 'fail';
		}

		public static function order()
		{
			$db = new DB;
			if(isset($_POST['csv']) and $_POST['csv'] == true){
				$tableArray = explode(',', $_POST['table']);
				$idArray = explode(',', $_POST['id']);
				$count = count($tableArray);
				if($count == count($idArray)){
					for($n = 0; $n < $count; $n++){
						if($tableArray[$n] != '' and $idArray[$n] != ''){
							Order::order($tableArray[$n], $idArray[$n], $_POST['order']);
						}
					}
					return;
				}
				else return 'count match error: tables do not match id count';
			}
			else{
				if(Order::order($_POST['table'], $_POST['id'], $_POST['order'])) return;
				else return 'fail';
			}
						
		}

		public static function uploadProgress()
		{
			sleep(2);
			Upload::file('file', '/site/files/images/');
			//$current = $_SESSION['upload_progress_form1']["bytes_processed"];
    		//$total = $_SESSION['upload_progress_form1']["content_length"];
    		//echo $current < $total ? ceil($current / $total * 100) : 100;
/*

		[upload_progress_form1] => Array
        (
            [start_time] => 1408959753
            [content_length] => 71793
            [bytes_processed] => 71793
            [done] => 1
            [files] => Array
                (
                    [0] => Array
                        (
                            [field_name] => file
                            [name] => Dal--0014.jpg
                            [tmp_name] => C:\x\tmp\phpC209.tmp
                            [error] => 0
                            [done] => 1
                            [start_time] => 1408959753
                            [bytes_processed] => 71386
                        )

                )

        )*/			
		}
	}

//!EOF : /main/application/controllers/admin/ajax.php