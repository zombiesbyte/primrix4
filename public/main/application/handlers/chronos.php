<?php namespace main\application\handlers;

/**
 * Chronos, the Greek god of time. Tick-Tock let's get on with it.
 * 
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	class Chronos
	{
		/**
		 * We can use this function to get information on the difference between two
		 * times in an array of variations from seconds or hours, minutes and seconds.
		 * @param time $time1 the first time (early)
		 * @param time $time2 the second time (late)
		 * @return array (hrs, mins, secs) or hrs, mins and secs only
		 */
		public static function timeDifference($time1, $time2)
		{
			$time1 = strtotime($time1);
			$time2 = strtotime($time2);
			$sec = $time2 - $time1; //in seconds
			$secOnly = $sec;

			list($sec, $min) = self::_timeCalc($sec, 60);
			$minOnly = $min;
			list($min, $hrs) = self::_timeCalc($min, 60);
			$hrsOnly = $hrs;
			list($hrs, $days) = self::_timeCalc($hrs, 24);

			return(array($hrs, $min, $sec, $hrsOnly, $minOnly, $secOnly));         
		}
		
		/**
		 * Required for timeDifference function
		 * @param int $time time in seconds
		 * @param int $thresh division of time
		 * @return array altered time and the newly formed unit
		 */
		protected static function _timeCalc($time, $thresh)
		{
			$new_set = 0;
			while($time >= $thresh){
				$new_set++;
				$time = $time - $thresh;
			}                
			return(array($time, $new_set));
		}


		/**
 		* The method uses the start, current and end datetimes to find a percentage
 		* of remaining time. The decreased param is set to calculate a deduction from
 		* 100%. Setting this flag to false will start the increment from 0% and up.
 		* Please note that there is no cull on the percentage when it reaches 0% or 100%
 		* @param datetime $startTime start time
 		* @param datetime $currentTime current time
 		* @param datetime $endTime end time
 		* @param boolean $decreased default true
 		* @return int
 		*/
		public static function percent($startTime, $currentTime, $endTime, $decreased = true)
		{
			$difArray = self::timeDifference($startTime, $endTime);
			$totalSeconds1 = $difArray[5];

			$difArray = self::timeDifference($startTime, $currentTime);
			$totalSeconds2 = $difArray[5];

			$percent = ($totalSeconds2 / $totalSeconds1) * 100;
			$percent = round($percent, 0, PHP_ROUND_HALF_UP);

			if($decreased) return 100 - $percent;
			else return $percent;
		}

	}

//!EOF : /main/application/handlers/chronos.php