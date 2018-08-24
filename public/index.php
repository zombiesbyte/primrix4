<?php

/**
 * Bootstrap
 *
 * Our journey has to begin somewhere.
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

use \site\config\def;
use \main\application\handlers\User;
use \main\application\handlers\Doc;
use \main\application\handlers\Output;
use \main\application\controllers\Controller;

/**
 * Lets get the session started up
 */
ob_start();
session_start();

//this helps to always know we can address the 
//$_SESSION as an array even if it is empty.
if(!isset($_SESSION)) $_SESSION = array(); 

/**
 * Lets load the autoloader up from Composer
 */
require '/vendor/autoload.php';

//get the default settings
Def::get();
Def::setRoutes();
Def::setCharSets();
Def::setErrorCodes();
Def::setErrorFormat();

//go on our journey
//$page_timer = new Page_Timer; //start page execution timer 

//lets load() our front controller
$controller = new Controller;
$controller->load();

//lets check for any Primrix logins that have expired
//User::checkLoginSession();

Doc::inject();
Output::evaluate(Doc::$doc);
//echo "<pre>";
//print_r($controller);
//echo "</pre>";



//$controller->page->doc->define_var('page_load_time', $page_timer->get_pageloadtime()); //report execution time
//$controller->page->doc->define_settings();

//$controller->page->doc->replace_site_variables(); //replace references built from calling classes
//$controller->page->doc->replace_site_variables(); //again for anything nested

//$controller->output->document($controller->page->doc->document); //(eval present)    

//if(DEBUG_MODE) echo $_SESSION['debugger']['notice']; //if the debugger has been activated
//welcome back

class Help
{
	public static function pre($array, $tag = "")
	{
		echo $tag . "<pre>", print_r($array), "</pre>";
	}
}
		
//!EOF : /index.php