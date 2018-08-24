<?php namespace site\config;

	
/**
 * Def[initions]
 *
 * The definitions file holds the core configurations such as database connections,
 * file paths, domain information and other environment settings
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\handlers\Routes;
	use \main\application\handlers\DB;
	use \main\application\handlers\Form;

	class Def
	{

		public static $primrix; //holds our global settings
		public static $ruleSet = array();
		public static $ruleSetDesc = array();
		public static $errorCodes = array();
		public static $errorFormats = array();

		public static function get()
		{
			self::_getConfig();
			self::_getSettings();

			/**
			 * settings.php notes
			 * [environment]
			 * The environment setting holds the current state of the project
		 	 * We deliberately left out "testing" and "staging" due to their
		 	 * relation with "development" and "production" environments.
			 *
			 * [debug]
			 * For development and testing environments only. This loads a
			 * helper to display debug information.
			 *
			 * [timezone]
			 * Server timezone: GMT (Default)
			 * 
			 * [line_end]
			 * line_end: "\\r\\n" for windows
			 * line_end: "\\n" for linux
			 * line_end: "\\r" for macintosh
			 *
			 * [database][default]
			 * The named default connection i.e. "connection1"
			 * found in: [database][development|production][connection1]
			 *
			 * [routes][admin]
			 * This is the single named URI segment which would be regarded
			 * as authority access and should not be effected by site active
			 * states. This list can be added to by using the ¦ (broken pipe
			 * aka broken vertical bar) which can be accessed on a standard
			 * QWERTY keyboard by holding down Ctrl+Alt and pressing the key
			 * left of the number 1. Some keyboards do not have access to
			 * this key and should either copy and paste the above character
			 * or access it via the character code insertion tool ASCII 116.
			 * 
			 */
		}

		public static function setRoutes()
		{
			//lets define our stereotypical routes. The first rule has the greatest power

			//Primrix custom controllers
			//Routes::set('/test', 'site', 'site'); //site/application/controllers/site for some reason this steals focus from admin - please check
			//
			//I setup a class called test in admin and when I tried to get to it it used this routing instead?
			
			//Primrix default controllers (/main/application/controllers/...)
			Routes::set('/primrix-ajax', 'main', 'ajax'); //this is our ajax handling route
			Routes::set('/admin', 'main', 'admin'); //this is used for Primrix CMS
			Routes::set('/', 'site', 'site'); //this is our default generic

		}

		private static function _getConfig()
		{
			ini_set("max_execution_time", "240"); 		//Maximum execution time of each script, in seconds
			ini_set("max_input_time", "240"); 			//Maximum amount of time each script may spend parsing request data
			ini_set("memory_limit", "128M"); 			//Maximum amount of memory a script may consume
			ini_set("post_max_size", "64M"); 
			ini_set("upload_max_filesize", "32M");
		}

		private static function _getSettings()
		{
			$pathToSettings = str_replace("\\", "/", __DIR__);
			$json = file_get_contents($pathToSettings . '/settings.php', null, null, 15);
			self::$primrix = json_decode($json); //read in the JSON data as an object
			self::$primrix->array = json_decode($json, true); //make the data also available as an array
			
			//lets set some dynamic data
			self::$primrix->datetime->sdate = date('Y-m-d');
			self::$primrix->datetime->stime = date('H:i:s');
			self::$primrix->array['datetime']['sdate'] = date('Y-m-d');
			self::$primrix->array['datetime']['stime'] = date('H:i:s');
		}

		public static function addSettings($setArray)
		{
			if(is_array($setArray)){
				foreach($setArray as $key => $value){
					self::$primrix->settings->{$key} = $value;
					self::$primrix->array['settings'][$key] = $value;
				}
			}
		}

		public static function appendDBSettings()
		{
			if(!isset($_SESSION['primrix']['settings'])){
				$db = new DB;
				$db->orderby("`id` ASC");
				$db->query('primrix_settings');
				while($row = $db->fetch()){
					$row['name'] = Form::fromHTML($row['name']);
					self::addSettings([$row['name'] => $row['value']]);
				}
				return true;
			}
			return false;
		}

		/**
		 * Sets the character sets up from a predetermined list of primaries and presets
		 * to be used throughout the validation process of forms and alike.
		 * @return null
		 */
		public static function setCharSets()
		{
			//primary rules
			self::$ruleSet['a'] = 		'a-z';			//alpha lower case			
			self::$ruleSet['A'] = 		'A-Z';			//alpha upper case
			self::$ruleSet['0'] = 		'0-9';			//numeric
			self::$ruleSet['sp'] = 		' ';			//space
			self::$ruleSet['dt'] = 		'.';			//dot
			self::$ruleSet['ds'] = 		"\-";			//dash
			self::$ruleSet['us'] = 		'_';			//underscore
			self::$ruleSet['at'] = 		'@';			//at sign
			self::$ruleSet['rb'] = 		'()';			//round brackets
			self::$ruleSet['cb'] = 		'{}';			//curly braces
			self::$ruleSet['sb'] = 		'[\]';			//square brackets
			self::$ruleSet['ab'] = 		'<>';			//angle brackets - security concern!
			self::$ruleSet['fs'] = 		"\/";			//forward slash
			self::$ruleSet['bs'] = 		"\\";			//backward slash
			self::$ruleSet['ex'] = 		'!';			//exclamation mark
			self::$ruleSet['sc'] = 		';';			//semicolon   
			self::$ruleSet['am'] = 		'&';			//ampersand
			self::$ruleSet['ca'] = 		',';			//comma
			self::$ruleSet['co'] = 		':';			//colon
			self::$ruleSet['sy'] = 		'~\*';			//symbols
			self::$ruleSet['pr'] = 		'%';			//percentage
			self::$ruleSet['hs'] = 		'#';			//hash -none uri safe
			self::$ruleSet['pp'] = 		'|';			//pipe -none uri safe
			self::$ruleSet['ct'] = 		'^';			//caret
			self::$ruleSet['pl'] = 		'+';			//plus
			self::$ruleSet['qm'] = 		'?';			//question mark -within a uri this will always be handled as a query string
			self::$ruleSet['eq'] = 		'=';			//equals sign
			self::$ruleSet['gv'] = 		'`';			//grave
			self::$ruleSet['dq'] = 		"\"";			//double quote
			self::$ruleSet['sq'] = 		"\'";			//single quote
			self::$ruleSet['nl'] = 		"\n";			//newline
			self::$ruleSet['cr'] = 		"\r";			//carriage return
			self::$ruleSet['hex'] = 	'A-Fa-f0-9'; 	//hexadecimal
			self::$ruleSet['hup'] = 	'A-F0-9';		//hexadecimal upper case
			self::$ruleSet['haf'] = 	'A-F';			//hexalpha upper case
			self::$ruleSet['cash'] = 	'$£€';			//cash symbols
			self::$ruleSet['none'] = 	'';				//no validation - security concern!
			
			//The description for each entity above
			self::$ruleSetDesc['a'] = 		'lower case letters'; //a-z
			self::$ruleSetDesc['A'] = 		'upper case letters'; //A-Z
			self::$ruleSetDesc['0'] = 		'numbers'; //'0-9
			self::$ruleSetDesc['sp'] = 		'space'; //space
			self::$ruleSetDesc['dt'] = 		'period'; //.
			self::$ruleSetDesc['ds'] = 		'dash'; //-
			self::$ruleSetDesc['us'] = 		'underscore'; //_
			self::$ruleSetDesc['at'] = 		'at symbol'; //@
			self::$ruleSetDesc['rb'] = 		'round brackets'; //()
			self::$ruleSetDesc['cb'] = 		'curly brackets'; //{}
			self::$ruleSetDesc['sb'] = 		'square brackets'; //[]
			self::$ruleSetDesc['ab'] = 		'angle brackets'; //<>
			self::$ruleSetDesc['fs'] = 		'forward slash'; ///
			self::$ruleSetDesc['bs'] = 		'back slash'; //\
			self::$ruleSetDesc['ex'] = 		'exclamation mark'; //!
			self::$ruleSetDesc['sc'] = 		'semicolon'; //;
			self::$ruleSetDesc['am'] = 		'ampersand'; //&
			self::$ruleSetDesc['ca'] = 		'comma'; //,
			self::$ruleSetDesc['co'] = 		'colon'; //:
			self::$ruleSetDesc['sy'] = 		'tide and asterisk'; //~\*
			self::$ruleSetDesc['pr'] = 		'percentage'; //%
			self::$ruleSetDesc['hs'] = 		'hash'; //#
			self::$ruleSetDesc['pp'] = 		'pipe'; //|
			self::$ruleSetDesc['ct'] = 		'caret'; //^
			self::$ruleSetDesc['pl'] = 		'plus sign'; //+
			self::$ruleSetDesc['qm'] = 		'question mark'; //?
			self::$ruleSetDesc['eq'] = 		'equals sign'; //=
			self::$ruleSetDesc['gv'] = 		'grave'; //`
			self::$ruleSetDesc['dq'] = 		'double quote'; //"
			self::$ruleSetDesc['sq'] = 		'single quote'; //'
			self::$ruleSetDesc['nl'] = 		'newline'; //\n
			self::$ruleSetDesc['cr'] = 		'carriage return'; //\r
			self::$ruleSetDesc['hex'] = 	'upper and lowercase hexadecimal'; //A-Fa-f0-9
			self::$ruleSetDesc['hup'] = 	'upper case hexadecimal'; //A-F0-9
			self::$ruleSetDesc['haf'] = 	'upper case hex letters (A-F)'; //A-F
			self::$ruleSetDesc['cash'] = 	'dollar sign, pound sign, euro sign'; //$£€
			self::$ruleSetDesc['none'] = 	''; //no validation

			//please note: adding our own presets will not be checked for errors so we need to make sure
			//that the presets are using only the available ruleset defined above the primary rules. 
			self::$ruleSet['presets'] = [
				'url' => 			['a','A','0','co','sc','at','sy','pr','hs','dt','ca','ex','rb','us','fs','pl','ds','eq','qm','pp'],
				'uri' => 			['a','A','0','ds','us'],
				'path' => 			['a','A','0','ds','us','fs'],
				'filepath' => 		['a','A','0','dt','ds','us','fs'],
				'csvfilepath' =>	['a','A','0','dt','ds','us','fs','ca'],
				'varname' =>		['a','A','0','us'],
				'username' => 		['a','A','0','dt','ds','us'],
				'filename' => 		['a','A','0','dt','ds','us'],
				'csvfilename' =>	['a','A','0','dt','ds','us','ca'],
				'usernamesp' => 	['a','A','0','dt','ds','us', 'sp'],
				'finance' => 		['0','dt','cash'],
				'basic' => 			['a','A','0','ds','sp','dt','fs','am','ca'],
				'rtf' => 			['a','A','0','ds','sp','dt','rb','fs','ex','am','co','ca','eq','qm','nl','cr'],
				'cookie' => 		['a','A','0','ds','sp','dt','fs','am','ca'],
				'phone' => 			['0','sp','dt','ds','pl'],
				'email' => 			['a','A','0','dt','ds','us','at','rb','ex','sc','am','ca','co','sy','pr','hs','pp','pl','qm'],
				'password' => 		['a','A','0','sp','dt','ds','us','at','rb','cb','sb','fs','bs','ex','sc','am','ca','co','sy','pr','hs','pp','ct','pl','qm','eq','gv','dq','sq','cash'],
				'cryptic' => 		['A','0','co','at','sy','dt','ca','ex','rb','us','ds','pl','eq'],
				'hex' => 			['hex'],
				'hexhash' =>		['hex','hs'],
				'upalphanum' => 	['A','0'],
				'loalphanum' => 	['a','0'],
				'alphanum' =>		['A','a','0'],
				'alpha' =>			['A','a'],
				'num' =>			['0'], //alias
				'numds' =>			['0','ds'],
				'none' =>			['none'] //alias
			];

			/*$ruleSet['alphanum'] =     'A-Za-z0-9';
			$ruleSet['numeric'] =        '0-9';
			$ruleSet['numerical_list'] = '0-9\n';
			$ruleSet['alphanumsp'] =     'A-Za-z0-9 ';
			$ruleSet['username'] =       'A-Za-z0-9.\-_';
			$ruleSet['variable'] =       'A-Za-z0-9_';
			$ruleSet['basictext'] =      'A-Za-z0-9 .\-_';
			$ruleSet['metatext'] =       'A-Za-z0-9 .\-_|;:,@#£~?=+[&*()!{}';
			$ruleSet['keywords'] =       'A-Za-z0-9 .\-_,';
			$ruleSet['keywords_nl'] =    'A-Za-z0-9 .\-_\n\r';
			$ruleSet['uri'] =            'A-Za-z0-9\-_';
			$ruleSet['urisp'] =          'A-Za-z0-9\-_ ';
			$ruleSet['alphanumunder'] =  'A-Za-z0-9_';
			$ruleSet['url'] =            'A-Za-z0-9\-_\/.?=+#';            
			$ruleSet['advancedtext'] =   'A-Za-z0-9 ,.\-_;:$%|^\n\r@#£~?=+[&*()\/\\\\!{}\"\'`\]';
			$ruleSet['telephone'] =      '0-9 .\-+[()]';
			$ruleSet['financial'] =      '0-9 .\-';
			$ruleSet['datetime'] =       '0-9 \-:';
			$ruleSet['filename'] =       'A-Za-z0-9.\-_()';
			$ruleSet['wysiwyg'] =        'A-Za-z0-9  ,.\-_;:$%|^\n\r@#£~?=+[&*()\/\\\\!{}\"\'`\\>]]';
			$ruleSet['hex'] =            '0-9A-Fa-f#';
			$ruleSet['novalidation'] =   '';*/			

		}

		public static function setErrorFormat()
		{
			self::$errorFormats = [
				'label' => 'labelError',
				'errorTagL' => '<div class="error">',
				'errorTagR' => '</div>'
			];
		}

		/**
		 * A mix of error messages used throughout Primrix
		 * This is a new concept (to me) but if feels a little cleaner and provides a easy way to reword
		 * Future improvements could be a 2 char country code array to include other languages
		 * @return null
		 */
		public static function setErrorCodes()
		{
			self::$errorCodes = [
				//form handling errors
				0   => 'Character set not found.',
				1   => '*Error number not in use',
				2   => 'This field is too short.',
				3   => 'This field is too long.',
				4   => 'This field contains characters which are not allowed. Only the following may be used:<br><br>',
				5   => 'This field requires a minimum selection of ',
				6   => 'This field requires a maximum selection of ',
				7   => 'The email address supplied cannot be validated, please specify an alternative.',
				8   => 'The email address supplied cannot be found within our records, please use your registered email address.',
				9   => 'The fields do not match, please type carefully',
				10  => 'The field requires a selection',
				11  => 'Maximum file size exceeded. Please keep your file size to a maximum of ',
				12  => 'Maximum Server file size exceeded. Please keep your file size to a maximum of ',
				13  => 'Warning: File upload has been interrupted. Please try again.',
				14  => 'Warning: No file has been selected for upload! Please click browse to find a file.',
				15  => 'Server failed to write to temporary directory. Please try again, if this issue persists then please contact us.',
				16  => 'Server failed to write the file. Please try again, if this issue persists then please contact us.',
				17  => 'The file type which was uploaded is not supported. Please only use the specified file types for uploading.',
				18  => 'The file already exists, please delete the existing file before trying to upload a new version',
				19  => 'There was a problem uploading the file, please try again, if this issue persists then please contact us.',
				20  => 'The Captcha code was incorrect, please try again.',
				21  => 'The Captcha solution was incorrect, please try again.',
				22  => 'The width or the height should be supplied',
				23  => 'The name provided already exists, please try a different name',
				//user account specific
				50  => 'The request to reset your password was unsuccessful, please request a new password reset',
				51  => 'Username and/or Password incorrect',
				52  => 'Thank you, your new password has been reset. You can now login using your new details',
				53  => 'The email address is not valid, please provide an alternative email address',
				54  => 'The username provided has already been taken, please try an alternative username',
				//Primrix method errors
				100 => 'Trying to pass an array instead of a string.',
				101 => 'There is no route defined for the current URI! i.e. Routes::set(\'/\', \'main\', \'default\') is missing',
				102 => 'Thank you, an email has been sent to your email address. Please click the link within this email to reset 
						your password. If you do not receive an email then please check your spam folders',
				103 => 'Something went wrong! The email we tried to send you could not be sent by our server. This could be that
						your email address is not recognised or we could be having some server trouble. Please try again later. If
						you continue to receive this error message then please contact us regarding this problem'
			];
		}		
	}



/*
//Definitions    


//system
define('DEFAULT_CLASSES', 'main/application/classes/default/');
define('HANDLERS', 'main/application/handlers/');
define('CONTROLLERS', 'main/application/controllers/');

//admin
define('ADMIN_CLASSES', 'main/application/classes/admin/');
define('ADMIN_VIEWS','main/core-views/admin/');
define('ADMIN_ASSETS','main/core-assets/');
define('ADMIN_MODEL','main/application/models/');
define('ADMIN_PLUGINS','main/application/plugins/');

//site
define('SITE_CLASSES', 'site/application/classes/');
define('SITE_VIEWS','site/views/');
define('SITE_MODELS','site/application/models/');
define('SITE_PLUGINS','site/application/plugins/');

define('USER_DIR_PATH_1','/site/images/');
define('USER_DIR_PATH_2','/site/documents/');
define('USER_DIR_PATH_3','/site/email/');
define('USER_DIR_PATH_4','/site/menu/');
define('USER_DIR_PATH_5','/site/eshop/');
define('USER_DIR_PATH_6','/site/brand_logos/');
define('USER_DIR_PATH_7','/site/promotional/');
define('USER_DIR_PATH_8','');
define('USER_DIR_PATH_9','');
define('USER_DIR_PATH_10','');

define('USER_DIR_NAME_1','Site Images');
define('USER_DIR_NAME_2','Site Documents');
define('USER_DIR_NAME_3','Email Files');
define('USER_DIR_NAME_4','Site Menu');
define('USER_DIR_NAME_5','eShop Images');
define('USER_DIR_NAME_6','Brand Logos');
define('USER_DIR_NAME_7','Promotional');
define('USER_DIR_NAME_8','');
define('USER_DIR_NAME_9','');
define('USER_DIR_NAME_10','');    

define('USER_DIR_DESCRIPTION_1','This directory will contain all of your images used within the structure of your website (example use http://www.' . DOMAIN . '/site/images/filename.jpg)');
define('USER_DIR_DESCRIPTION_2','This directory holds all of your documents and other files that you offer for your visitors to download (example use http://www.' . DOMAIN . '/site/documents/file.pdf)');
define('USER_DIR_DESCRIPTION_3','This directory can be used to upload artwork for newsletters or email graphics such as signatures (example use http://www.' . DOMAIN . '/site/email/sig.jpg)');
define('USER_DIR_DESCRIPTION_4','If your site supports an image driven menu structure then all menu graphics will be available from here');
define('USER_DIR_DESCRIPTION_5','This directory is used if you have an eShop feature available (example use http://www.' . DOMAIN . '/site/eshop/stock.jpg)');
define('USER_DIR_DESCRIPTION_6','This is where all of your eShop product brand logos are stored');
define('USER_DIR_DESCRIPTION_7','This directory contains promotional adverts and banners');
define('USER_DIR_DESCRIPTION_8','');
define('USER_DIR_DESCRIPTION_9','');
define('USER_DIR_DESCRIPTION_10','');
	
//other
//FPDF
define('FPDF_VERSION','1.6');
define('FPDF_FONTPATH', SERVER_BASE . SITE_DIR . 'main/core-assets/font/');

//Admin Captcha (for Primrix)
define('ADMIN_CAPTCHA_IMG_WIDTH', 175);
define('ADMIN_CAPTCHA_IMG_HEIGHT', 45);
define('ADMIN_CAPTCHA_FONT_FILE', 'gdfonts/caveman.gdf');
define('ADMIN_CAPTCHA_FONT_SIZE', 24);
define('ADMIN_CAPTCHA_FONT_COLOUR', "#0172E5");
define('ADMIN_CAPTCHA_SHOW_ARCS', false);
define('ADMIN_CAPTCHA_ARC_COLOUR', "#0172E5");
define('ADMIN_CAPTCHA_AUDIO_PATH', SERVER_BASE . ADMIN_ASSETS . 'captcha/audio/');

//Captcha
define('CAPTCHA_IMG_WIDTH', 175);
define('CAPTCHA_IMG_HEIGHT', 45);
define('CAPTCHA_FONT_FILE', 'gdfonts/caveman.gdf');
define('CAPTCHA_FONT_SIZE', 24);
define('CAPTCHA_FONT_COLOUR', "#444836");
define('CAPTCHA_SHOW_ARCS', false);
define('CAPTCHA_ARC_COLOUR', "#444836");
define('CAPTCHA_AUDIO_PATH', SERVER_BASE . ADMIN_ASSETS . 'captcha/audio/');

//Highlight Current Page on Site Menu (layer 1 only) [use button_current as css]
define('CPH', true);*/

//!EOF : /site/config/definitions.php