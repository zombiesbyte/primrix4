<?php namespace main\application\handlers;

/**
 * Uri
 *
 * The URI Handler extracts the current URI and places it into segments. This
 * makes accessing URI logic easier and by separating document commands and
 * query strings from structure and path routing.
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;

	class Uri
	{
		
		public static $path = "";
		public static $ctrl = "";
		public static $cArray = array();
		public static $uri = "";

		public static $admin = false;
		public static $query = array();
		public static $array = array();
		public static $fields = array();
		public static $values = array();
		public static $page = "";
		public static $sort = "";		
		public static $show = "";
		public static $limit = "";

		//we can define our own if required
		//public static $custom = array();
		
		//if we define a custom uri segment then we need to add it to this list.
		//The @ symbol is used to indicate that the segment is a value only and
		//should not be included within the URI as a name/value pair
		//
//Agh! damn it - missed the search/ uri problem - need to address this
		public static $uri_schema = [
			'admin' => 	'@variable', //value only
			'array' => 	'@array', 	//value only
			'fields' => 'array',	//name/value pair
			'values' => 'array',	//name/value pair
			'page' => 	'variable',	//name/value pair
			'sort' => 	'variable',	//name/value pair
			'show' => 	'variable',	//name/value pair
			'limit' => 	'variable'	//name/value pair
		];

//we need to add language option ie /uk/ this would be set before the class or
//we need to come up with a way of setting the uri field like /co/uk or even try and use
//subdomains such as uk.mydomain.com / us.mydomain.com / fr.mydomain.com as a way of converting the
//language content. Next we need to think how the fuck we do this as a CMS content edit (maybe duplicate everything as a content map)
//aghgh!
//
		
		/**
		 * Instantiates URI Handler
		 *
		 * The current URI is used to build an object with arrays. Each segment of the URI is analysed for specific
		 * data labels and is handled accordingly. Any segments that do not match are treated and stored within the
		 * URI array (uri_array). Segments which match "search" are removed and the next segment is treated as a
		 * field value pair separated by an ampersand (&). Other segments such as page accept a single value however
* these can be further analysed within scripts if needed. Note that hyphens are replaced with underscores
* ready for directly addressing class/method names within our front controller style framework.
		 *
		 * This function is implemented as a static and is initially set from our front controller via the get() method.
		 *
		 * @example
		 * http://domain.com/one/two/three/search/fieldname&fieldvalue/page/2/sort/asc/show/something/limit/20/number-four
		 * ->uri_admin: 	true/false
		 * ->uri_array:		[0]one, [1]two, [2]three, [3]number_four
		 * ->uri_fields: 	[0]fieldname
		 * ->uri_values: 	[0]fieldvalue
		 * ->uri_page: 		[0]2
		 * ->uri_sort: 		[0]asc
		 * ->uri_show: 		[0]something
		 * ->uri_limit: 	[0]20
		 * 
		 * @param none
		 * @return null
		 */
		
		public static function get()
		{
			//allowable character list for URI segments
			$requested_uri = preg_replace('/[^A-Za-z0-9:\@~.,!&*()_\/\-+=?]/', '', $_SERVER['REQUEST_URI']);
			//xss attack prevention (additional security)
			while(preg_match('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', $requested_uri)){
				$requested_uri = preg_replace('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', '', $requested_uri);
			}
			
			self::$uri = $requested_uri;

			$queryString = strpos($requested_uri, '?');

			if($queryString !== false){
				self::$query = array(); //reset this each time
				$uriVsQuery = explode('?', $requested_uri);
				$requested_uri = $uriVsQuery[0];
				for($n = 1; $n < count($uriVsQuery); $n++){
					self::$query[] = $uriVsQuery[$n];
				}
			}

			self::$array = explode('/', $requested_uri);

			self::uriSort();

			return null;

		}


		/**
		 * Iterate through our URI segments
		 * @return null
		 */
			
		private static function uriSort()
		{
			//find a list of routes that are regarded as administrator use
			//this list would by-pass site active states
			$adminRoutesArray = explode("¦", Def::$primrix->routes->admin);

			//some temporary arrays
			$search_fields = array();
			$search_values = array();
			$sort_array = "";
			$search_pagination = "";
			$search_show = "";
			$limit = "";
			$uri_seg = array();
			//$custom

			//lets use our defined routes to
			//define our path and ctrl variables			
			$rArray = Routes::get();

			self::$path = $rArray[0];
			self::$ctrl = $rArray[1];
			$routeArray = explode('/', $rArray[2]);

			self::$array = Bunch::killKey(0, self::$array);
			$routeArray = array_slice($routeArray, 1);

			$total_uri = count(self::$array);
			for($n = 1; $n <= $total_uri; $n++)
			{
				if(in_array(self::$array[$n], $adminRoutesArray))
				{
					if($n == 1) self::$admin = true;
				}

				if(self::$array[$n] == "sort"){
					$sort_array = self::$array[$n+1];
					self::$array[$n+1] = "";
				}
				else if(self::$array[$n] == "page"){
					$search_pagination = intval(self::$array[$n+1], 10);
					self::$array[$n+1] = "";
				}
				else if(self::$array[$n] == "show"){
					$search_show = self::$array[$n+1];
					self::$array[$n+1] = "";
				}
				else if(self::$array[$n] == "search"){
					$search_string = self::$array[$n+1];
					$search_string_array = explode('&', $search_string);
					$total_search_strings = count($search_string_array);
					for($i = 0; $i < $total_search_strings; $i+=2){
						$search_fields[] = $search_string_array[$i];
						$search_values[] = $search_string_array[$i+1];
						self::$array[$n+1] = "";
					}
				}
				else if(self::$array[$n] == "limit"){
					$limit = self::$array[$n+1];
					self::$array[$n+1] = "";
				}
				
				//add your own URI filters if required
				
				else{
					$uri_seg[] = self::$array[$n];
				}
			}
			
			self::$array = "";
			self::$array = array();
			foreach($uri_seg as $seg){
				if($seg != "") self::$array[] = $seg;
			}

			$i = 0;
			//lets remove the matched route from the URI segments array
			if(count(self::$array) > 0){
				for($n = 0; $n < count($routeArray); $n++){
					if($routeArray[$n] == self::$array[$n]) $i++;
				}
			}

			//create the controller array as the uri array without the matched routes
			self::$cArray = array_slice(self::$array, $i);
			
			//update our class properties with our sort
			self::$fields = $search_fields;
			self::$values = $search_values;
			self::$page = $search_pagination;
			self::$sort = $sort_array;
			self::$show = $search_show;
			self::$limit = $limit;
			//self::custom = $custom;

			//We make the first three elements of the uri_array available
			//if they haven't already been set so that our controller
			//can address them for routing purposes we'll also update our
			//uri array to do the same.
			if(!isset(self::$cArray[0])) self::$cArray[0] = "";
			if(!isset(self::$cArray[1])) self::$cArray[1] = "";
			if(!isset(self::$array[0])) self::$array[0] = "";
			if(!isset(self::$array[1])) self::$array[1] = "";
			

			//If there is no page segment then we need to make this a
			//default of 1 so that we have something to use
			if(self::$page == "" or self::$page <= 0) self::$page = 1;

			return null;
		}

		/**
		 * If we need to rebuild the URI using its current information
		 * then we can use this method. We can also pass any modifications
		 * which will overwrite the current data. This is great for pagination
		 * @param  array $mods modify any URI segment
		 * @return string returns a formatted string
		 */
		public static function rebuild($mods = array())
		{
			$newURI = "";
			if(is_array($mods)){
				foreach(self::$uri_schema as $uriSeg => $type){
					if($type == 'variable'){
						if(isset($mods[$uriSeg])) $newURI .= $uriSeg . '/' . $mods[$uriSeg] . '/';
						else if(self::$$uriSeg != "") $newURI .= $uriSeg . '/' . self::$$uriSeg . '/';						
					}
					else if($type == 'array'){
						if(isset($mods[$uriSeg])){
							foreach($mods[$uriSeg] as $modified){
								$newURI .= $uriSeg . '/' . $modified . '/';
							}
						}						
						else{
							foreach(self::$$uriSeg as $seg){
								if($seg != "") $newURI .= $uriSeg . '/' . $seg . '/';
							}
						}
					}
					if($type == '@variable'){
						if(isset($mods[$uriSeg])) $newURI .= $uriSeg . '/' . $mods[$uriSeg] . '/';
						else if(self::$$uriSeg != "") $newURI .= self::$$uriSeg . '/';
					}
					else if($type == '@array'){
						if(isset($mods[$uriSeg])){
								foreach($mods[$uriSeg] as $modified){
									$newURI .= $modified . '/';
								}
						}
						else{
							foreach(self::$$uriSeg as $seg){
								if($seg != "") $newURI .= $seg . '/';
							}
						}
					}
				}
			}
			return $newURI;
		}

		public static function arrayShift()
		{
			print_r(self::$array);
			self::$array = array_shift(self::$array);
		}

		/**
		 * Convert a uri-with-dashes to UriWithDashes class format
		 * @param string to reformat
		 * @return formatted string
		 */
		public static function toClass($classStr)
		{
			$classStr = str_replace('-', ' ', $classStr);
			$classStr = ucwords($classStr);
			$classStr = str_replace(' ', '', $classStr);
			return $classStr;
		}

		/**
		 * Alias of toClass
		 * @param string to reformat
		 * @return formatted string
		 */
		public static function toMethod($methodStr)
		{
			return self::toClass($methodStr);
		}

		public static function matchUrl($url2Match, $strict = false)
		{
			if($strict){
				$urlFull = implode('/', self::$array);
				if(substr($urlFull, -1) != '/') $urlFull .= '/';
				if(substr($url2Match, -1) != '/') $url2Match .= '/';
				if($url2Match == $urlFull) return true;
				else return false;
			}
			else{
				$url2MatchArray = explode('/', $url2Match);
				$c = count($url2MatchArray);
				$matched = 0;
				
				for($n = 1; $n < $c; $n++){
					if(isset(self::$array[$n-1])){
						if($url2MatchArray[$n] == self::$array[$n-1]) $matched++;
					}
				}
				if($matched == ($c -1)) return true;
				else return false;
			}
		}

		public static function determine($dbp)
		{
			if(Def::$primrix->settings->site_active == 'true') $live = true;
			else $live = false;

			if(!self::$admin){
				if($dbp != null and $dbp['active'] == 'n' and !isset($_SESSION['primrix']['user'])) return false;
				else if(!$live and !isset($_SESSION['primrix']['user'])) return false;
				else return true;
			}
			else return true;
		}
	}

//!EOF : main/application/handlers/uri.php