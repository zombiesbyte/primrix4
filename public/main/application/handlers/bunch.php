<?php namespace main\application\handlers;

/**
 * Bunch alias of array type handler (couldn't call this class array due it
 * being a system word. Bunch has a set of static methods that perform various
 * array based tasks.
 * 
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	class Bunch
	{
		/**
		 * killKey using a two dimensional array finds the $keyID supplied
		 * and removes it from the array while maintaining other keys
		 * @param $keyID a key within the supplied array
		 * @param $fullArray the original array
		 * @return array
		 */
		public static function killKey($keyID, $fullArray)
		{
			$tempArray = array();
			foreach(array_keys($fullArray) as $key){                
				if($key != $keyID) $tempArray[$key] = $fullArray[$key];
			}
			return $tempArray;    
		}

		/**
		 * killValue single dimensional array finds the $killVal supplied
		 * and removes it from the array.
		 * @param $killVal a value within the supplied array
		 * @param $fullArray the original array
		 * @return array
		 */
		public static function killValue($killVal, $fullArray)
		{
			$tempArray = array();
			foreach($fullArray as $value){                
				if($value != $killVal) $tempArray[] = $value;
			}
			return $tempArray;    
		}

		/**
		 * Find and return the first key of an array
		 * @param array 
		 * @return string
		 */
		public static function firstKey($array)
		{
			if(is_array($array)){
				return(array_shift(array_keys($array)));
			}
		}


		/**
		 * Find and return the last key of an array
		 * @param array
		 * @return string
		 */
		public static function lastKey($array)
		{
			if(is_array($array)){
				return(array_pop(array_keys($array)));
			}
		}
		

		/**
		 * Returns an array sorted by value length
		 * @param array $strArray the array to sort
		 * @param  string $order asc|desc
		 * @return array
		 */
		public static function sortValues($strArray, $order = "asc")
		{
			$strLengths = array();
			for($n = 0; $n < count($strArray); $n++){
				$strLengths[] = strlen($strArray[$n]);
			}
			
			if(strtolower($order) == "asc") asort($strLengths, SORT_NUMERIC); 
			else arsort($strLengths, SORT_NUMERIC);
			
			$newStrArray = array();
			foreach($strLengths as $strIndex => $str_len){
				$newStrArray[] = $strArray[$strIndex];
			}            
			
			return $newStrArray;            
		}


		/**
		 * This is always helpful to have handy. The method accepts a csv or an
		 * array and makes sure that we are always dealing with an array.
		 * @param mixed $data an array or a string
		 * @param string $sv separator value such as comma in a csv
		 * @return array
		 */
		public static function asArray($data = "", $sv = ',', $assocPairing = false)
		{
			$array = array();

			if($sv == "\n"){
				$data = str_replace("\r", '', $data);
				$data = str_replace('&NewLine;', "\n", $data);
			}

			if($data != ""){
				if(!is_array($data)){
					if(!$assocPairing and $sv !== false) $array = explode($sv, $data);
					else if($sv === false) $array[] = $data;
					else {
						$tempArray = explode($sv, $data);
						for($n = 0; $n < count($tempArray); $n += 2){
							$array[$tempArray[$n]] = $tempArray[$n+1];
						}
					}
				}
				else{
					foreach($data as $field => $value){
						if(is_array($value)) $value = implode($sv, $value);
						$array[$field] = $value;
					}
				}
			}
			
			return $array;
		}


		/**
		 * Clean array performs a check for empty elements and removes
		 * them from the array. This rewrites the array so count and keys
		 * will change if blanks exist.
		 * @param array the array to clean
		 * @return array a cleaned array
		 */
		public static function clean($array1)
		{
			$cleaned = array();
			if(is_array($array1)){
				foreach($array1 as $field1 => $value1){
					if(!is_array($value1)) if($value1 != "") $cleaned[$field1] = $value1;
					else if(!empty($value1)){						
						foreach($value1 as $field2 => $value2){
							if(!is_array($value2)) if($value2 != "") $cleaned[$field1][$field2] = $value2;
							else if(!empty($value2)){
								foreach($value2 as $field3 => $value3){
									if(!is_array($value3)) if($value3 != "") $cleaned[$field1][$field2][$field3] = $value3;
									else if(!empty($value3)){
										echo "clean function within /main/application/handlers/bunch.php can only handle 3 dimensional arrays, please extend this method to support further depth";
									}
								}
							}
						}
					}
				}
			}
			return $cleaned;
		}


	}

//!EOF : /main/application/handlers/bunch.php