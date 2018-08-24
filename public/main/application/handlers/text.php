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

	class Text
	{


		/**
		 * Method generates a random word based on a Hex system as default.
		 * @param int $length default 10
		 * @param string $charSet from our Def::$ruleSet['presets']
		 * @return string random word
		 */
		public static function randWord($length = 10, $charSet = 'hup')
		{
			$arg = func_get_args();
			if(!isset($arg[0]) or $arg[0] <= 0) $arg[0] = 10;
			$randword = "";

			$chr_list = "";
			foreach(Def::$ruleSet['presets'][$charSet] as $set){
				$chr_list .= Def::$ruleSet[$set];
			}
			
			$chr_list = str_replace('\\', '', $chr_list); //remove any escaped characters

			srand();
			for($n = 0; $n < $length; $n++){
				$pos = rand(0,(strlen($chr_list) - 1));
				$randword .= substr($chr_list, $pos, 1);
			}
			return($randword);
		}

		/**
		 * Creates a teaser (aka snippet, teaser, excerpt, strap, strap-line, puff, hook)
		 * regardless of the chosen name, this accepts content as a string and culls it
		 * to an approximate length adding an eclipse style ... at the end should there be
		 * more text available. The approximation is due to word length forcing the cull
		 * to be done prematurely. With letter kerning and font letter widths not being fixed
		 * this only solves the problem partially. Why can't we go back to using fixed width
		 * fonts in everything? :)
		 * @param string $string string text we wish to cull
		 * @param int $maxLength [description]
		 * @return string
		 */
		public static function teaser($string, $maxLength)
		{
			if($string != ''){
				
				$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
				$string = strip_tags($string);
				$string = stripslashes($string);
				if(strlen($string) > $maxLength){
					while(substr($string, -1) != " " and $maxLength > 0){
						$maxLength--;
						$string = substr($string, 0, $maxLength);
					}
					return(substr($string, 0, ($maxLength - 1)) . '...');
				}
				return($string);
				
			}
			else return($string);
		}

		/**
		 * Removes a string from the end of a supplied string. This is useful
		 * for removing commas from a string that has been build from a loop.
		 * @param string $needle the string to be popped of the end
		 * @param string $haystack the string affected
		 * @return string
		 */
		public static function groom($needle, $haystack){
			$strLength = strlen($needle);
			return substr($haystack, 0, (0 - $strLength));
		}

		public static function strip($needle, $haystack){
			$strLength = strlen($needle);
			while(substr($haystack, (0 - $strLength)) == $needle){
				$haystack = substr($haystack, 0, (0 - $strLength));
			}
			return $haystack;
		}

		function html($string, $decode)
		{
			if($decode == 'html'){
				$string = htmlentities($string, ENT_QUOTES, 'UTF-8');
				$string = str_replace("'","&#039;",$string);
				$string = str_replace('"',"&#034;",$string);
			}
			else{
				$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
				$string = str_replace("&#039;","'",$string);
				$string = str_replace("&#034;",'"',$string);
			}
			return($string);
		}

		/**
		 * Normalise a string to supported common characters. This method
		 * is great for converting filenames and variable names by stripping
		 * nonsense characters that could potentially cause problems.
		 * @param  string $string
		 * @param  string $spaces_to convert spaces to supplied argument
		 * @return string returns a normalised string
		 */
		public static function normalise($string, $spacesTo = '-', $toLower = true)
		{
			$string = str_replace(' ', $spacesTo, $string);
			$string = preg_replace('/[^A-Za-z0-9.\-_()]/', '', $string);
			if($toLower) $string = strtolower($string);
			return($string);
		}

		public static function variable_to_string($variable)
		{
			$string = str_replace('_', ' ', $variable);
			$string = preg_replace('/([A-Z])/', ' $1', $string);
			$string = trim($string);
			$string = ucwords($string);
			return $string;
		}

		// Thanks to my Dad, I found a problem with certain characters being a problem for RSS/XML so the function to
		// convert the html references over to hex was born
		function xml_protection($string)
		{
			$string = str_replace('&ndash;', '&#8211; ',$string); // en dash
			$string = str_replace('&mdash;', '&#8212',$string); //em dash
			$string = str_replace('&iexcl;', '&#161;',$string); //inverted exclamation
			$string = str_replace('&iquest;','&#191;',$string); //inverted question mark
			$string = str_replace('&quot;','&#34;',$string); //quotation mark
			$string = str_replace('&ldquo;','&#8220;',$string); //left double curly quote
			$string = str_replace('&rdquo;','&#8221;',$string); //right double curly quote
			$string = str_replace('&lsquo;','&#8216;',$string); //left single curly quote
			$string = str_replace('&rsquo;','&#8217;',$string); //right single curly quote
			$string = str_replace('&laquo;','&#171;',$string); //guillemets (European-style quotation marks)
			$string = str_replace('&raquo;','&#187;',$string); //guillemets (European-style quotation marks)
			$string = str_replace('&nbsp;','&#160;',$string); //non-breaking space
			$string = str_replace('&amp;','&#38;',$string); //ampersand
			$string = str_replace('&cent;','&#162;',$string); //cent
			$string = str_replace('&copy;','&#169;',$string); //copyright
			$string = str_replace('&divide;','&#247;',$string); //divide
			$string = str_replace('&micro;','&#181',$string); //micron
			$string = str_replace('&middot;','&#183;',$string); //middle dot
			$string = str_replace('&para;','&#182;',$string); //pilcrow (paragraph sign)
			$string = str_replace('&plusmn;','&#177;',$string); //plus/minus
			$string = str_replace('&euro;','&#8364;',$string); //Euro
			$string = str_replace('&pound;','&#163;',$string); //British Pound Sterling
			$string = str_replace('&reg;','&#174;',$string); //registered
			$string = str_replace('&sect;','&#167;',$string); //section
			$string = str_replace('&trade;','&#153;',$string); //trademark
			$string = str_replace('&yen;','&#165;',$string); //Japanese Yen
			$string = str_replace('&aacute;','&#225;',$string); //lower-case "a" with acute accent 
			$string = str_replace('&Aacute;','&#193;',$string); //upper-case "A" with acute accent
			$string = str_replace('&agrave;','&#224;',$string); //lower-case "a" with grave accent 
			$string = str_replace('&Agrave;','&#192;',$string); //upper-case "A" with grave accent
			$string = str_replace('&acirc;','&#226;',$string); //lower-case "a" with circumflex
			$string = str_replace('&Acirc;','&#194;',$string); //upper-case "A" with circumflex
			$string = str_replace('&aring;','&#229;',$string); //lower-case "a" with ring 
			$string = str_replace('&Aring;','&#197;',$string); //upper-case "A" with ring
			$string = str_replace('&atilde;','&#227;',$string); //lower-case "a" with tilde
			$string = str_replace('&Atilde;','&#195;',$string); //upper-case "A" with tilde
			$string = str_replace('&auml;','&#228;',$string); //lower-case "a" with diaeresis/umlaut 
			$string = str_replace('&Auml;','&#196;',$string); //upper-case "A" with diaeresis/umlaut
			$string = str_replace('&aelig;','&#230;',$string); //lower-case "ae" ligature
			$string = str_replace('&AElig;','&#198;',$string); //upper-case "AE" ligature
			$string = str_replace('&ccedil;','&#231;',$string); //lower-case "c" with cedilla
			$string = str_replace('&Ccedil;','&#199;',$string); //upper-case "C" with cedilla
			$string = str_replace('&eacute;','&#233;',$string); //lower-case "e" with acute accent
			$string = str_replace('&Eacute;','&#201;',$string); //upper-case "E" with acute accent
			$string = str_replace('&egrave;','&#232;',$string); //lower-case "e" with grave accent
			$string = str_replace('&Egrave;','&#200;',$string); //upper-case "E" with grave accent
			$string = str_replace('&ecirc;','&#234;',$string); //lower-case "e" with circumflex
			$string = str_replace('&Ecirc;','&#202;',$string); //upper-case "E" with circumflex
			$string = str_replace('&euml;','&#235;',$string); //lower-case "e" with diaeresis/umlaut
			$string = str_replace('&Euml;','&#203;',$string); //upper-case "E" with diaeresis/umlaut
			$string = str_replace('&iacute;','&#237;',$string); //lower-case "i" with acute accent
			$string = str_replace('&Iacute;','&#205;',$string); //upper-case "I" with acute accent
			$string = str_replace('&igrave;','&#236;',$string); //lower-case "i" with grave accent
			$string = str_replace('&Igrave;','&#204;',$string); //upper-case "I" with grave accent
			$string = str_replace('&icirc;','&#238;',$string); //lower-case "i" with circumflex
			$string = str_replace('&Icirc;','&#206;',$string); //upper-case "I" with circumflex
			$string = str_replace('&iuml;','&#239;',$string); //lower-case "i" with diaeresis/umlaut
			$string = str_replace('&Iuml;','&#207;',$string); //upper-case "I" with diaeresis/umlaut
			$string = str_replace('&ntilde;','&#241;',$string); //lower-case "n" with tilde
			$string = str_replace('&Ntilde;','&#209;',$string); //upper-case "N" with tilde
			$string = str_replace('&oacute;','&#243;',$string); //lower-case "o" with acute accent
			$string = str_replace('&Oacute;','&#211;',$string); //upper-case "O" with acute accent
			$string = str_replace('&ograve;','&#242;',$string); //lower-case "o" with grave accent
			$string = str_replace('&Ograve;','&#210;',$string); //upper-case "O" with grave accent
			$string = str_replace('&ocirc;','&#244;',$string); //lower-case "o" with circumflex
			$string = str_replace('&Ocirc;','&#212;',$string); //upper-case "O" with circumflex
			$string = str_replace('&oslash;','&#248;',$string); //lower-case "o" with slash
			$string = str_replace('&Oslash;','&#216;',$string); //upper-case "O" with slash
			$string = str_replace('&otilde;','&#245;',$string); //lower-case "o" with tilde
			$string = str_replace('&Otilde;','&#213;',$string); //upper-case "O" with tilde
			$string = str_replace('&ouml;','&#246;',$string); //lower-case "o" with diaeresis/umlaut
			$string = str_replace('&Ouml;','&#214;',$string); //upper-case "O" with diaeresis/umlaut
			$string = str_replace('&szlig;','&#223;',$string); //ess-tsett
			$string = str_replace('&uacute;','&#250;',$string); //lower-case "u" with acute accent
			$string = str_replace('&Uacute;','&#218;',$string); //upper-case "U" with acute accent
			$string = str_replace('&ugrave;','&#249;',$string); //lower-case "u" with grave accent
			$string = str_replace('&Ugrave;','&#217;',$string); //upper-case "U" with grave accent
			$string = str_replace('&ucirc;','&#251;',$string); //lower-case "u" with circumflex
			$string = str_replace('&Ucirc;','&#219;',$string); //upper-case "U" with circumflex
			$string = str_replace('&uuml;','&#252;',$string); //lower-case "u" with diaeresis/umlaut
			$string = str_replace('&Uuml;','&#220;',$string); //upper-case "U" with diaeresis/umlaut
			$string = str_replace('&yuml;','&#255;',$string); //lower-case "y" with diaeresis/umlaut
			return($string);
		}
	}

//!EOF : /main/application/handlers/text.php