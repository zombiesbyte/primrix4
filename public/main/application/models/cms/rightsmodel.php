<?php namespace main\application\models\cms;

/**
 * RightsModel
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \site\config\Def;
	use \main\application\handlers\DB;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Text;
	use \main\application\handlers\Uri;
	use \main\application\handlers\Form;
	use \main\application\handlers\Bunch;

	class RightsModel
	{

		public static function getToolTip($rightsArray, $reset = false)
		{
			
			$rightsArray = explode("\n", $rightsArray);

			$html = '';
			if(!isset($_SESSION['primrix']['cache']['rights']) or $reset){
				
				$_SESSION['primrix']['cache']['rights'] = array();
				$i = 0;
				$db = new DB;
				$db->orderBy("`id` ASC");
				$db->query('primrix_rights');
				while($row = $db->fetch()){
					$_SESSION['primrix']['cache']['rights'][$i]['id'] = $row['id'];
					$_SESSION['primrix']['cache']['rights'][$i]['group_name'] = $row['group_name'];
					$_SESSION['primrix']['cache']['rights'][$i]['group_colour'] = $row['group_colour'];
					$_SESSION['primrix']['cache']['rights'][$i]['compulsory'] = $row['compulsory'];
					$i++;
				}
			}

			$rightsArray = Bunch::asArray($rightsArray, false);

			foreach($_SESSION['primrix']['cache']['rights'] as $rights){
				if(in_array($rights['id'], $rightsArray)){
					if($rights['compulsory'] == 'y') $html .= "<span class=\"fa fa-user\"></span> <span style=\"text-shadow: 0 0 5px {$rights['group_colour']};\"> <b>{$rights['group_name']}</b></span>&nbsp; ";
					else $html .= "<span class=\"fa fa-user\"></span> <span style=\"text-shadow: 0 0 5px {$rights['group_colour']};\"> {$rights['group_name']}</span>&nbsp; ";
				}
			}

			return $html;
		}

		public static function generateRightsCheckboxes($rightsArray = null, $injectionVar = null)
		{
			$db = new DB;
			$html = "";

			//we make sure that this is always an array
			if($rightsArray != null){
				if(!is_array($rightsArray)){
					//$temp = $rightsArray;
					//$rightsArray = array();
					//$rightsArray[] = $temp;
					$rightsArray = explode("\n", $rightsArray);
				}
			}
			else $rightsArray = array();

			$db->orderBy("`id` ASC");
			$db->query('primrix_rights');
			while($row = $db->fetch()){

				$html .= "<div class='grp'>\n";

				if(in_array($row['id'], $rightsArray)) $html .= "<input type='checkbox' id='rights' name='rights[]' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}' checked>\n";
				else if($row['compulsory'] == 'y') $html .= "<input type='checkbox' id='rights' name='rights[]' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}' checked>\n";
				else if($_SESSION['primrix']['user']['rights'] == $row['id'])  $html .= "<input type='checkbox' id='rights' name='rights[]' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}' checked>\n";
				else $html .= "<input type='checkbox' id='rights' name='rights[]' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}'>\n";
				
				$html .= "<label for='rights' class='freeStyleLabel'><span style='text-shadow: 0 0 5px {$row['group_colour']}'>{$row['group_name']}</span></label>\n";
				$html .= "</div>\n";

			}
			
			$db->close();

			if($injectionVar == null) return $html;
			else Doc::bind($injectionVar, $html);
		}

		public static function generateRightsRadioBtn($rightsID = null, $injectionVar = null, $protectedLevels = true)
		{
			$db = new DB;
			$html = "";

			if($protectedLevels) $db->where("`id` >= '{$_SESSION['primrix']['user']['rights']}'");
			$db->orderBy("`id` ASC");
			$db->query('primrix_rights');
			while($row = $db->fetch()){

				$html .= "<div class='grp'>\n";

				if($rightsID == $row['id']) $html .= "<input type='radio' id='rights' name='rights' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}' checked>\n";
				else $html .= "<input type='radio' id='rights' name='rights' tabindex='1' value='{$row['id']}' alt='{$row['group_name']}'>\n";

				$html .= "<label for='rights' class='freeStyleLabel'><span style='text-shadow: 0 0 5px {$row['group_colour']}'>{$row['group_name']}</span></label>\n";
				$html .= "</div>\n";

			}
			
			$db->close();

			if($injectionVar == null) return $html;
			else Doc::bind($injectionVar, $html);
		}

		/**
		 * The screening process of this method challenges the a passed rights array
		 * and makes sure that all of the compulsory rights are included as well as
		 * the current users rights level should this be required.
		 * @param  array $rightsArray an array of rights ids
		 * @param  boolean $includeUserRights if true then the current user rights id will be included
		 * @return array $rightsArray
		 */
		public static function screenRights($rightsArray, $includeUserRights = true)
		{
			$db = new DB;

			$db->orderBy("`id` ASC");
			$db->query('primrix_rights');
			while($row = $db->fetch()){
				if($row['compulsory'] == 'y' and !in_array($row['id'], $rightsArray)) $rightsArray[] = $row['id'];
				else if($_SESSION['primrix']['user']['rights'] == $row['id'] and !in_array($row['id'], $rightsArray)){
					if($includeUserRights) $rightsArray[] = $row['id'];
				}
			}
			
			$db->close();
			return $rightsArray;
		}	

	}

//!EOF : /main/application/models/cms/rightsmodel.php