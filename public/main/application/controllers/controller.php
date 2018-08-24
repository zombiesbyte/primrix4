<?php namespace main\application\controllers;

/**
 * Controller
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */


	use \main\application\handlers\Uri;
	use \main\application\handlers\Settings;
	use \main\application\handlers\DB;
	use \main\application\handlers\Routes;
	use \main\application\handlers\Doc;
	use \site\config\Def;

	class Controller
	{
		public $db;
		public $uri;
		public $log;
		public $doc;
		public $out;

		public function __construct()
		{
			$this->db = new DB; //create a db instance
			Uri::get(); //initialise our uri handler;
			Def::appendDBSettings();
		}
		
		public function load()
		{
			//lets work with our current uri
			Routes::$uri = Uri::$array;

			//lets store some easy variable references
			$ext = Def::$primrix->settings->ext;

			//check the db for our uri and return a dbp array otherwise null
			if(!$dbp = $this->_dbPageExists(Uri::$cArray)){
				if(Uri::$admin){
					$dbp['active'] = 'y'; //we can't turn off admin area
					$dbp['allow_params'] = 'y'; //we always should allow params
				}
				else $this->error404();
			}

			//lets define a namespace and class string together with a fall-back in cases where our class is actually a method within the default
			//controller or where we are passing params to our default controller
			$classController = Uri::$path . "\\application\\controllers\\" . Uri::$ctrl . "\\" . Uri::toClass(Uri::$cArray[0]);
			$altController = Uri::$path . "\\application\\controllers\\" . Uri::$ctrl . "\\" . Def::$primrix->settings->defaultcontroller;

			//lets determine if the site and particular page we are trying to access is set to an active state or if we need to bypass it because it
			//is regarded as an administration page. This also tests to see if we need by-pass the active state should the session belong to an admin
			if(!Uri::determine($dbp)) $this->unavailable();
			
			//lets create our instance for our new page
			if(class_exists($classController)) $page = new $classController($dbp);
			else if(class_exists($altController)) $page = new $altController($dbp);

			//check that the method exists within the class or if the class is the method otherwise default it to look for index*
			//we also check that allow_param flag is true otherwise a 404 error is produced if the method does not accept params
			if(Uri::$cArray[1] != '' and method_exists($page, Uri::toMethod(Uri::$cArray[1]))){
				if($dbp['allow_params'] or !isset(Uri::$cArray[2])) $page->{Uri::toMethod(Uri::$cArray[1])}($dbp);
				else $this->error404();
			}
			else if(method_exists($page, Uri::toMethod(Uri::$cArray[0]))){
				if($dbp['allow_params'] or Uri::$cArray[1] == '') $page->{Uri::toMethod(Uri::$cArray[0])}($dbp);
				else $this->error404();
			}
			else{
				if($dbp['allow_params'] or Uri::$cArray[1] == '') $page->{Def::$primrix->settings->defaultmethod}($dbp); //index* is always our default
				else $this->error404();
			}

			//* by default index is our default method name but this can be changed via the site/config/settings.php file			
		}

		protected function _dbPageExists($uri)
		{
			if(is_array($uri)){
				$uriString = "/";
				foreach($uri as $seg){
					if($seg != '') $uriString .= $seg . '/';
				}
			}
			else $uriString = $uri;

			$this->db->where("`uri` = '{$uriString}'");
			$this->db->query('primrix_site_pages');

			if($this->db->numRows() >= 1){
				$pageRow = $this->db->fetch();
				$this->db->close();
				return $pageRow;
			}
			else{
				$this->db->close();
				return false;
			}
		}

		public function error404()
		{
			//404 Page Cannot Be Found - The address is either incorrect or the page has
			//been removed from the site (and there is no redirect setup in Primrix).
			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found", true, 404);
			echo "<html><head></head><body>";
			echo "<div style='text-align:center;width:400px;margin:10px auto 0px auto;'>";
			echo "<h1>404 Not Found</h1>";
			echo "<h2>Page Not Found</h2>";
			echo "<p>Please check that you have entered the correct address<br>";
			echo "and that the page you are looking for still exists</p>";
			echo "</div>";
			echo "</body></html>";
			die();
		}
		
		public function unavailable()
		{
			//503 Service Unavailable - The server is currently unavailable (because it is
			//overloaded or down for maintenance). Generally, this is a temporary state.
			header($_SERVER["SERVER_PROTOCOL"] . " 503 Service Unavailable", true, 503);
			echo "<html><head></head><body>";
			echo "<div style='text-align:center;width:400px;margin:10px auto 0px auto;'>";
			echo "<h1>503 Service Unavailable</h1>";
			echo "<h2>This site is temporary unavailable</h2>";
			echo "<p>We are currently updating the website/server and our service<br>";
			echo "is expected to be restored soon. Thank you for your patience</p>";
			echo "</div>";
			echo "</body></html>";
			die();
		}
	}

//!EOF : /main/application/controllers/controller.php