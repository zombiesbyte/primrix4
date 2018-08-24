<?php namespace main\application\models\admin;

/**
 * ContentCentreModel
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

	class ContentCentreModel
	{
		public static function buildMetaManagerTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`order` ASC");
			$db->query('primrix_meta_blueprint');
			while($row = $db->fetch()){
				$html .= "<tr>\n";

				$html .= "<td class='small'><a data-table='primrix_meta_blueprint' data-id='{$row['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small'><a data-table='primrix_meta_blueprint' data-id='{$row['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft' alt='{$row['notes']}'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['default']}</td>\n";

				if($row['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_meta_blueprint' data-id='{$row['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small'><a data-table='primrix_meta_blueprint' data-id='{$row['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";

				$html .= "<td class='small'><span class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></span></td>\n";

				$html .= "<td class='small'><a href='/admin/content-centre/edit-meta/{$row['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		public static function buildGlobalContentTable()
		{
			$db = new DB;
			$html = "";

			$db->orderby("`order` ASC");
			$db->query('primrix_global_content');
			while($row = $db->fetch()){
				$html .= "<tr>\n";
				$html .= "<td class='small'><a data-table='primrix_global_content' data-id='{$row['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small'><a data-table='primrix_global_content' data-id='{$row['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft' alt='{$row['notes']}'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>" . Text::teaser($row['value'], 300) . "</td>\n";

				$returnURL = Address::encode("/admin/content-centre/global-content");

				$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_global_content/{$row['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></a></td>\n";

				

				$html .= "<td class='small'><a href='/admin/primrix/editor/primrix_global_content/{$row['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		public static function buildSiteContentTable()
		{
			$db1 = new DB;
			$db2 = new DB;
			$html = "";

			$db1->orderby("`order` ASC");
			$db1->query('primrix_site_pages');
			while($row1 = $db1->fetch()){
				$html .= "<tr>\n";
				$html .= "<td class='small primary'><a class='expander fa fa-toggle-right' data-expander-id='{$row1['id']}'></a></td>\n";

				$html .= "<td class='small primary'><a data-table='primrix_site_pages' data-id='{$row1['id']}' data-order='up' class='order fa fa-angle-up'></a></td>\n";
				$html .= "<td class='small primary'><a data-table='primrix_site_pages' data-id='{$row1['id']}' data-order='down' class='order fa fa-angle-down'></a></td>\n";

				$html .= "<td class='small primary'><input type='checkbox' class='selection' id='a{$row1['id']}' name='a[]' value='{$row1['id']}'></td>\n";

				if($row1['uri'] == '/') $html .= "<td class='small primary' alt='{$row1['template']}'><span class='fa fa-home'></span></td>\n";
				else $html .= "<td class='small primary' alt='{$row1['template']}'><span class='fa fa-file'></span></td>\n";

				$html .= "<td class='txtLeft primary' alt='{$row1['name']}'>{$row1['uri']}</td>\n";

				$returnURL = Address::encode("/admin/content-centre/site-content");

				if($row1['active'] == 'y') $html .= "<td class='small primary'><a data-table='primrix_site_pages' data-id='{$row1['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small primary'><a data-table='primrix_site_pages' data-id='{$row1['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";				

				$html .= "<td class='small primary'><a href='/admin/content-centre/page-meta/{$row1['id']}' class='fa fa-code' alt='Edit Meta'></a></td>\n";
				$html .= "<td class='small primary'><a href='/admin/primrix/permissions/primrix_site_pages/{$row1['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row1['rights']) . "'></a></td>\n";
				$html .= "<td class='small primary'><a href='/admin/content-centre/edit-page/{$row1['id']}' class='fa fa-pencil' alt='Edit record'></a></td>\n";

				$html .= "</tr>\n";

				$db2->where("`page_id` = '{$row1['id']}'");
				$db2->orderby("`group` ASC");
				$db2->query('primrix_site_clusters');
				while($row2 = $db2->fetch()){
					$html .= "<tr class='hide' data-expander-grp='{$row1['id']}'>\n";
					$html .= "<td class='blend2BG' colspan='3'></td>\n";

					$html .= "<td class='small'><input type='checkbox' class='selection' id='b{$row2['id']}' name='b[]' value='{$row2['id']}'></td>\n";
					$html .= "<td class='small' alt='{$row2['cluster_desc']}'><span class='fa fa-th'></span></td>\n";
					$html .= "<td class='txtLeft'>{$row2['cluster_ref']}</td>\n";

					$html .= "<td class='small'>&nbsp;</td>\n";
					$html .= "<td class='small'>&nbsp;</td>\n";

					$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_site_clusters/{$row2['id']}/{$returnURL}' class='fa fa-users' alt='" . RightsModel::getToolTip($row1['rights']) . "'></a></td>\n";
					$html .= "<td class='small'><a href='/admin/content-centre/cluster-setup/{$row2['page_id']}/{$row2['group']}' class='fa fa-pencil' alt='Edit cluster'></a></td>\n";
					$html .= "</tr>\n";
				}
				$db2->close();

				$html .= "<tr class='hide' data-expander-grp='{$row1['id']}'>\n";
				$html .= "<td class='blend2BG' colspan='3'></td>\n";
				$html .= "<td class='blend2BG' colspan='3'></td>\n";
				$html .= "<td><a href='/admin/content-centre/create-cluster/{$row1['id']}' class='fa fa-plus' alt='Create a new Cluster'></a></td>\n";
				$html .= "<td><a href='/admin/content-centre/save-cluster/{$row1['id']}' class='fa fa-save' alt='Save Cluster Profile'></a></td>\n";
				$html .= "<td><a href='' class='fa fa-truck' alt='Another Option'></a></td>\n";
				$html .= "<td><a href='' class='fa fa-bank' alt='Another Option'></a></td>\n";
				$html .= "</tr>\n";

			}

			Doc::bind('table_rows', $html);
		}

		public static function buildClusterTable($pageID, $groupID)
		{
			$db1 = new DB;
			$db2 = new DB;
			$html = "";

			$db1->where("`page_id` = '{$pageID}' AND `group` = '{$groupID}'");
			//$db1->orderby("`order` ASC");
			$db1->query('primrix_site_clusters');
			$cluster = $db1->fetch();
			$db1->close();

			$db1->select('`table_group`');
			$db1->where("`page_id` = '{$cluster['page_id']}' AND `cluster_group` = '{$cluster['group']}'");
			$db1->orderby("`table_group` DESC");
			$db1->query('primrix_site_content');
			$contentGrp = $db1->fetch();
			$db1->close();

			$totalContentGrps = $contentGrp['table_group'];

			for($n = 1; $n <= $totalContentGrps; $n++){
				
				$html .= "<table>\n";
				$html .= "<tr>\n";
				$html .= "	<th></th>\n";
				$html .= "	<th>Variable Name</th>\n";
				$html .= "	<th>Value</th>\n";
				$html .= "	<th></th>\n";
				$html .= "	<th></th>\n";
				$html .= "</tr>\n";

				$db2->where("`page_id` = '{$cluster['page_id']}' AND `cluster_group` = '{$cluster['group']}' AND `table_group` = '{$n}'");
				$db2->orderby("`group` ASC, `name` ASC");
				$db2->query('primrix_site_content');
				while($row2 = $db2->fetch()){
					
					$html .= "<tr>\n";

					if($n == 1) $html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row2['id']}' name='id[]' value='{$row2['id']}'></td>\n";
					else $html .= "<td class='small'>&nbsp;</td>\n";

					$html .= "<td class='txtLeft' alt='{$row2['notes']}'>{$row2['name']}</td>\n";
					$html .= "<td class='txtLeft'>" . Text::teaser($row2['value'], 300) . "</td>\n";

					$returnURL = Address::encode("/admin/content-centre/cluster-setup/{$pageID}/{$groupID}");

					$html .= "<td class='small'><a href='/admin/primrix/permissions/primrix_site_content/{$row2['id']}/{$returnURL}'  class='fa fa-users' alt='" . RightsModel::getToolTip($row2['rights']) . "'></a></td>\n";

					//future implentation: The passing of false on a url to the editor will remove the link so that the variable cannot be edited.
					//Currently, there is no way of updating variable groups within the cluster from a single master variable
					//if($n == 1) $html .= "<td class='small'><a href='/admin/primrix/editor/primrix_site_content/{$row2['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
					//else $html .= "<td class='small'><a href='/admin/primrix/editor/primrix_site_content/{$row2['id']}/{$returnURL}/false' class='fa fa-pencil' alt='Edit record'></a></td>\n";
					
					//the following line of code will be used for the time being.
					$html .= "<td class='small'><a href='/admin/primrix/editor/primrix_site_content/{$row2['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";

					$html .= "</tr>\n";

				}

				$html .= "<tr><td class='txtLeft'>\n";
				$html .= "<a href='/admin/content-centre/move-table/up/{$pageID}/{$groupID}/{$n}' alt='Move table up'><i class='fa fa-chevron-up faSmall'></i></a> \n";
				$html .= "<a href='/admin/content-centre/move-table/down/{$pageID}/{$groupID}/{$n}' alt='Move table down'><i class='fa fa-chevron-down faSmall'></i></a> \n";
				$html .= "</td><td class='txtLeft' colspan='3'>&nbsp;</td>\n";
				$html .= "<td class='small'>\n";
				///
				
				$html .= "<a class='deleteLink' href='/admin/content-centre/delete-table/{$pageID}/{$groupID}/{$n}' alt='Delete this table'><i class='fa fa-trash-o'></i></a>\n";
				$html .= "</td></tr>\n";

				$html .= "</table>\n";
				$db2->close();

			}	
			

			Doc::bind('tables', $html);
		}

		public static function duplicateClusterGroup($clusterID, $pageID, $groupID)
		{
			$db = new DB;
			$dbWrite = new DB;

			$nextContentGroup = $dbWrite->nextIndexGrp('primrix_site_content', 'table_group', 'page_id', $pageID, 'cluster_group', $groupID);

			$db->where("`page_id` = '{$pageID}' AND `cluster_group` = '{$groupID}' AND `table_group` = '1'");
			$db->query('primrix_site_content');
			while($row = $db->fetch()){
				//echo "<pre>", print_r($row), "</pre>";

				$clusterContent = array();
				$clusterContent['id'] = $dbWrite->nextIndex('primrix_site_content', 'id');
				$clusterContent['page_id'] = $pageID;
				$clusterContent['cluster_group'] = $groupID;
				$clusterContent['table_group'] = $nextContentGroup;
				$clusterContent['group'] = $row['group'];
				$clusterContent['name'] = $row['name'];
				$clusterContent['value'] = $row['value'];
				$clusterContent['validation'] = $row['validation'];
				$clusterContent['options'] = $row['options'];
				$clusterContent['editor'] = $row['editor'];
				$clusterContent['notes'] = $row['notes'];
				$clusterContent['rights'] = $row['rights'];
				$clusterContent['created_at'] = date('Y-m-d H:i:s');
				$clusterContent['updated_at'] = date('Y-m-d H:i:s');
				$clusterContent['updated_by'] = $_SESSION['primrix']['user']['id'];

				$dbWrite->insert('primrix_site_content', $clusterContent);
			}
		}

		public static function buildPageMetaTable($pageID)
		{
			$db = new DB;
			$html = "";

			$db->where("`page_id` = '{$pageID}'");
			$db->join(['primrix_meta_blueprint','primrix_site_meta'], ['id','meta_id']);
			while($row = $db->fetch()){
				
				//echo "<pre>", print_r($row), "</pre>";
				$html .= "";	
				
				$html .= "<tr>\n";

				$html .= "<td class='txtLeft' alt='{$row['notes']}'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['value']}</td>\n";

				if($row['active'] == 'y') $html .= "<td class='small'><span class='fa fa-check-square-o' alt='Active'></span></td>\n";
				else $html .= "<td class='small'><span class='fa fa-square-o' alt='Inactive'></span></td>\n";

				$html .= "<td class='small'><span class='fa fa-users' alt='" . RightsModel::getToolTip($row['rights']) . "'></span></td>\n";

				$returnURL = Address::encode("/admin/content-centre/page-meta/{$pageID}");

				$html .= "<td class='small'><a href='/admin/content-centre/editor/{$row['id']}/{$returnURL}' class='fa fa-pencil' alt='Edit record'></a></td>\n";
				$html .= "</tr>\n";
			}

			Doc::bind('table_rows', $html);
		}

		/**
		 * This builds the table which allows the user to select their page meta profile from. All entries from the blueprint will be present and any existing
		 * choices will have a tick against them. This is the core management of the profile for selecting and deselecting from the stockpile of attributes.
		 * @return null
		 */
		public static function buildMetaRepositoryTable($pageID)
		{
			$db = new DB;
			$html = "";
			$pageMetaArray = array();

			$db->where("`page_id` = '{$pageID}'");
			$db->query('primrix_site_meta');
			while($row = $db->fetch()){
				$pageMetaArray[] = $row['meta_id'];
			}
			$db->close();

			$db->orderby("`order` ASC");
			$db->query('primrix_meta_blueprint');
			while($row = $db->fetch()){
				$html .= "<tr>\n";

				if(in_array($row['id'], $pageMetaArray)) $html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}' checked></td>\n";
				else $html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft' alt='{$row['notes']}'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['default']}</td>\n";
				$html .= "</tr>\n";
			}
			$db->close();

			Doc::bind('table_rows', $html);
		}

		public static function buildPageBlueprintTable()
		{
			$db = new DB;
			$html = "";

			$db->orderBy("`name` ASC");
			$db->query('primrix_page_blueprint');
			while($row = $db->fetch()){
				$html .= "<tr>";
				$html .= "<td class='small'><input type='checkbox' class='selection' id='id{$row['id']}' name='id[]' value='{$row['id']}'></td>\n";

				$html .= "<td class='txtLeft'>{$row['name']}</td>\n";
				$html .= "<td class='txtLeft'>{$row['template']}</td>\n";

				if($row['active'] == 'y') $html .= "<td class='small'><a data-table='primrix_page_blueprint' data-id='{$row['id']}' class='toggleActive fa fa-check-square-o' alt='Active'></a></td>\n";
				else $html .= "<td class='small'><a data-table='primrix_page_blueprint' data-id='{$row['id']}' class='toggleActive fa fa-square-o' alt='Inactive'></a></td>\n";
				$html .= "</tr>";
			}
			$db->close();

			Doc::bind('table_rows', $html);
		}

	}

//!EOF : /main/application/models/admin/contentcentremodel.php