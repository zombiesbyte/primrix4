<?php namespace main\application\controllers\admin;

/**
 * ContentCentre
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\controllers\controller;
	
	use \main\application\handlers\Uri;
	use \main\application\handlers\Doc;
	use \main\application\handlers\Form;
	use \main\application\handlers\DB;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Captcha;
	use \main\application\handlers\Text;
	use \main\application\handlers\File;
	use \main\application\handlers\Order;
	use \main\application\handlers\XML;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\ContentCentreModel;

	use \main\application\models\cms\RightsModel;

	use \site\config\def;

	class ContentCentre extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();

		}

		public function metaManager()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_meta_blueprint', 'id', $_POST['id']);
				Order::rebuildOrders('primrix_meta_blueprint', 'order');
				Address::go('/admin/content-centre/meta-manager');
			}

			if(Form::submit('add')) Address::go('/admin/content-centre/add-meta');

			ContentCentreModel::buildMetaManagerTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'metamanager.html', 'main/views/admin/modules/content_centre/');
		}

		public function addMeta()
		{
			
			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'notes' => '3|128|basic|text',
				'name' => '3|32|basic|text',
				'meta_ref' => '3|32|varname|text',
				'meta_code' => '2|64|none|text',
				'default' => '0|128|none|text',
				'editor' => '1|1|alpha|select',
				'options' => '0|0|rtf|textarea',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_meta_blueprint', 'id');
					$_POST['order'] = $_POST['id'];
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_meta_blueprint', $_POST);

					Address::go('/admin/content-centre/meta-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/meta-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'metamanager_add.html', 'main/views/admin/modules/content_centre/');
		}

		public function editMeta()
		{
			$id = Uri::$array[3];

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query('primrix_meta_blueprint');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/content-centre/meta-manager');

			RightsModel::generateRightsCheckboxes($data['rights'], 'checkboxRights');

			$rules = [
				'notes' => '3|128|basic|text',
				'name' => '3|32|basic|text',
				'meta_ref' => '3|32|varname|text',
				'meta_code' => '2|64|none|text',
				'default' => '0|128|none|text',
				'editor' => '1|1|alpha|select',
				'options' => '0|0|rtf|textarea',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update('primrix_meta_blueprint', $_POST, 'id,created_at,order');

					Address::go('/admin/content-centre/meta-manager');
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/meta-manager');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'metamanager_add.html', 'main/views/admin/modules/content_centre/');
		}

		public function globalContent()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_global_content', 'id', $_POST['id']);
				Order::rebuildOrders('primrix_global_content', 'order');
				Address::go('/admin/content-centre/global-content');
			}

			if(Form::submit('add')){
				$returnURL = Address::encode("/admin/content-centre/global-content");
				Address::go('/admin/primrix/new-variable/primrix_global_content/' . $returnURL);
			}

			ContentCentreModel::buildGlobalContentTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'globalcontent.html', 'main/views/admin/modules/content_centre/');
		}

		public function siteContent()
		{
			if(Form::submit('delete')){
				$db = new DB;

				if(isset($_POST['a'])){
					foreach($_POST['a'] as $pageID){
						//delete content
						$db->where("`page_id` = '{$pageID}'");
						$db->deleteRow('primrix_site_content');
				
						//delete cluster
						$db->where("`page_id` = '{$pageID}'");
						$db->deleteRow('primrix_site_clusters');
				
						//delete meta
						$db->where("`page_id` = '{$pageID}'");
						$db->deleteRow('primrix_site_meta');
				
						//delete page
						$db->where("`id` = '{$pageID}'");
						$db->deleteRow('primrix_site_pages');
						Order::rebuildOrders('primrix_site_pages', 'order');
					}
				}

				if(isset($_POST['b'])){
					foreach($_POST['b'] as $id){
						//lookup cluster info
						$db->select("`page_id`, `group`");
						$db->where("`id` = '{$id}'");
						$db->query('primrix_site_clusters');
						$clusterRow = $db->fetch();
						$db->close();

						//delete content
						$db->where("`page_id` = '{$clusterRow['page_id']}' AND `cluster_group` = '{$clusterRow['group']}'");
						$db->deleteRow('primrix_site_content');

						//delete cluster
						$db->where("`id` = '{$id}'");
						$db->deleteRow('primrix_site_clusters');
					}
				}

				Address::go('/admin/content-centre/site-content');
				
			}

			if(Form::submit('add')) Address::go('/admin/content-centre/add-page');

			$env = Def::$primrix->environment;
			$protocol = Def::$primrix->settings->{$env}->default_protocol;
			$www = Def::$primrix->settings->{$env}->default_www;
			$domID = Def::$primrix->settings->{$env}->domain_id;
			$domExt = Def::$primrix->settings->{$env}->domain_ext;
			$fullDomainPath = $protocol . $www . $domID . $domExt;
			Doc::bind('fullDomainPath', $fullDomainPath);

			ContentCentreModel::buildSiteContentTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitecontent.html', 'main/views/admin/modules/content_centre/');
		}

		public function addPage()
		{
			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$db = new DB;

			$blueprints = array();
			$db->select("`id`, `name`");
			$db->where("`active` = 'y'");
			$db->orderBy("`id` ASC");
			$db->query('primrix_page_blueprint');
			while($bpRow = $db->fetch()){
				$blueprints[ $bpRow['id'] ] = $bpRow['name'];
			}
			$db->close();

			$blueprintOptions = Form::buildHTML('select', 'blueprint', $blueprints, $_POST, false);
			Doc::bind('blueprintOptions', $blueprintOptions); 

			$rules = [
				'uri' => '1|128|path|text|URN',
				'name' => '1|64|basic|text',
				'template' => '0|1|filename|select',
				'blueprint' => '0|1|varname|select',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){

					$_POST['uri'] = Form::fromHTML($_POST['uri']);
					if($_POST['uri'][0] != '/') $_POST['uri'] = '/' . $_POST['uri'];
					if(substr($_POST['uri'], -1) == '/') $_POST['uri'] = Text::groom('/', $_POST['uri']);
					if($_POST['uri'] == "") $_POST['uri'] = "/";

					$_POST['id'] = $db->nextIndex('primrix_site_pages', 'id');
					$_POST['order'] = $_POST['id'];
					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];
					
					$db->insert('primrix_site_pages', $_POST);

					if($_POST['blueprint'] != "") $this->loadPageBlueprint($_POST['blueprint'], $_POST['id']);

					Address::go('/admin/content-centre/page-meta/' . $_POST['id']);
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitecontent_add.html', 'main/views/admin/modules/content_centre/');
		}

		public function editPage()
		{
			$pageID = Uri::$array[3];

			$db = new DB;

			$blueprints = array();
			$db->select("`id`, `name`");
			$db->where("`active` = 'y'");
			$db->orderBy("`id` ASC");
			$db->query('primrix_page_blueprint');
			while($bpRow = $db->fetch()){
				$blueprints[ $bpRow['id'] ] = $bpRow['name'];
			}
			$db->close();

			$blueprintOptions = Form::buildHTML('select', 'blueprint', $blueprints, $_POST, false);
			Doc::bind('blueprintOptions', $blueprintOptions);

			$db->where("`id` = '{$pageID}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go('/admin/content-centre/site-content');

			RightsModel::generateRightsCheckboxes($data['rights'], 'checkboxRights');

			$rules = [
				'uri' => '1|128|path|text|URN',
				'name' => '1|64|basic|text',
				'template' => '0|1|filename|text',
				'blueprint' => '0|1|varname|select',
				'active' => '1|1|alpha|radio',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['uri'] = Form::fromHTML($_POST['uri']);
					if($_POST['uri'][0] != '/') $_POST['uri'] = '/' . $_POST['uri'];
					if(substr($_POST['uri'], -1) == '/') $_POST['uri'] = Text::groom('/', $_POST['uri']);
					if($_POST['uri'] == "") $_POST['uri'] = "/";
					
					$_POST['updated_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];
					
					$db->where("`id` = '{$pageID}'");
					$db->update('primrix_site_pages', $_POST);

					Address::go('/admin/content-centre/site-content');
				}
			}

			if(Form::submit('reapply')){
				if($_POST['blueprint'] != "") $this->loadPageBlueprint($_POST['blueprint'], $pageID);
				Address::go('/admin/content-centre/site-content');
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'sitecontent_edit.html', 'main/views/admin/modules/content_centre/');
		}

		public function loadPageBlueprint($blueprintID, $pageID)
		{
			$db = new DB;

			$db->where("`page_id` = '{$pageID}'");
			$db->deleteRow('primrix_site_clusters');

			$db->where("`page_id` = '{$pageID}'");
			$db->deleteRow('primrix_site_content');

			$db->where("`id` = '{$_POST['blueprint']}'");
			$db->query('primrix_page_blueprint');
			$blueprint = $db->fetch();
			$db->close();

			$xmlArray =  XML::toArray($blueprint['xml'], true);
			foreach($xmlArray['cluster'] as $cluster){

				$addCluster = array();
				$addCluster['id'] = $db->nextIndex('primrix_site_clusters', 'id');
				$addCluster['page_id'] = $pageID;
				$addCluster['group'] = $cluster['group'][0];
				$addCluster['table_group'] = $cluster['table_group'][0];
				$addCluster['cluster_ref'] = $cluster['cluster_ref'][0];
				$addCluster['cluster_desc'] = $cluster['cluster_desc'][0];
				$addCluster['duplication_allowed'] = $cluster['duplication_allowed'][0];
				$addCluster['use_html_block'] = $cluster['use_html_block'][0];
				$addCluster['cluster_html'] = $cluster['cluster_html'][0];
				$addCluster['rights'] = $cluster['rights'][0];
				$addCluster['cluster_html'] = $cluster['cluster_html'][0];
				$addCluster['created_at'] = date('Y-m-d H:i:s');
				$addCluster['updated_at'] = date('Y-m-d H:i:s');
				$addCluster['updated_by'] = $_SESSION['primrix']['user']['id'];
				
				$db->insert('primrix_site_clusters', $addCluster);

				foreach($cluster['content'] as $content){
					
					$addContent = array();
					$addContent['id'] = $db->nextIndex('primrix_site_content', 'id');
					$addContent['page_id'] = $pageID;
					$addContent['cluster_group'] = $cluster['group'][0];
					$addContent['group'] = $content['group'][0];
					$addContent['table_group'] = $content['table_group'][0];
					$addContent['name'] = $content['name'][0];
					$addContent['value'] = $content['value'][0];
					$addContent['validation'] = $content['validation'][0];
					$addContent['options'] = $content['options'][0];
					$addContent['editor'] = $content['editor'][0];
					$addContent['notes'] = $content['notes'][0];
					$addContent['rights'] = $content['rights'][0];
					$addContent['created_at'] = date('Y-m-d H:i:s');
					$addContent['updated_at'] = date('Y-m-d H:i:s');
					$addContent['updated_by'] = $_SESSION['primrix']['user']['id'];
					
					$db->insert('primrix_site_content', $addContent);

				}
			}


		}

		public function createCluster()
		{
			$pageID = Uri::$array[3];

			$db = new DB;
			$db->select('`uri`');
			$db->where("`id` = '{$pageID}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $page = $db->fetch();
			else Address::go('/admin/content-centre/site-content');

			if($page['uri'] == '/') Doc::bind('pageUri', "<span class='fa fa-home std'></span> " . $page['uri']);
			else Doc::bind('pageUri', "<span class='fa fa-file std'></span> " . $page['uri']);

			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'cluster_desc' => '3|128|basic|text',
				'cluster_ref' => '2|32|varname|text',
				'duplication_allowed' => '0|1|alpha|check',
				'use_html_block' => '0|1|alpha|check',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){
					$db = new DB;

					$_POST['id'] = $db->nextIndex('primrix_site_clusters', 'id');
					$_POST['page_id'] = $pageID;
					$_POST['group'] = $db->nextIndexGrp('primrix_site_clusters', 'group', 'page_id', $pageID);
					$_POST['order'] = $_POST['group'];

					if($_POST['duplication_allowed'] == '') $_POST['duplication_allowed'] = 'n';
					if($_POST['use_html_block'] == '') $_POST['use_html_block'] = 'n';

					$_POST['created_at'] = date('Y-m-d H:i:s');
					$_POST['modified_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->insert('primrix_site_clusters', $_POST);

					Address::go('/admin/content-centre/cluster-setup/' . $_POST['id']);
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'createcluster.html', 'main/views/admin/modules/content_centre/');
		}

		public function editCluster()
		{
			//we use the clusterID instead to edit
			$clusterID = Uri::$array[3];
			$returnURL = Address::decode(Uri::$array[4]);

			$db = new DB;
			$db->where("`id` = '{$clusterID}'");
			$db->query('primrix_site_clusters');
			if($db->numRows() == 1) $clusterData = $db->fetch();
			else Address::go($returnURL);
			$db->close();

			$db->select('`uri`');
			$db->where("`id` = '{$clusterData['page_id']}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $page = $db->fetch();
			else Address::go($returnURL);
			$db->close();

			if($page['uri'] == '/') Doc::bind('pageUri', "<span class='fa fa-home std'></span> " . $page['uri']);
			else Doc::bind('pageUri', "<span class='fa fa-file std'></span> " . $page['uri']);

			RightsModel::generateRightsCheckboxes(null, 'checkboxRights');

			$rules = [
				'cluster_desc' => '3|128|basic|text',
				'cluster_ref' => '2|32|varname|text',
				'duplication_allowed' => '0|1|alpha|check',
				'use_html_block' => '0|1|alpha|check',
				'rights' => '1|0|num|check'
			];

			Form::setData($rules, $clusterData);

			if(Form::submit('save')){
				
				RightsModel::generateRightsCheckboxes($_POST['rights'], 'checkboxRights');

				if(Form::validate($rules)){

					if($_POST['duplication_allowed'] == '') $_POST['duplication_allowed'] = 'n';
					if($_POST['use_html_block'] == '') $_POST['use_html_block'] = 'n';

					$_POST['modified_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$clusterID}'");
					$db->update('primrix_site_clusters', $_POST, 'id,page_id,group,order,created_at');

					Address::go($returnURL);
				}
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'createcluster.html', 'main/views/admin/modules/content_centre/');
		}		

		public function clusterSetup()
		{
			$pageID = Uri::$array[3];
			$groupID = Uri::$array[4];

			$db = new DB;
			$db->where("`page_id` = '{$pageID}' AND `group` = '{$groupID}'");
			$db->query('primrix_site_clusters');
			if($db->numRows() == 1) $clusterData = $db->fetch();
			else Address::go('/admin/content-centre/site-content');
			$db->close();

			Doc::bind('clusterName', $clusterData['cluster_ref']);

			if($clusterData['use_html_block'] == 'y'){
				$editHTMLButton = "<button type='submit' class='left' id='htmlBlock' name='htmlBlock' alt='Edit the HTML code block' tabindex='1'>\n";
				$editHTMLButton .= "<span class='fa fa-html5'></span> HTML Block</button>\n";
			}else $editHTMLButton = "";

			if($clusterData['duplication_allowed'] == 'y'){
				$extendClusterButton = "<button type='submit' id='extendCluster' name='extendCluster' alt='Extend the cluster group' tabindex='1'>\n";
				$extendClusterButton .= "<span class='fa fa-expand'></span> Extend Cluster</button>\n";
			}else $extendClusterButton = "";

			Doc::bind('editHTMLButton', $editHTMLButton);
			Doc::bind('extendClusterButton', $extendClusterButton);

			$returnURL = Address::encode("/admin/content-centre/cluster-setup/{$pageID}/{$groupID}");
			$editClusterLink = '/admin/content-centre/edit-cluster/' . $clusterData['id'] . '/' . $returnURL;
Doc::bind('editCluster', "<a href='{$editClusterLink}' class='right'><span class='fa fa-edit'></span></a>\n");

			ContentCentreModel::buildClusterTable($pageID, $groupID);

			if(Form::submit('addVariable')){

				//next we find the next group id based on page_id, cluster_group then group. This is a 3rd level lookup so we can work out how many times we
				//need to replicate the entry within our cluster so that the new variable entry applies to all groups within.
				$tableGroupIndex = $db->nextIndexGrp('primrix_site_content', 'table_group', 'cluster_group', $clusterData['group'], 'page_id', $clusterData['page_id']);
				if($tableGroupIndex > 1) $totalReplication = $tableGroupIndex - 1; //nextIndexGrp will return the next index so to get an accurate figure we need to deduct 1 from the value.
				else $totalReplication = $tableGroupIndex;

				$db->where("`page_id` = '{$clusterData['page_id']}' AND `cluster_group` = '{$clusterData['group']}' AND `table_group` = '{$totalReplication}'");
				$groupIndex = $db->nextIndexLookup('primrix_site_content', 'group');

				$passedFV = Address::encode("page_id,{$clusterData['page_id']},cluster_group,{$clusterData['group']},table_group,{$totalReplication},group,{$groupIndex}");

				Address::go('/admin/primrix/new-variable/primrix_site_content/' . $returnURL . '/' . $passedFV . '/' . $totalReplication . '/' . 'table_group');
			}

			if(Form::submit('extendCluster')){
				ContentCentreModel::duplicateClusterGroup($clusterData['id'], $pageID, $groupID);
				Address::go("/admin/content-centre/cluster-setup/{$pageID}/{$groupID}");
			}

			if(Form::submit('htmlBlock')){
				Address::go("/admin/content-centre/edit-cluster-block/{$clusterData['id']}/{$returnURL}");
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			if(Form::submit('delete')){
				foreach($_POST['id'] as $id){
					if($id != ""){
						$db->select("`id`,`page_id`,`cluster_group`,`group`");
						$db->where("`id` = '{$id}'");
						$db->query('primrix_site_content');
						if($db->numRows() == 1){
							
							$row = $db->fetch();
							$db->close();

							//we remove the rows from each of our table_groups
							$db->where("`page_id` = '{$row['page_id']}'
										AND `cluster_group` = '{$row['cluster_group']}'
										AND `group` = '{$row['group']}'");
							$db->deleteRow('primrix_site_content');

							//we then need to find out how many table groups their are
							//so we can loop through each of them and rebuild the orders (group)							
							$db->where("`page_id` = '{$row['page_id']}' AND `cluster_group` = '{$row['cluster_group']}'");
							$totalTableGrp = $db->nextIndexLookup('primrix_site_content','table_group');

							for($n = 1; $n < $totalTableGrp; $n++){
								$db->where("`page_id` = '{$row['page_id']}'
											AND `cluster_group` = '{$row['cluster_group']}'
											AND `table_group` = '{$n}'");
								$db->rebuildOrders('primrix_site_content', 'group');
							}
						}
					}
				}

				Address::go("/admin/content-centre/cluster-setup/{$pageID}/{$groupID}");
			}

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'setupcluster.html', 'main/views/admin/modules/content_centre/');
		}

		public function editClusterBlock()
		{
			$clusterID = Uri::$array[3];
			$returnURL = Address::decode(Uri::$array[4]);

			$db = new DB;
			$db->where("`id` = '{$clusterID}'");
			$db->query('primrix_site_clusters');
			if($db->numRows() == 1) $data = $db->fetch();
			else Address::go($returnURL);
			$db->close();

			$clusterVariables = "";
			$db->where("`page_id` = '{$data['page_id']}' AND `cluster_group` = '{$data['group']}' AND `table_group` = '1'");
			$db->query('primrix_site_content');
			while($contentRow = $db->fetch()){
				$clusterVariables .= $contentRow['name'] . ', ';
			}
			$db->close();

			if($clusterVariables != '') $clusterVariables = Text::groom(', ', $clusterVariables);
			Doc::bind('clusterVariables', $clusterVariables);

			Doc::bind('lWrapper', Def::$primrix->settings->prefix);
			Doc::bind('rWrapper', Def::$primrix->settings->suffix);

			$rules = [
				'cluster_html' => '0|0|none|textarea'
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){

					$_POST['modified_at'] = date('Y-m-d H:i:s');
					$_POST['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$clusterID}'");
					$db->update('primrix_site_clusters', $_POST, 'id,page_id,group,order,created_at,duplication_allowed,use_html_block');

					Address::go($returnURL);
				}
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'clusterblock.html', 'main/views/admin/modules/content_centre/');

		}

		public function moveTable()
		{
			$direction = Uri::$array[3];
			$pageID = Uri::$array[4];
			$groupID = Uri::$array[5];
			$tbl_group = Uri::$array[6];
			$returnURL = "/admin/content-centre/cluster-setup/{$pageID}/{$groupID}";

			$db = new DB;

			$db->where("`page_id` = '{$pageID}' AND `cluster_group` = '{$groupID}'");
			$db->reorderField('primrix_site_content','table_group', $tbl_group, $direction);

			Address::go($returnURL);
			
		}

		public function deleteTable()
		{

			$pageID = Uri::$array[3];
			$groupID = Uri::$array[4];
			$group = Uri::$array[5];
			$returnURL = "/admin/content-centre/cluster-setup/{$pageID}/{$groupID}";

			$db = new DB;

			$db->where("`page_id` = '{$pageID}' AND `cluster_group` = '{$groupID}' AND `table_group` = '{$group}'");
			$db->deleteRow('primrix_site_content');

			$db->where("`page_id` = '{$pageID}' AND `cluster_group` = '{$groupID}'");
			$db->rebuildOrders('primrix_site_content', 'table_group', $group);
			
			Address::go($returnURL);
	
		}

		public function saveCluster()
		{

			$pageID = Uri::$array[3];
			$db = new DB;
			$db->where("`id` = '{$pageID}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $row = $db->fetch();
			else Address::go('/admin/content-centre/site-content');
			$db->close();

			Doc::bind('pageName', $row['name']);

			$rules = [
				'name' => '2|32|varname|text',
				'template' => '0|1|varname|select'
			];

			Form::setData($rules);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){

					$db1 = new DB;
					$db2 = new DB;

					$cDataL = "<![CDATA[";
					$cDataR = "]]>";

					$sxml = XML::newObj();

					$db1->where("`page_id` = '{$pageID}'");
					$db1->orderBy("`group` ASC");
					$db1->query('primrix_site_clusters');
					while($cluster = $db1->fetch()){

						$clusterXML = $sxml->addChild('cluster');
						$clusterXML->addChild('group', $cluster['group']);
						$clusterXML->addChild('cluster_ref', $cDataL . $cluster['cluster_ref'] . $cDataR);
						$clusterXML->addChild('cluster_desc', $cDataL . $cluster['cluster_desc'] . $cDataR);
						$clusterXML->addChild('duplication_allowed', $cluster['duplication_allowed']);
						$clusterXML->addChild('use_html_block', $cluster['use_html_block']);
						$clusterXML->addChild('cluster_html', $cDataL . $cluster['cluster_html'] . $cDataR);
						$clusterXML->addChild('rights', $cluster['rights']);

						$db2->where("`page_id` = '{$pageID}' AND `cluster_group` = '{$cluster['group']}'");
						$db2->orderBy("`id` ASC, `group` ASC");
						$db2->query('primrix_site_content');
						while($content = $db2->fetch()){

							$contentXML = $clusterXML->addChild('content');
							$contentXML->addChild('group', $content['group']);
							$contentXML->addChild('table_group', $content['table_group']);
							$contentXML->addChild('name', $cDataL . $content['name'] . $cDataR);
							$contentXML->addChild('value', $cDataL . $content['value'] . $cDataR);
							$contentXML->addChild('validation', $cDataL . $content['validation'] . $cDataR);
							$contentXML->addChild('options', $cDataL . $content['options'] . $cDataR);
							$contentXML->addChild('editor', $content['editor']);
							$contentXML->addChild('notes', $cDataL . $content['notes'] . $cDataR);
							$contentXML->addChild('rights', $content['rights']);
						
						}
						$db2->close();
					}
					$db1->close();

					$_POST['xml'] = XML::formatOutput($sxml);
					$_POST['id'] = $db1->nextIndex('primrix_page_blueprint', 'id');
					$_POST['active'] = 'y';

					$db1->insert('primrix_page_blueprint', $_POST);
					Address::go('/admin/content-centre/site-content');					
				}
			}

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'savecluster.html', 'main/views/admin/modules/content_centre/');
		}

		public function pageMeta()
		{
			$pageID = Uri::$array[3];

			$db = new DB;
			$db->select("`name`");
			$db->where("`id` = '{$pageID}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $row = $db->fetch();
			else Address::go('/admin/content-centre/site-content');
			$db->close();

			Doc::bind('pageName', $row['name']);

			ContentCentreModel::buildPageMetaTable($pageID);

			if(Form::submit('cancel')) Address::go('/admin/content-centre/site-content');

			if(Form::submit('edit')) Address::go('/admin/content-centre/edit-meta-profile/' . $pageID);

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'pagemeta.html', 'main/views/admin/modules/content_centre/');
		}

		//We borrow from our primrix.php class the editor interface. This method needs to tackle a join but passes to our normal
		//methods for handling the user input over at primrix.php.
		
		public function editor()
		{
			$id = Uri::$array[3];
			$returnURL = Address::decode(Uri::$array[4]);

			$db = new DB;
			
			$db->where("`{$db->prefix}primrix_site_meta`.`id` = '{$id}'");
			$db->join(['primrix_meta_blueprint','primrix_site_meta'], ['id','meta_id']);

			if($db->numRows() == 1){
				$row = $db->fetch();
				$db->close();

				Doc::Bind('from', Text::variable_to_string('primrix_site_meta'));

//check if and why we use $dbp (null was passed as $dbp is never used)
$primrixClass = new Primrix(null);
				$primrixClass->{$row['editor']}('primrix_site_meta', $id, $row['name'], $row['notes'], $row['value'], $row['options'], $row['validation'], $returnURL, false);

			}
			else Address::go($returnURL);

		}

		public function editMetaProfile()
		{
			$pageID = Uri::$array[3];
			
			$db = new DB;
			$db->select("`name`");
			$db->where("`id` = '{$pageID}'");
			$db->query('primrix_site_pages');
			if($db->numRows() == 1) $row = $db->fetch();
			else Address::go('/admin/content-centre/page-meta/' . $pageID);

			Doc::bind('pageName', $row['name']);

			ContentCentreModel::buildMetaRepositoryTable($pageID);

			if(Form::submit('cancel')) Address::go('/admin/content-centre/page-meta/' . $pageID);

			if(Form::submit('save')){

				$db = new DB;
				if(!isset($_POST['id'])) $_POST['id'] = array();

				//loop through additions and add
				foreach($_POST['id'] as $id){
					$insertArray = array();
					
					$db->select("`id`");
					$db->where("`meta_id` = '{$id}' AND `page_id` = '{$pageID}'");
					$db->query('primrix_site_meta');
					if($db->numRows(true) == 0){

						$db->select("`id`, `default`");
						$db->where("`id` = '{$id}'");
						$db->query('primrix_meta_blueprint');
						$metaRow = $db->fetch();
						$db->close();
					
						$insertArray['id'] = $db->nextIndex('primrix_site_meta', 'id');
						$insertArray['page_id'] = $pageID;
						$insertArray['meta_id'] = $id;
						$insertArray['value'] = $metaRow['default'];
						$db->insert('primrix_site_meta', $insertArray);
					
					}
				}

				$dbDel = new DB;

				//then loop through what we have and remove
				$db->orderBy("`order` ASC");
				$db->query('primrix_meta_blueprint');
				while($metaRow = $db->fetch()){
					if(!in_array($metaRow['id'], $_POST['id'])){
						$dbDel->where("`page_id` = '{$pageID}' AND `meta_id` = '{$metaRow['id']}'");
						$dbDel->deleteRow('primrix_site_meta');
					}
				}
				$db->close();		
				Address::go('/admin/content-centre/page-meta/' . $pageID);
			}

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'pagemeta_edit.html', 'main/views/admin/modules/content_centre/');
		}
		
		public function pageBlueprints()
		{
			if(Form::submit('delete')){
				$db = new DB;
				if(isset($_POST['id'])) $db->deleteRowByArray('primrix_page_blueprint', 'id', $_POST['id']);
				Address::go('/admin/content-centre/page-blueprints');
			}

			ContentCentreModel::buildPageBlueprintTable();

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'pageblueprints.html', 'main/views/admin/modules/content_centre/');
		}

	}

//!EOF : /main/application/controllers/admin/contentcentre.php