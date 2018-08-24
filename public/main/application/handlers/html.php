<?php namespace main\application\handlers;

/**
 * HTML
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;

	class HTML
	{
		//nts: this class should allow the writing of html tags in an abstract way. Allowing the use of already defined variables
		//to merge with predetermined prefixes together with additional options and flags. The problem comes down to these additional
		//attributes as there is an abundance of extras that can be included in html and when you add these complexities to the
		//the growing number of other tags and flags of mix-ins like javascript issues arise to the nature of a tag being too complex in
		//how it is addressed nevermind the procedure in breaking this down and compiling output.
		//
		//Take for instance the image tag. This requires src data which obviously is the core detail of such an element but then goes on to
		//needing a width and height value. This is complete contradiction to the new methods of separating all style from a document. This is
		//proven by the fact that we have width and height attributes in CSS which preside any html image attribute yet exclude the image attribute
		//from the html and we are targeted for our poor html?!
		//
		//Going back to the initial problem, the abstract way in which we define our attributes can become too cumbersome and begin looking too cryptic
		//which results in our original solution becoming unfriendly and completely unusable.
		//
		//Example: IMG:myimage:50:25:alternative text:class:data-src:etc
		//
		//If there was maybe a way to address the templating as an option array of attributes this may work a lot better:
		//
		//Example:
		//{IMG:myimage}
		//{IMG-dim:50,25}
		//{IMG-alt:alternative text}
		//{IMG-cls:class}
		//{IMG-dat:atr,data-src}
		//{IMG-...:etc}
		//
		//This would almost reflect how the properties of an object were set and would be in-line with the way we program. This then becomes a little
		//more readable. The problem then is the processing power needed to loop and bind these iterations on a larger scale as we activate our class on
		//each entry. It may be better to allow for a set number of options in the first example and have an alternative way of defining more complex attributes
		//on an independent solution.
		//
		//idk?
		public static function tags($var)
		{
			//echo $var;
			$html = "";
			$parts = explode(':', $var);

			//\Help::pre($parts);

			$tag = $parts[0];
			$var = Def::$primrix->settings->prefix . $parts[1] . Def::$primrix->settings->suffix;
			//$attr1 = $parts[2];
			//$attr2 = $parts[3];

			//\Help::pre(Doc::$bindings);

			if($tag == 'IMG'){

				$html = "<img src='" . Doc::$bindings[$var] . "' width='' height=''>";

			}

			return $html;

		}
		 
	}

//!EOF : /main/application/handlers/html.php