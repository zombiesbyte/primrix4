<?php namespace main\application\handlers;

/**
 * Output
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	class Output
	{
		public static function evaluate($code)
		{
			eval('?' . '>' . $code);
		}
	}

//!EOF : /main/application/handlers/output.php