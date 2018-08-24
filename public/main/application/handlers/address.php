<?php namespace main\application\handlers;

/**
 * Address
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	
	class Address
	{
		public static function stringToURI($string)
		{
			$string = str_replace('-',' ',$string);
			$string = str_replace('_',' ',$string);
			$string = preg_replace('/[^A-Za-z0-9 \-_]/', '', $string);
			$string = strtolower($string);
			$string = str_replace(' ','-',$string);
			$string = str_replace('----','-',$string);
			$string = str_replace('---','-',$string);
			$string = str_replace('--','-',$string);
			return($string);
		}

		public static function urlMask($url)
		{
			$url = str_replace('/','|',$url);
			$url = str_replace('?','&#63;',$url);
			return($url);
		}

		public static function urlUnmask($url)
		{
			$url = str_replace('|','/',$url);
			$url = str_replace('&#63;','?',$url);            
			return($url);
		}

		public static function encode($string)
		{
			return(base64_encode($string));
		}

		public static function decode($string)
		{
			return(base64_decode($string));
		}

		/**
		 * Simply uses the header function for redirecting the user to the
		 * next page. This has also been kept for other types of headers
		 * which simply needs the second flag set to false and a full header
		 * string can be passed to the header function.
		 * @param string $url location of redirect or header string
		 * @param boolean $location default true as location is used, pass this as false if necessary
		 * @return null
		 */
		public static function go($url, $location = true)
		{
			if($location) header('Location: ' . $url);
			else header($url);
		}

		/**
		 * Simple function to return a formatted URL based on the environment settings
		 * @param string $url a string uri or url
		 * @return string a string url
		 */
		public static function url($url)
		{
			//wow - the things we do to be dynamic - 100% settings driven
			$environment = Def::$primrix->environment;
			$protocol = Def::$primrix->settings->{$environment}->default_protocol;
			$www = Def::$primrix->settings->{$environment}->default_www;
			$domainID = Def::$primrix->settings->{$environment}->domain_id;
			$domainExt = Def::$primrix->settings->{$environment}->domain_ext;
			$domainName = $domainID . $domainExt;			
			
			if(strpos($url, 'http://') === true or strpos($url, 'https://') === true){
				return $domainName . $url;
			}
			else{
				return $protocol . $www . $domainName . $url;
			}
		}
	}

//!EOF : /main/application/handlers/address.php