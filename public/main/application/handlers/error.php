<?php namespace main\application\handlers;

/**
 * Error
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;

	class Error
	{
		private static $errors = array();

		public static function show()
		{
			//we would preformat the error to be shown on screen.
			
			//pass an array of: (d: destination, s: source)
			//dClass, dMethod, dErrorMsg, sFile (file), sMethod (function), sClass, sType?, sLine, sArgs
			//
			//Idea is to keep things consistent across. Could maybe include the whole debug_backtrace() as
			//additional info
			//
			foreach(self::$errors as $error){
				foreach($error as $key => $errorInfo){
					if($key != 'sArgs') echo $key . ": " . $errorInfo . "<br>\n";
					else{
						echo $key . "<br>\n";
						foreach($errorInfo as $arg){
							echo "-&gt; " . $arg . "<br>\n";
						}
					}
				}
			}
			
		}

		public static function set($callingClass = null, $callingFunc = null, $errorCode = null, $dieOnError = false)
		{

			$trace = debug_backtrace();
			$args = array();
			foreach($trace[1]['args'] as $argGrp){
				if(is_array($argGrp)){
					foreach($argGrp as $arg){
						$args[] = $arg;
					}
				}
				else $args[] = $argGrp;				
			}

			self::$errors[] = [
				'dErrorMsg' => Def::$errorCodes[$errorCode],
				'dClass' => $callingClass,
				'dMethod' => $callingFunc,
				'sFile' => $trace[1]['file'],
				'sClass' => $trace[1]['class'],
				'sMethod' => $trace[1]['function'],
				'sType' => $trace[1]['type'],
				'sArgs' => $args
			];

			self::show();

			if($dieOnError) die('<br>Primrix Terminated!<br>');
		}



		public static function logError()
		{
			//log the error in a file (perhaps serialised)
		}
		 
	}

//!EOF : /main/application/handlers/error.php