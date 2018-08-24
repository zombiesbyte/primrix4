<?php namespace main\application\controllers\generic;

/**
 * DefaultController
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\controllers\Controller;
	
	use \main\application\handlers\Uri;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Form;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Captcha;
	use \main\application\handlers\Text;
	
	class DefaultController extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();
			echo "<pre>", print_r($dbp), "</pre>";

		}

		public function index()
		{
			echo "Default Controller";
		}

	}

//!EOF : /main/application/controllers/generic/defaultcontroller.php