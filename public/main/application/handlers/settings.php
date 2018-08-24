<?php namespace main\application\handlers;

/**
 * Settings
 *
 * Load our initial environment and configuration settings.
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	class Settings
	{

		public static $env;

		/**
		 * This is available as a static::function
		 * call and settings can be accessed simply by calling the static variable Settings::env. Settings
		 * are copied from /site/config/settings.php as a JSON formatted file. Settings can be addressed
		 * both via an object-> or via an array within the object->array[''] for ease.
		 */
		public static function set()
		{
			$json = file_get_contents(SERVER_BASE . 'site/config/settings.php', null, null, 15);			
			self::$env = json_decode($json);
			self::$env->array = json_decode($json, true);
		}
	}

//!EOF : main/application/handlers/settings.php