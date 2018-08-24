<?php namespace main\application\handlers;

/**
 * Form
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
		
	class Form
	{
		public static $post;
		private static $_htmlCharTable;
		private static $errors = array();

		/**
		 * Submit function is very simple but helps to keep the class code looking a little
		 * cleaner. Simply checks if the submit value is set in 2 ways.
		 * @param string $submitButton i.e. $_POST['submit']
		 * @return boolean
		 */
		public static function submit($submitButton)
		{
			if(isset($_POST[$submitButton]) or isset($_POST[$submitButton . '_x'])) return true;
			else return false;
		}


		/**
		 * There are about 70 different ways to inject an illegal character from an entry or input element this
		 * is an attempt to complete a security check on all potentials to replace left angle bracket (probably
		 * the most unsafe character to have floating about) The literal < character is not removed as this
		 * will be caught when htmlentities is called. This method deals with the $_POST data directly.
		 * @param array $data the data array to check
		 * @return null
		*/		
		protected static function _clean_data($data)
		{
			foreach($data as $assoc1 => $var1){
				
				if(!is_array($var1)){                
					while(preg_match('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', $var1)){
						$newData[$assoc1] = preg_replace('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', '', $var1);
						$var1 = $newData[$assoc1]; //this is updated for the while check to exit on false
						$_POST[$assoc1] = $var1;
					}
				}
				else if(is_array($var1)){
					//second level checking
					foreach($var1 as $assoc2 => $var2){
						if(!is_array($var2)){
							while(preg_match('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', $var2)){
								$newData[$assoc2] = preg_replace('/(%3C|&#x3C|&#60|PA==|&lt|&#|&#x|\x)/i', '', $var2);
								$var2 = $newData[$assoc2]; //this is updated for the while check to exit on false
								$_POST[$assoc1][$assoc2] = $var2;
							}
						}
					}
				}
			}
		}


	  	/**
	  	 * charFilter is used to return a replacement string based on the character sets provided in the
	  	 * static array $ruleSet (set by setCharSets() method). This method checks against the primary
	  	 * rules list, the named presets or a user defined regex. The setHTML flag (if true) then converts
	  	 * all html special characters to their corresponding html code before the clean version of the
	  	 * passed string is returned.
	  	 * 
	  	 * @param mixed $charSet primary rule/named preset/regex
	  	 * @param string $string only strings can be passed
	  	 * @return string a cleaned version of the string.
	  	 */
		public static function charFilter($charSet, $string, $toHTML = false)
		{
			if($charSet != 'none'){
				if(!is_array($string)){
					if(is_array($charSet)){
						if($charSet[0] == 'regex') $string = preg_replace('/[^' . $charSet[1] . ']/', '', $string);
						else{
							$rulesString = "";
							foreach($charSet as $set){
								if(isset(Def::$ruleSet[$set])) $rulesString .= Def::$ruleSet[$set];
								else Error::set(get_called_class(), __FUNCTION__, 0);
							}
							$string = preg_replace('/[^' . $rulesString . ']/', '', $string);
						}
					}
					else{
						if(isset(Def::$ruleSet[$charSet]))	$string = preg_replace('/[^' . Def::$ruleSet[$charSet] . ']/', '', $string);
						else{
							if(isset(Def::$ruleSet['presets'][$charSet])){
								$rulesString = "";
								foreach(Def::$ruleSet['presets'][$charSet] as $set){
									$rulesString .= Def::$ruleSet[$set]; //we don't test against this as the presets should always be correct!
								}
								$string = preg_replace('/[^' . $rulesString . ']/', '', $string);
							}
							else Error::set(get_called_class(), __FUNCTION__, 0);
						}
					}

					if($toHTML) $string = self::toHTML($string);
					return $string;
				}
				else Error::set(get_called_class(), __FUNCTION__, 100);
			}
			else {
				if($toHTML) $string = self::toHTML($string);
				return $string;
			}
		}

		/**
		 * This is used to return the characters allowed within a validation rule set.
		 * This can be used to aid the user in appropriate characters to use within a field
		 * and is included within an error message when a field entry is rejected because of
		 * illegal characters.
		 * @param  mixed $charSet primary rule/named preset/regex
		 * @return string human readable validation set
		 */
		public static function getAllowableChars($charSet)
		{
			$allowed = "";
			if($charSet != 'none'){
				if(is_array($charSet)){
					if($charSet[0] == 'regex') $allowed = $charSet[1];
					else{
						$rulesString = "";
						foreach($charSet as $set){
							if(isset(Def::$ruleSet[$set])) $rulesString .= Def::$ruleSetDesc[$set] . ', ';							
						}
						$allowed = $rulesString;
					}
				}
				else{
					if(isset(Def::$ruleSet[$charSet])) $allowed = Def::$ruleSetDesc[$charSet];
					else{
						if(isset(Def::$ruleSet['presets'][$charSet])){
							$rulesString = "";
							foreach(Def::$ruleSet['presets'][$charSet] as $set){
								$rulesString .= Def::$ruleSetDesc[$set] . ', ';
							}
							$allowed = $rulesString;
						}
					}
				}
			}
			else $allowed = "All characters are permitted";

			return self::toHTML(stripslashes($allowed));
		}


		/**
		 * Method extracts the rules array ready to be processed
		 * min: the minimum amount of characters or selections
		 * max: the maximum amount of characters or selections
		 * set: the character set allowed within this field
		 * typ: the type of field text|select|check|radio|password
		 * ffn: the field friendly name used within error messages
		 * arc: the flag to automatically remove illegal characters
		 * @param array $rules pipe seperated flags
		 * @return array $fields an array of settings
		 */
		private static function extractRules($rules)
		{
			$fields = array();

			foreach($rules as $field => $rule)
			{
				$ruleArray = explode('|', $rule);
				if(isset($ruleArray[0])) $fields[$field]['min'] = $ruleArray[0];
				if(isset($ruleArray[1])) $fields[$field]['max'] = $ruleArray[1];
				if(isset($ruleArray[2])) $fields[$field]['set'] = $ruleArray[2];
				if(isset($ruleArray[3])) $fields[$field]['typ'] = $ruleArray[3];
				if(isset($ruleArray[4])) $fields[$field]['ffn'] = $ruleArray[4];
				if(isset($ruleArray[5])) $fields[$field]['arc'] = $ruleArray[5];
			}

			foreach($fields as $field => $value){
				$friendly_field = str_replace('_', ' ', $field);
				$friendly_field = ucwords($friendly_field);
				//defaults should there be any rules missing
				if(!isset($fields[$field]['min'])) $fields[$field]['min'] = 0;
				if(!isset($fields[$field]['max'])) $fields[$field]['max'] = 0;
				if(!isset($fields[$field]['set'])) $fields[$field]['set'] = 'basic';
				if(!isset($fields[$field]['typ'])) $fields[$field]['typ'] = 'text';
				if(!isset($fields[$field]['ffn'])) $fields[$field]['ffn'] = $friendly_field;
				if(!isset($fields[$field]['arc'])) $fields[$field]['arc'] = false;
			}

			return $fields;
		}


		/**
		 * Set the data within a $_POST array. Uses a dataArray to populate the $_POST array
		 * @param
		 * @return
		 */
		public static function setData($rules, $dataArray = array(), $fields = null)
		{
			if($fields == null) $fields = self::extractRules($rules);
			
			foreach($fields as $field => $value){
				
				//we do this first to clear out any possible left over variables which are not targeted
				//by our logic. Any variables in bind are simply overwritten during the logic process.
				Doc::bind($field, '', 'form'); //remove all {form:$field} tags
				Doc::bind($field, '', 'label'); //remove all {label:$field} tags
				Doc::bind($field, '', 'error'); //remove all {error:$field} tags
				Doc::bind($field, '', 'form', '*'); //remove all {form:$field:[various]} tags (select)
				Doc::bind($field, '', 'allowed'); //remove all {allowed:$field} tags

				if(isset($dataArray[$field])){
					
					if(is_array($dataArray[$field])){
						foreach($dataArray[$field] as $key => $value2){

							if($fields[$field]['typ'] == 'text' or $fields[$field]['typ'] == 'textarea'){
								Doc::bind($field, $dataArray[$field][$key], 'form');
							}
							else if($fields[$field]['typ'] == 'select'){
								Doc::bind($field, ' selected', 'form', $dataArray[$field][$key]);
							}
							else if($fields[$field]['typ'] == 'check'  or $fields[$field]['typ'] == 'radio'){
								Doc::bind($field, ' checked', 'form', $dataArray[$field][$key]);
							}
							else if($fields[$field]['typ'] == 'password'){
								//do nothing
							}
						}
					}
					else{
						if($fields[$field]['typ'] == 'text' or $fields[$field]['typ'] == 'textarea'){
							Doc::bind($field, $dataArray[$field], 'form');
						}
						else if($fields[$field]['typ'] == 'select'){
							Doc::bind($field, ' selected', 'form', $dataArray[$field]);
						}
						else if($fields[$field]['typ'] == 'check'  or $fields[$field]['typ'] == 'radio'){
							Doc::bind($field, ' checked', 'form', $dataArray[$field]);
						}
						else if($fields[$field]['typ'] == 'password'){
							//do nothing
						}						
					}

					Doc::bind($field, self::getAllowableChars($fields[$field]['set']), 'allowed');
				}
			}		
		}


		/**
		 * Validate the $_POST input based on the rules supplied. Please note that the character checking only
		 * extends as far as the first dimension of a $_POST array. i.e. $_POST['field'] is checked while
		 * $_POST['field1']['field2'] values are not. This is because it is assumed that a second tier data is
		 * established manually from either hard coding it or pulling it from a trusted data source.
		 * @return boolean if validation passed then true else false
		 */
		public static function validate($rules)
		{
			self::_clean_data($_POST);

			$fields = self::extractRules($rules);
			
			foreach($fields as $field => $value){

				//if the post field is not set then set it
				if(!isset($_POST[$field])) $_POST[$field] = "";

				//lets check our lengths
				if($fields[$field]['typ'] == 'text' or $fields[$field]['typ'] == 'textarea' or $fields[$field]['typ'] == 'password'){
					if($fields[$field]['min'] > 0 and strlen($_POST[$field]) < $fields[$field]['min']) self::setErrors($field, $fields[$field]['ffn'], '', 2);
					if($fields[$field]['max'] > 0 and strlen($_POST[$field]) > $fields[$field]['max']) self::setErrors($field, $fields[$field]['ffn'], '', 3);
				}
				else if($fields[$field]['typ'] == 'select' or $fields[$field]['typ'] == 'check' or $fields[$field]['typ'] == 'radio'){
					if(is_array($_POST[$field])){
						if(!empty($_POST[$field])) $_POST[$field] = Bunch::clean($_POST[$field]); //lets clear out any blank values as these will interfere with our selections
						
						if(($fields[$field]['min'] == $fields[$field]['max']) and ($fields[$field]['min'] == 1 and $_POST[$field] == "")) self::setErrors($field, $fields[$field]['ffn'], '', 10);
						else if(count($_POST[$field]) < $fields[$field]['min']) self::setErrors($field, $fields[$field]['ffn'], $fields[$field]['min'], 5);
						else if($fields[$field]['max'] > 0 and count($_POST[$field]) > $fields[$field]['max']) self::setErrors($field, $fields[$field]['ffn'], $fields[$field]['max'], 6);
					}
					else{
						if(($fields[$field]['min'] == $fields[$field]['max']) and ($fields[$field]['min'] == 1 and $_POST[$field] == "")) self::setErrors($field, $fields[$field]['ffn'], '', 10);
						else if($fields[$field]['min'] > 1 or ($fields[$field]['min'] == 1 and $_POST[$field] == "")) self::setErrors($field, $fields[$field]['ffn'], $fields[$field]['min'], 5);
						else if($fields[$field]['min'] > 1 and $fields[$field]['max'] > 1) self::setErrors($field, $fields[$field]['ffn'], $fields[$field]['min'], 5);
					}
				}

				if(!is_array($_POST[$field])){
					if($fields[$field]['arc']) $_POST[$field] = self::charFilter($fields[$field]['set'], $_POST[$field]); //auto remove characters (arc)
					else if($_POST[$field] != self::charFilter($fields[$field]['set'], $_POST[$field])) self::setErrors($field, $fields[$field]['ffn'], self::getAllowableChars($fields[$field]['set']) . '<br><br>', 4);
					if($fields[$field]['typ'] != 'password') $_POST[$field] = self::toHTML($_POST[$field]); //lets convert all to html special character entities for security except passwords
				}
			}

			self::setData('', $_POST, $fields); //lets repopulate the form with our post data (we've converted all special characters so this will be fine)

			if(self::getErrors()){
				self::injectErrors();
				return false;
			}
			else return true;
		}


		public static function setErrors($field, $friendly = null, $errorMsg, $errorCode = null)
		{
			if($errorCode == null){
				self::$errors[][$field] = [
					'errorMsg' => $errorMsg,
					'friendly' => $friendly
				];
			}
			else{
				self::$errors[][$field] = [
					'errorMsg' => Def::$errorCodes[$errorCode] . $errorMsg,
					'friendly' => $friendly
				];
			}
		}

		public static function getErrors()
		{
			if(!empty(self::$errors)){
				return true;
			}
			else return false;
		}

		public static function injectErrors($formatArray = array())
		{
			$formatArray = $formatArray + Def::$errorFormats;

			foreach(self::$errors as $errors){
				foreach($errors as $field => $errorArray){
					Doc::bind($field, $formatArray['label'], $prefix = "label");
					Doc::bind($field, $formatArray['errorTagL'] . $errorArray['friendly'] . ': ' . $errorArray['errorMsg'] . $formatArray['errorTagR'], $prefix = "error");
				}
			}
		}

		/**
		 * This will convert all appropriate characters to their html entities
		 * @param string $string
		 * @return string 
		 */
		public static function toHTML($string)
		{
			return htmlentities($string, ENT_QUOTES | ENT_HTML5, "UTF-8");
		}

		public static function fromHTML($string)
		{
			return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, "UTF-8");
		}

		/**
		 * Checks a given ip or record of a domain name and returns true if 
		 * @param string $domOrIP Domain name or IP address (email can be supplied but the domain is extracted)
		 * @param string $recordType the record type to lookup i.e. A (A record) or MX (MX record)
		 * @return boolean
		 */
		public static function lookupDNS($domOrIP, $recordType = 'A')
		{
			if($domOrIP != ""){
				if(strpos($domOrIP, '@')){
					$emailArray = explode('@', $domOrIP);
					return checkdnsrr(idn_to_ascii($emailArray[1]), $recordType);
				}
				else return checkdnsrr(idn_to_ascii($domOrIP), $recordType);
			}
		}

		public static function buildHTML($element, $fieldname, $varArray, $dataArray, $varArrayAsKeys = false)
		{
			$html = "";

			if($element == 'select'){
				foreach($varArray as $value => $label){
					
					$selected = "";

					if(isset($_POST[$fieldname])){
						if($varArrayAsKeys){
							if($_POST[$fieldname] == $label) $selected = ' selected';
						}
						else{
							if($_POST[$fieldname] == $value) $selected = ' selected';
						}
					}
					else if(isset($dataArray[$fieldname])){
						if($varArrayAsKeys){
							if($dataArray[$fieldname] == $label) $selected = ' selected';
						}
						else{
							if($dataArray[$fieldname] == $value) $selected = ' selected';
						}
					}

					if($varArrayAsKeys) $html .= "<option value=\"{$label}\"{$selected}>{$label}</option>\n";
					else $html .= "<option value=\"{$value}\"{$selected}>{$label}</option>\n";
				}
			}
			else if($element == 'check'){
				foreach($varArray as $value => $label){
					
					$selected = "";

					if(isset($_POST[$fieldname])){
						foreach($_POST[$fieldname] as $dataVal){
							if($varArrayAsKeys){
								if($dataVal == $label) $selected = ' checked';
							}
							else{
								if($dataVal == $value) $selected = ' checked';
							}
						}
					}
					else if(isset($dataArray[$fieldname])){
						foreach($dataArray as $field => $dataVal){
							if(is_array($dataVal)){
								foreach($dataVal as $data){
									if($varArrayAsKeys){
										if($data == $label) $selected = ' checked';
									}
									else{
										if($data == $value) $selected = ' checked';
									}
								}
							}
							else{
								if($varArrayAsKeys){
									if($dataVal == $label) $selected = ' checked';
								}
								else{
									if($dataVal == $value) $selected = ' checked';
								}
							}
						}
					}

					if($varArrayAsKeys){
						$html .= "<div class=\"checkboxGrp\">";
						$html .= "<input type=\"checkbox\" id=\"field\" name=\"field[]\" value=\"{$label}\"{$selected}>";
						$html .= "<label for=\"{$label}\">{$label}</label>";
						$html .= "</div>\n";
					}
					else{
						$html .= "<div class=\"checkboxGrp\">";
						$html .= "<input type=\"checkbox\" id=\"field\" name=\"field[]\" value=\"{$value}\"{$selected}>";
						$html .= "<label for=\"{$label}\">{$label}</label>";
						$html .= "</div>\n";
					}
				}
			}
			return $html;
		}

		/**
		 * This should be set within the user model! Not here! why?
		 * @param string  $field field name
		 * @param string  $value string value
		 * @param integer $hours The time to expire in hours (168 = 1 week)
		 */
		public static function setCookie($field, $value, $hours = 168)
		{
			setcookie($field, $value, time() + 3600 * $hours);
		}

		public static function getCookie($name)
		{
			if(isset($_COOKIE[$name])){
				return self::charFilter('cookie', $_COOKIE[$name], true);
			}
			else return false;
		}

		/**
		 * Sets the input type required for a upload progress bar
		 */
		public static function uploadProgressField($formID)
		{
			return "<input type=\"hidden\" value=\"{$formID}\" name=\"" . ini_get("session.upload_progress.name") . "\">\n";
		}

		public static function errorCodes($field, $uploadErrorCode)
		{
			$maxPostSize = ini_get('post_max_size');
			$uploadMaxFilesize = ini_get('upload_max_filesize');

			$maxPostSize = File::returnBytes($maxPostSize);
			$uploadMaxFilesize = File::returnBytes($uploadMaxFilesize);

			$maxPostSize = File::formatFilesize($maxPostSize);
			$uploadMaxFilesize = File::formatFilesize($uploadMaxFilesize);

			if($uploadErrorCode == UPLOAD_ERR_FORM_SIZE) self::setErrors($field, $field, $maxPostSize, 11);
			else if($uploadErrorCode == UPLOAD_ERR_INI_SIZE) self::setErrors($field, $field, $uploadMaxFilesize, 12);
			else if($uploadErrorCode == UPLOAD_ERR_PARTIAL) self::setErrors($field, $field, '', 13);
			else if($uploadErrorCode == UPLOAD_ERR_NO_FILE) self::setErrors($field, $field, '', 14);
			else if($uploadErrorCode == UPLOAD_ERR_NO_TMP_DIR) self::setErrors($field, $field, '', 15);
			else if($uploadErrorCode == UPLOAD_ERR_CANT_WRITE) self::setErrors($field, $field, '', 16);			
			else if($uploadErrorCode == UPLOAD_ERR_OK) return true; //continue of all OK

			return false;
		}
	}

//!EOF : /main/application/handlers/form.php