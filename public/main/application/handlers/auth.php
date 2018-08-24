<?php namespace main\application\handlers;

/**
 * Auth (as in Authority)
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	class Auth
	{
		/**
		 * Returns a hash of a password using PHP's native password hashing functionality.
		 * They recommend using a length of 255 characters when setting up the db field due to
		 * this possibly getting longer over the course of PHP version increments. Please Note
		 * that this method also checks for a / (forward slash) which can potentially cause
		 * problems should the hash ever be used in a URL as a security feature. This can add
		 * processing time but this will be a random delay period depending upon the luck.
		 * 
		 * @param string $string the string to be hashed
		 * @return string the hashed string
		 */
		public static function hash($string)
		{
			$cost = self::getMaxCost();
			$options = ['cost' => $cost];
			do $hash = password_hash($string, PASSWORD_DEFAULT, $options);
			while(strpos($hash, '/') !== false);
			return $hash;
		}


		/**
		 * This tests the current hardware and returns the highest possible cost without causing
		 * too much hindrance on the hardware. This is used within the cost function of a bcrypt
		 * statement. This was a suggested method from PHP manual when using the native password
		 * hashing functions that come with PHP 5
		 * @return int cost limit based on 0.2 target time
		 */
		public static function getMaxCost()
		{
			$timeTarget = 0.2;
			$cost = 9;

			do{
				$cost++;
				$start = microtime(true);
				password_hash("test", PASSWORD_DEFAULT, ["cost" => $cost]);
				$end = microtime(true);
			} while (($end - $start) < $timeTarget);

			return $cost;
		}


		/**
		 * Alias of the native PHP hashing function. This breaks down a hash and returns
		 * information as an array. "algo", "algoName", "bcrypt" and "options" => "cost"
		 * I can't think why you would need this info but include it in honor :)
		 * @param  string $hash a hashed string
		 * @return array information array
		 */
		public static function getHashInfo($hash)
		{
			return password_get_info($hash);
		}


		/**
		 * Alias function for verifying that the supplied string would match the supplied hash
		 * using PHP's awesome native hashing functions.
		 * @param string $string plain string
		 * @param string $hash the hash to check against
		 * @return boolean pass or fail
		 */
		public static function verifyHash($string, $hash)
		{
			return password_verify($string, $hash);
		}
		
   }

//!EOF : /main/application/handlers/auth.php