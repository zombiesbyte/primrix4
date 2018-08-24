<?php namespace site\application\controllers\site;

/**
 * DefaultController
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */
	
	use \main\application\controllers\controller;
	use \main\application\handlers\DB;
	use \main\application\handlers\Uri;
	use \main\application\handlers\Doc;
	use \main\application\handlers\HTML;
	use \main\application\handlers\Form;
	use \main\application\handlers\User;
	use \main\application\handlers\Auth;
	use \main\application\handlers\Address;
	use \main\application\handlers\Chronos;
	use \main\application\handlers\Captcha;
	use \main\application\handlers\Text;
	use \site\config\Def;
	
	class DefaultController extends Controller
	{
		public $dbp;

		public function __construct($dbp)
		{
			parent::__construct();
			//\help::pre($dbp);
			//prints
			//[id] => 1
			//[order] => 1
			//[uri] => /
			//[allow_params] => y
			//[name] => Home
			//[template] => 
			//[rights] => 1
			//[created_at] => 0000-00-00 00:00:00
			//[updated_at] => 2015-10-04 16:53:26
			//[updated_by] => 1
			//[active] => y
			
			$this->dbp = $dbp;

		}

		public function index()
		{
			$pageData = array();

			$db1 = new DB;
			$db2 = new DB;
			$db1->select("`id`,`page_id`,`group`,`cluster_ref`,`duplication_allowed`,`use_html_block`,`cluster_html`");
			$db1->where("`page_id` = '{$this->dbp['id']}'");
			$db1->query('primrix_site_clusters');
			while($cluster = $db1->fetch()){
			
				if($cluster['use_html_block'] == 'y') $pageData[$cluster['cluster_ref']]['html'] = true;
				else $pageData[$cluster['cluster_ref']]['html'] = false;


				//\help::pre($cluster);
				//
				
				$tableGrps = array();

				$db2->select("`page_id`,`cluster_group`,`table_group`,`group`,`name`,`value`");
				$db2->where("`page_id` = '{$cluster['page_id']}' AND `cluster_group` = '{$cluster['group']}'");
				$db2->orderBy("`table_group` ASC");
				$db2->query('primrix_site_content');
				while($content = $db2->fetch()){
					
					$tableGrps[$content['table_group']] = $content['table_group'];

					if($pageData[$cluster['cluster_ref']]['html']){
						$pageData[$cluster['cluster_ref']][$content['table_group']]['html'] = "<!--" . Form::fromHTML($cluster['cluster_html']) . "-->";
					}

					$pageData[$cluster['cluster_ref']][$content['table_group']][$content['name']] = $content['value'];

					

					//\help::pre($content);
					//
				}	
				$db2->close();

				foreach($tableGrps as $n){
					foreach($pageData[$cluster['cluster_ref']][$n] as $var => $value){
						if(isset($pageData[$cluster['cluster_ref']][$n]['html'])){
							$pageVar = Def::$primrix->settings->prefix . $var . Def::$primrix->settings->suffix;
							$pageData[$cluster['cluster_ref']][$n]['html'] = preg_replace('/' . $pageVar . '/', $value, $pageData[$cluster['cluster_ref']][$n]['html']);
						}
					}
				}				

			}
			$db1->close();

			\help::pre($pageData);

			/*$db->where("`{$db->prefix}primrix_site_pages`.`id` = '{$this->dbp['id']}'");
			$db->orderBy("`{$db->prefix}primrix_site_clusters`.`group` ASC");
			$db->join(array('primrix_site_pages','primrix_site_clusters','primrix_site_content'), array('id','page_id','page_id'), 'JOIN');
			echo "numRows: " . $db->numRows();
			while($page = $db->fetch()){
				\help::pre($page);
			}
			$db->close();
			*/


			/*
			
			My temporary notes to work out stuff

			{cluster} - pulls the entire cluster and loops through the tables available
			{cluster:variable} - pulls the variable name within cluster from each table
			{cluster[1]} - pulls the first table of the cluster
			{cluster[3]} - pulls the third table of cluster
			{cluster[1]:variable} - pulls the variable name within the first table
			{cluster[2]:variable} - pulls the variable name within the second table


			Images
			{cluster:myimage} - /images/myimage.jpg
			{cluster:myimage:alt} - The alternative text of an image
			{cluster:myimage:link} - The link (if applicable) of an image
			{cluster:myimage:title} - The title of an image
			{cluster:myimage:path} - /images/
			{cluster:myimage:filename} - myimage.jpg
			{cluster:myimage:width} - width (int only)
			{cluster:myimage:height} - height (int only)
			{cluster:myimage:sThumb} - /images/thumbs/s_myimage.jpg
			{cluster:myimage:mThumb} - /images/thumbs/m_myimage.jpg
			{cluster:myimage:lThumb} - /images/thumbs/l_myimage.jpg
			{cluster:myimage:html: optional :    optional    }
			{cluster:myimage:html:id[someID]:class[someClass]} - <img src='/site/files/images/myimage.jpg' alt='%%' title='%%' width='%%' height='%%' id='someID' class='someClass'>
			
			long hand
			<img src='/site/files{cluster:myimage}' alt='{cluster:myimage:alt}' title='{cluster:myimage:alt}' width='{cluster:myimage:width}' height='{cluster:myimage:height}'>
			
			

			 */
			
		}

		public function mymethod()
		{
			echo "<br>My Method";
		}

	}