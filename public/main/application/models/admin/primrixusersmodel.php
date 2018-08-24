<?php namespace main\application\models\admin;

/**
 * PrimrixUsersModel
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
	use \main\application\handlers\Address;

	use \main\application\models\cms\RightsModel;

	class PrimrixUsersModel
	{
		public static function buildPrimrixUsersTable()
		{
			$db = new DB;
			$html = "";

			$db->where("`rights` >= '{$_SESSION['primrix']['user']['rights']}'");
			$db->orderby("`order` ASC");
			$db->query('primrix_users');
			while($row = $db->fetch()){
				$html .= "<tr>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft'>{$row['username']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['first_name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['last_name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['email']}</td>\n";

				if($row['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_users' data-id='{$row['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small'><a data-table='primrix_users' data-id='{$row['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

				$html .= "<td class='small'><span class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></span></td>\n";

				$html .= "<td class='small'><a href='/admin/primrix-users/edit-user/{$row['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		public static function buildPermissionsTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`id` ASC");
			$db->query('primrix_rights');
			while($row = $db->fetch()){
				$html .= "<tr>\n";
				if($row['id'] != 1) $html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";
				else $html .= "<td class='small' alt='This can not be removed'>!</td>\n";

				$html .= "<td class='txtLeft'>{$row['group_name']}</td>\n";
				$html .= "<td class='small'><span class='colourSquare' style='background-color:{$row['group_colour']}' alt='{$row['group_colour']}'></span></td>\n";
				
				if($row['compulsory'] == 'y') $html .= "<td class='small'><span class='fa fa-check'></span></td>\n";
				else  $html .= "<td class='small'><span class='fa fa-times'></span></td>\n";

				$html .= "<td class='small'><a href='/admin/primrix-users/edit-permission/{$row['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);			
		}

		/**
		 * This simply checks that the username supplied has not already been used
		 * @param  string $username the new username
		 * @return boolean the boolean result of the lookup
		 */
		public static function newUsername($username)
		{
			$db = new DB;
			$db->where("`username` = '{$username}'");
			$db->query('primrix_users');
			if($db->numRows(true) == 0) return false;
			else return true;
		}

	}

//!EOF : /main/application/models/admin/siteoptionsmodel.php