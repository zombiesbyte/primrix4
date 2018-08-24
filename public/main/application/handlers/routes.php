<?php namespace main\application\handlers;

/**
 * Routes
 *
 * The routes files defines the URI route between default and name page
 * controllers. This helps us to handle all possible scenarios.
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	//use /

	class Routes
	{
		public static $uri;
		public static $regRoutes = array();

		/**
		 * Used to define a route path. We match the provided URI pattern
		 * and provide a root path and controller path to where we wish to
		 * handle the page. A route cannot define the controller itself as
		 * this is handled by our front controller which has a default of
		 * index if the class is not specified. 
		 * @param string $uri the basic pattern to match i.e. /something/etc
		 * @param string $rootPath i.e. main or site (others can be used to extend Primrix)
		 * @param string $ctrlPath i.e. default or admin etc
		 */
		public static function set($uri, $rootPath, $ctrlPath)
		{
			self::$regRoutes[$uri] = $rootPath . ',' . $ctrlPath;
		}

		/**
		 * From the routes set above return the root path and the controller path
		 * @return array [0]:root path, [1]:controller path, [2]:the route matched
		 */
		public static function get()
		{
			$url = "";
			foreach(Uri::$array as $seg){
				$url .= $seg . '/';
			}

			foreach(self::$regRoutes as $route => $pageController){
				$escapedRoute = str_replace('/', '\/', $route);
				if(preg_match("/" . $escapedRoute . "/", $url)){
					return explode(',', $pageController . ',' . $route);
				}
			}
			
			//if we haven't returned by this stage then there isn't a route that
			//matches. There should always be a route that matches! This would
			//indicate that the rule for Routes::set('/', 'main', 'default');
			//is missing within our route definitions class. DieOnError is true!
			Error::set(get_called_class(), __FUNCTION__, 101, true);
		}
	}

//!EOF : /main/application/handlers/routes.php