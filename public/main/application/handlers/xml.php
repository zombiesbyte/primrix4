<?php namespace main\application\handlers;

/**
 * XML
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	use \SimpleXmlElement;
	use \SimpleXmlIterator;
	
	class XML
	{

		public static function newObj()
		{
			$root = "<" . "?xml version='1.0'?><root></root>";
			return new SimpleXMLElement($root);
			//$root = '<?xml version="1.0" encoding="UTF-8"? >';
			//return new \stdClass;
			//return new SimpleXMLElement($root, NULL, false);
		}

		public static function formatOutput($obj)
		{
			$dom = dom_import_simplexml($obj)->ownerDocument;
			$dom->formatOutput = true;
			return $dom->saveXML();
		}

		/**
		 * toArray converts an xml string and returns an array. Thanks
		 * to ratfactor at gmail dot com from php.net for making this
		 * publicly available. This is a slightly modified version.
		 * @param string $xml string XML
		 * @param boolean $convertFromHTML if true then html characters are converted
		 * @return array XML as an array
		 */
		public static function toArray($xml, $convertFromHTML = true)
		{
			if($convertFromHTML) $xml = Form::fromHTML($xml);
			$sxi = new SimpleXmlIterator($xml, null);
			return self::keyIterator($sxi);
		}

		/**
		 * Required method for toArray. This cycles through
		 * the keys of the xml recessively
		 * @param obj $sxi the SimpleXmlIterator object
		 * @return array XML as an array
		 */
		public static function keyIterator($sxi)
		{
			$xmlArray = array();
			for($sxi->rewind(); $sxi->valid(); $sxi->next()) {
				if(!array_key_exists($sxi->key(), $xmlArray)) $xmlArray[$sxi->key()] = array();
				if($sxi->hasChildren()) $xmlArray[$sxi->key()][] = self::keyIterator($sxi->current());
				else $xmlArray[$sxi->key()][] = strval($sxi->current());
			}
			return $xmlArray;
		}

		public static function save($obj, $filepath)
		{
			$sxml = new SimpleXmlElement($obj);
			return $sxml->asXml($filepath);
		}

		public static function initObj()
		{
			$root = "<" . "?xml version='1.0'?><root></root>";
			//return new SimpleXMLElement($root, NULL, false);
			return new SimpleXMLElement($root);
			//return simplexml_load_string($root);
		}

	}

//!EOF : /main/application/handlers/xml.php