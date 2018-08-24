<?php namespace main\application\models\admin;

/**
 * SiteStructuresModel
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

	class SiteStructuresModel
	{
		public static function buildRedirectionTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`from_url` ASC");
			$db->query('primrix_redirects');
			while($row = $db->fetch()){
				$html .= "<tr>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['from_url']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['to_url']}</td>\n";

				if($row['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_redirects' data-id='{$row['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small'><a data-table='primrix_redirects' data-id='{$row['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

				$returnURL = Address::encode("/admin/site-structures/redirection-manager");

				$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_redirects/{$row['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></a></td>\n";
				$html .= "<td class='small'><a href='/admin/site-structures/editRedirection/{$row['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		public static function buildSiteManagerTable()
		{
			$db1 = new DB;
			$db2 = new DB;
			$db3 = new DB;
			$html = "";

			$db1->orderBy("`order` ASC");
			$db1->query('primrix_site_menu_1');
			while($row1 = $db1->fetch()){

				$html .= "<tr>\n";
				$html .= "<td class='small'><a data-table='primrix_site_menu_1' data-id='{$row1['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small'><a data-table='primrix_site_menu_1' data-id='{$row1['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='a{$row1['id']}' name='a[]' value='{$row1['id']}'></td>\n";			

				$html .= "<td class='small primary'><span class='fa fa-level-up fa-rotate-90'></span></td>\n";
				$html .= "<td class='small'></td>\n";
				$html .= "<td class='small'></td>\n";
				$html .= "<td class='txtLeft'>{$row1['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row1['link']}</td>\n";
				
				if($row1['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_site_menu_1' data-id='{$row1['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small'><a data-table='primrix_site_menu_1' data-id='{$row1['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

				$html .= "<td class='small'><a class='fa fa-users' alt='Edit selected'></a></td>\n";
				$html .= "<td class='small'><a href='/admin/site-structures/editMenuItem/1/{$row1['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";

				$db2->where("`group` = '{$row1['id']}'");
				$db2->orderBy("`order` ASC");
				$db2->query('primrix_site_menu_2');
				while($row2 = $db2->fetch()){
					$html .= "<tr>\n";
					$html .= "<td class='small'><a data-table='primrix_site_menu_2' data-id='{$row2['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
					$html .= "<td class='small'><a data-table='primrix_site_menu_2' data-id='{$row2['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

					$html .= "<td class='small'><input type='checkbox' class='selection' id='b{$row2['id']}' name='b[]' value='{$row2['id']}'></td>\n";			

					$html .= "<td class='small'></td>\n";
					$html .= "<td class='small primary'><span class='fa fa-level-up fa-rotate-90'></span></td>\n";
					$html .= "<td class='small'></td>\n";
					$html .= "<td class='txtLeft'>{$row2['name']}</td>\n";
					$html .= "<td class='txtLeft'>{$row2['link']}</td>\n";
				
					if($row2['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_site_menu_2' data-id='{$row2['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
					else $html .= "<td class='small'><a data-table='primrix_site_menu_2' data-id='{$row2['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

					$html .= "<td class='small'><a class='fa fa-users' alt='Edit selected'></a></td>\n";
					$html .= "<td class='small'><a href='/admin/site-structures/editMenuItem/2/{$row2['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
					$html .= "</tr>\n";

					$db3->where("`group` = '{$row2['id']}'");
					$db3->orderBy("`order` ASC");
					$db3->query('primrix_site_menu_3');
					while($row3 = $db3->fetch()){
						$html .= "<tr>\n";
						$html .= "<td class='small'><a data-table='primrix_site_menu_3' data-id='{$row3['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
						$html .= "<td class='small'><a data-table='primrix_site_menu_3' data-id='{$row3['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

						$html .= "<td class='small'><input type='checkbox' class='selection' id='c{$row3['id']}' name='c[]' value='{$row3['id']}'></td>\n";			

						$html .= "<td class='small'></td>\n";
						$html .= "<td class='small'></td>\n";
						$html .= "<td class='small primary'><span class='fa fa-level-up fa-rotate-90'></span></td>\n";
						$html .= "<td class='txtLeft'>{$row3['name']}</td>\n";
						$html .= "<td class='txtLeft'>{$row3['link']}</td>\n";
					
						if($row3['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_site_menu_3' data-id='{$row3['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
						else $html .= "<td class='small'><a data-table='primrix_site_menu_3' data-id='{$row3['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

						$html .= "<td class='small'><a class='fa fa-users' alt='Edit selected'></a></td>\n";
						$html .= "<td class='small'><a href='/admin/site-structures/editMenuItem/3/{$row2['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
						$html .= "</tr>\n";
					}
					$db3->close();
				}
				$db2->close();

			}

			Doc::bind('table_rows', $html);

		}
	}

//!EOF : /main/application/models/admin/sitestructuresmodel.php