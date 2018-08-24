<?php namespace main\application\handlers;

/**
 * Doc
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\handlers\HTML;
	use \site\config\Def;

	class Doc
	{
		public static $doc;
		public static $bindings = array();

		public static function view($filename, $path = 'site/views/')
		{
			if(is_file($path . $filename)){
				$fileContents = file_get_contents($path . $filename);
				self::$doc = $fileContents;
			}
			else echo "<br>Error: view not found<br>";
		}

		public static function viewPart($placeholder, $filename, $path = 'site/views/')
		{
			if(is_file($path . $filename)){
				$fileContents = file_get_contents($path . $filename);
				self::$doc = preg_replace('/' . Def::$primrix->settings->prefix . $placeholder . Def::$primrix->settings->suffix . '/', $fileContents, self::$doc);
			}
			else echo "<br>Error: view not found<br>";
		}

		/**
		 * There are 3 ways to bind a document variable. [1] Simply sending a key/value pair to
		 * the method will bind the value to the key and append the default prefix and suffix.
		 * [2] send an 1 dimensional array of variable names which will all be bound with the
		 * same value as supplied as the second param. [3] send a 2 dimensional array of key
		 * and value pairs, this would ignore the value param.
		 * @param string/array $var string or an 1 or 2 dimensional array
		 * @param string $value string value
		 * @param string $prefix a string to prefix the bind key
		 * @param string $suffix a string to suffix the bind key
		 * @return null
		 */
		public static function bind($var, $value = '', $prefix = '', $suffix = '')
		{
			if($prefix != "") $prefix = $prefix . ':';
			if($suffix != ""){
				if($suffix == '*') $suffix = ':' . '.*?';
				else $suffix = ':' . $suffix;
			}

			if(!is_array($var)){
				$var = Def::$primrix->settings->prefix . $prefix . $var . $suffix . Def::$primrix->settings->suffix;
				self::$bindings[$var] = $value;
			}
			else{
				if(empty(array_values($var))){
					foreach($var as $v){
						$var = Def::$primrix->settings->prefix . $prefix . $v . $suffix . Def::$primrix->settings->suffix;
						self::$bindings[$var] = $value;
					}
				}
				else{
					foreach($var as $v => $val){
						$var = Def::$primrix->settings->prefix . $prefix . $v . $suffix . Def::$primrix->settings->suffix;
						self::$bindings[$var] = $val;						
					}
				}
			}
		}

		public static function inject()
		{
			//we reverse the order of our bindings because our general replacements come before our post
			//replacements which needs to be the reverse so that our post replacements aren't overwritten
			self::$bindings = array_reverse(self::$bindings);
			foreach(self::$bindings as $var => $value){
				self::$doc = preg_replace('/' . $var . '/', $value, self::$doc);
			}
		}

		/*public static function premitureInject()
		{
			self::$bindings = array_reverse(self::$bindings);
			foreach(self::$bindings as $var => $value){
				self::$doc = preg_replace('/' . $var . '/', $value, self::$doc);
			}
		}*/
		 
	}

//!EOF : /main/application/handlers/doc.php