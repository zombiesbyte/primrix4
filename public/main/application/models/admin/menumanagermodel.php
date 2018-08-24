<?php namespace main\application\models\admin;

/**
 * MenuManagerModel
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

	class MenuManagerModel
	{
		public static function buildMenus($highlight_1 = null, $hightlight_2 = null)
		{
			//instantiate our objects;
			$db = new DB;
			$db2 = new DB;

			$html = "";

			$db->where("`active` = 'y'");
			$db->orderby("`order` ASC");
			$db->query('primrix_menu');
			while($row1 = $db->fetch()){
				
				$rightsArray1 = explode("\n", $row1['rights']);
				$userRights = $_SESSION['primrix']['user']['rights'];
				if(in_array($userRights, $rightsArray1)){

					if($row1['menu_link'] != ''){
						$row1['menu_link'] = Form::fromHTML($row1['menu_link']);
						
						$target = '';
						if($row1['opens_new'] == 'y') $target = " target='_blank'";

						if(Uri::matchUrl($row1['menu_link'])) $html .= "<div class='item' id='{$row1['menu_ref']}'><a href='{$row1['menu_link']}' alt='{$row1['menu_tip']}' class='selected'{$target}><span class='{$row1['menu_icon']}'></span><p>{$row1['name']}</p></a></div>\n";
						else $html .= "<div class='item' id='{$row1['menu_ref']}'><a href='{$row1['menu_link']}' alt='{$row1['menu_tip']}'{$target}><span class='{$row1['menu_icon']}'></span><p>{$row1['name']}</p></a></div>\n";
					}
					else $html .= "<div class='item' id='{$row1['menu_ref']}'><a class='navOpen' data-nav-id='{$row1['id']}' alt='{$row1['menu_tip']}'><span class='{$row1['menu_icon']}'></span><p>{$row1['name']}</p></a></div>\n"; 

					$db2->where("`active` = 'y' AND `group` = '{$row1['id']}'");
					$db2->orderby("`order` ASC");
					$db2->query('primrix_menu_items');
					
					$totalRows = $db2->numRows();
					$currentRow = 1;

					while($row2 = $db2->fetch()){

						$rightsArray2 = explode("\n", $row2['rights']);
						if(in_array($userRights, $rightsArray2)){
							if($currentRow == 1) $html .= " <div class='subGrp' id='nav-{$row1['id']}'>\n";
							$row2['menu_link'] = Form::fromHTML($row2['menu_link']);

							$target = '';
							if($row2['opens_new'] == 'y') $target = " target='_blank'";

							if(Uri::matchUrl($row2['menu_link'])) $html .= "  <div class='subItem'><a href='{$row2['menu_link']}' class='selected' alt='{$row2['menu_tip']}'{$target}><span class='{$row2['menu_icon']}'></span><p>{$row2['name']}</p></a></div>\n";
							else $html .= "  <div class='subItem'><a href='{$row2['menu_link']}' alt='{$row2['menu_tip']}'{$target}><span class='{$row2['menu_icon']}'></span><p>{$row2['name']}</p></a></div>\n";
							//if($currentRow == $totalRows) $html .= " </div><!--subGrp-->\n";
							$currentRow++;
						}
					}

					if($currentRow > 1) $html .= " </div><!--subGrp-->\n";
				}
			}

			Doc::bind('primrix_menu', $html);

		}

		public static function buildMenuTable()
		{
			//instantiate our objects;
			$db = new DB;
			$db2 = new DB;

			$html = "";

			$returnURL = Address::encode("/admin/menu-manager");

			$db->orderby("`order` ASC");
			$db->query('primrix_menu');
			while($row1 = $db->fetch()){
				
				$html .= "<tr>\n";
				
				$html .= "<td class='small primary'><a data-table='primrix_menu' data-id='{$row1['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small primary'><a data-table='primrix_menu' data-id='{$row1['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small primary'><input type='checkbox' class='selection' id='a{$row1['id']}' name='a[]' value='{$row1['id']}'></td>\n";
				$html .= "<td class='small primary'><span class='{$row1['menu_icon']}'></span></td>\n";
				
				$opens_new = "";
				$dash = "";
				if($row1['opens_new'] == 'y') $opens_new = " <span class='sm fa fa-arrow-circle-o-up' alt='Opens in a new window or tab'></span>";
				if($row1['dash'] == 'y') $dash = " <span class='sm fa fa-dashboard' alt='Is included on the dashboard'></span>";

				$html .= "<td class='txtLeft primary'>{$row1['name']}{$opens_new}{$dash}</td>\n";
				
				if($row1['active'] == 'y') $html .= "<td class='small primary'><a data-table='primrix_menu' data-id='{$row1['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small primary'><a data-table='primrix_menu' data-id='{$row1['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

				$html .= "<td class='small primary'><a href='/admin/primrix/permissions/primrix_menu/{$row1['id']}/{$returnURL}' class='fa fa-users' alt='" . RightsModel::getToolTip($row1['rights']) . "'></span></td>\n";
				$html .= "<td class='small primary'><a href='/admin/menu-manager/edit/primrix_menu/{$row1['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";

				$db2->where("`group` = '{$row1['id']}'");
				$db2->orderby("`order` ASC");
				$db2->query('primrix_menu_items');
				
				$totalRows = $db2->numRows();
				$currentRow = 1;

				while($row2 = $db2->fetch()){
					$html .= "<tr>\n";
					
					$html .= "<td class='small'><a data-table='primrix_menu_items' data-id='{$row2['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
					$html .= "<td class='small'><a data-table='primrix_menu_items' data-id='{$row2['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

					$html .= "<td class='small'><input type='checkbox' class='selection' id='b{$row2['id']}' name='b[]' value='{$row2['id']}'></td>\n";
					$html .= "<td class='small'><span class='{$row2['menu_icon']}'></span></td>\n";
					
					$opens_new = "";
					$dash = "";
					if($row2['opens_new'] == 'y') $opens_new = " <span class='sm fa fa-arrow-circle-o-up' alt='Opens in a new window or tab'></span>";
					if($row2['dash'] == 'y') $dash = " <span class='sm fa fa-dashboard' alt='Is included on the dashboard'></span>";

					$html .= "<td class='txtLeft'>{$row2['name']}{$opens_new}{$dash}</td>\n";
				
					if($row2['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_menu_items' data-id='{$row2['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
					else $html .= "<td class='small'><a data-table='primrix_menu_items' data-id='{$row2['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

					$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_menu_items/{$row2['id']}/{$returnURL}' class='fa fa-users' alt='" . RightsModel::getToolTip($row2['rights']) . "'></span></td>\n";
					$html .= "<td class='small'><a href='/admin/menu-manager/edit/primrix_menu_items/{$row2['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
					$html .= "</tr>\n";
				}

			}

			Doc::bind('table_rows', $html);			
			
		}

		public static function buildMenuSelectList()
		{
			//instantiate our objects;
			$db = new DB;


			$varArray = array();
			$dataArray = array();

			$db->where();
			$db->orderby("`order` ASC");
			$db->query('primrix_menu');
			while($row = $db->fetch()){
				$varArray[$row['id']] = $row['name'];
			}
			$db->close();

			$varArray['d'] = '------------------------------------------------';
			$varArray[0] = 'New Parent';
			
			if(isset($_POST['group'])) $dataArray = $_POST['group'];
			else $dataArray = '';

			$parentOptions = Form::buildHTML('select', 'group', $varArray, $dataArray);

			Doc::bind('parentOptions', $parentOptions);
		}

	}

//!EOF : /main/application/models/admin/MenuManagerModel.php