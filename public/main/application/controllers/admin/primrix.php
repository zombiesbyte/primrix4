<?php namespace main\application\controllers\admin;

/**
 * Primrix
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

	use \site\config\Def;

	use \main\application\models\admin\UserModel;
	use \main\application\models\admin\MenuManagerModel;
	use \main\application\models\admin\SiteOptionsModel;

	use \main\application\models\cms\RightsModel;

	class Primrix extends Controller
	{
		
		public function __construct($dbp)
		{
			parent::__construct();

			if(!isset($_SESSION['primrix']['user'])) Address::go('/admin/login'); //security check			
			UserModel::checkLoginSession(); //session time check
			UserModel::setVariables(); //general variable replacements
			MenuManagerModel::buildMenus();
		}

		public function newVariable2()
		{
			$table = Uri::$array[3];
			$returnURL = Address::decode(Uri::$array[4]);

			if(isset(Uri::$array[5])){
				$passedFieldValues = Address::decode(Uri::$array[5]);
				$FieldValuesArray = explode(',', $passedFieldValues);
				for($n = 0; $n < count($FieldValuesArray); $n += 2){
					$newRow[ $FieldValuesArray[$n] ] = $FieldValuesArray[$n+1];
				}
			}

			$rules = [
				'fieldType' => 	'1|1|alpha|select',
				'fieldName' => 	'3|64|varname|text',
				'fieldDesc' => 	'3|255|basic|textarea',
				'fieldOptions' => '0|2000|rtf|textarea',
				'fieldSet' =>	'1|1|alphanum|select',
				'fieldMin' =>	'1|6|num|text',
				'fieldMax' =>	'1|6|num|text'
			];

			$ruleSetKeys = array();
			foreach(Def::$ruleSet['presets'] as $key => $array){
				$ruleSetKeys[] = $key;
			}

			$fieldSetOptions = Form::buildHTML('select', 'fieldSet', $ruleSetKeys, $_POST, true);
			Doc::bind('fieldSetOptions', $fieldSetOptions);

			Form::setData($rules);

		}

		/**
		 * Create a new variable that is compatible with the standard editors.
		 * Passing the table, a encoded return url and optional group id and passive
		 * values. Please note the passive values will simply be read as comma separated
		 * field,value pairs which is an encoded param. This is not intended to handle
		 * complicated values and it is recommended for use only with a short series of
		 * table column names and ids. It is also worth mentioning that the order column is
		 * not updated when replicating entries due to the complex querying that it can have.
		 * If you need to create an order then this can be worked out and passed through using
		 * the encoded field,value pairs.
		 * @return null This method outputs to the db
		 */
		public function newVariable()
		{
			$newRow = array();
			
			//minimum requirements of table name and return URL
			$table = Uri::$array[3];
			$returnURL = Address::decode(Uri::$array[4]);

			//additional parameters of encoded passedFV's
			//please note this is required for replication to occur
			if(isset(Uri::$array[5])){
				$passedFieldValues = Address::decode(Uri::$array[5]);
				$FieldValuesArray = explode(',', $passedFieldValues);
				for($n = 0; $n < count($FieldValuesArray); $n += 2){
					$newRow[ $FieldValuesArray[$n] ] = $FieldValuesArray[$n+1];
				}
			}

			//if passedFV's are used then we look for replication information
			//The first segment informs us of the number of times replication should occur
			//The second segment informs us of the field that anchors our replication
			$replicateEntry = 0;
			if(isset(Uri::$array[6])){
				$replicateEntry = Uri::$array[6];
				$replicateField = Uri::$array[7];
			}

			$rules = [
				'fieldType' => 	'1|1|alpha|select',
				'fieldName' => 	'3|64|varname|text',
				'fieldDesc' => 	'3|255|basic|textarea',
				'fieldOptions' => '0|2000|rtf|textarea',
				'fieldSet' =>	'1|1|alphanum|select',
				'fieldMin' =>	'1|6|num|text',
				'fieldMax' =>	'1|6|num|text'
			];

			$ruleSetKeys = array();
			foreach(Def::$ruleSet['presets'] as $key => $array){
				$ruleSetKeys[] = $key;
			}

			$fieldSetOptions = Form::buildHTML('select', 'fieldSet', $ruleSetKeys, $_POST, true);
			Doc::bind('fieldSetOptions', $fieldSetOptions);

			Form::setData($rules);

			if(Form::submit('save')){

				//for this particular circumstance we provide the validation information
				//here so that our validator doesn't knock it back when we proceed
				if($_POST['fieldType'] == 'palette'){
					$_POST['fieldMin'] = 6;
					$_POST['fieldMax'] = 7;
					$_POST['fieldSet'] = 'hexhash';
				}
				else if($_POST['fieldType'] == 'image'){
					$_POST['fieldMin'] = 0;
					$_POST['fieldMax'] = 0;
					$_POST['fieldSet'] = 'filepath';
				}
				else if($_POST['fieldType'] == 'gallery'){
					$_POST['fieldMin'] = 0;
					$_POST['fieldMax'] = 0;
					$_POST['fieldSet'] = 'filepathcsv';
				}

				if(Form::validate($rules)){
					
					$db = new DB;

					if($replicateEntry > 0){

						for($n = 1; $n <= $replicateEntry; $n++){
							$newRow[$replicateField] = $n;
							$newRow['id'] = $db->nextIndex($table, 'id');
							$newRow['name'] = $_POST['fieldName'];
							$newRow['validation'] = $_POST['fieldMin'] . '|' . $_POST['fieldMax'] . '|' . $_POST['fieldSet'];
							$newRow['options'] = $_POST['fieldOptions'];
							$newRow['editor'] = $_POST['fieldType'];
							$newRow['notes'] = $_POST['fieldDesc'];
							$newRow['rights'] = 1; //default rights level
							$newRow['created_at'] = date('Y-m-d H:i:s');
							$newRow['updated_at'] = date('Y-m-d H:i:s');
							$newRow['updated_by'] = $_SESSION['primrix']['user']['id'];
							$db->insert($table, $newRow);
						}
					}
					else{
						$newRow['id'] = $db->nextIndex($table, 'id');
						$newRow['name'] = $_POST['fieldName'];
						$newRow['validation'] = $_POST['fieldMin'] . '|' . $_POST['fieldMax'] . '|' . $_POST['fieldSet'];
						$newRow['options'] = $_POST['fieldOptions'];
						$newRow['editor'] = $_POST['fieldType'];
						$newRow['notes'] = $_POST['fieldDesc'];
						$newRow['rights'] = 1; //default rights level
						$newRow['order'] = $db->nextIndex($table, 'order');
						$newRow['created_at'] = date('Y-m-d H:i:s');
						$newRow['updated_at'] = date('Y-m-d H:i:s');
						$newRow['updated_by'] = $_SESSION['primrix']['user']['id'];
						$db->insert($table, $newRow);
					}

					Address::go($returnURL);					  	 	 	 
				}
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'new_variable.html', 'main/views/cms/editors/');
		}

		public function editVariable()
		{
			$table = Uri::$array[3];
			$id = Uri::$array[4];
			$returnURL = Address::decode(Uri::$array[5]);
			
			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query($table);
			$row = $db->fetch();
			$db->close();
			
			$rules = [
				'fieldType' => 	'1|1|alpha|select',
				'fieldName' => 	'3|64|varname|text',
				'fieldDesc' => 	'3|255|rtf|textarea',
				'fieldOptions' => '0|2000|rtf|textarea',
				'fieldSet' =>	'1|1|alphanum|select',
				'fieldMin' =>	'1|6|num|text',
				'fieldMax' =>	'1|6|num|text'
			];

			$validation = explode('|', $row['validation']);

			$data = [
				'fieldType' => 	$row['editor'],
				'fieldName' => 	$row['name'],
				'fieldDesc' => 	$row['notes'],
				'fieldOptions' => $row['options'],
				'fieldSet' =>	$validation[2],
				'fieldMin' =>	$validation[0],
				'fieldMax' =>	$validation[1]
			];

			$ruleSetKeys = array();
			foreach(Def::$ruleSet['presets'] as $key => $array){
				$ruleSetKeys[] = $key;
			}

			if(!Form::submit('save')) $_POST['fieldSet'] = $data['fieldSet'];
			$fieldSetOptions = Form::buildHTML('select', 'fieldSet', $ruleSetKeys, $_POST, true);
			Doc::bind('fieldSetOptions', $fieldSetOptions);

			Form::setData($rules, $data);

			if(Form::submit('save')){

				//for this particular circumstance we provide the validation information
				//here so that our validator doesn't knock it back when we proceed
				if($_POST['fieldType'] == 'palette'){
					$_POST['fieldMin'] = 6;
					$_POST['fieldMax'] = 7;
					$_POST['fieldSet'] = 'hexhash';
				}
				else if($_POST['fieldType'] == 'image'){
					$_POST['fieldMin'] = 0;
					$_POST['fieldMax'] = 0;
					$_POST['fieldSet'] = 'filepath';
				}
				else if($_POST['fieldType'] == 'gallery'){
					$_POST['fieldMin'] = 0;
					$_POST['fieldMax'] = 0;
					$_POST['fieldSet'] = 'filepathcsv';
				}

				if(Form::validate($rules)){
					
					$newRow = array();
					$newRow['name'] = $_POST['fieldName'];
					$newRow['validation'] = $_POST['fieldMin'] . '|' . $_POST['fieldMax'] . '|' . $_POST['fieldSet'];
					$newRow['options'] = $_POST['fieldOptions'];
					$newRow['editor'] = $_POST['fieldType'];
					$newRow['notes'] = $_POST['fieldDesc'];

					$newRow['updated_at'] = date('Y-m-d H:i:s');
					$newRow['updated_by'] = $_SESSION['primrix']['user']['id'];

					$db->where("`id` = '{$id}'");
					$db->update($table, $newRow);
					$db->close();
					Address::go($returnURL);					  	 	 	 
				}
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'new_variable.html', 'main/views/cms/editors/');
		}

		public function editor()
		{
			$table = Uri::$array[3];
			$id = Uri::$array[4];
			$returnURL = Address::decode(Uri::$array[5]);

			if(isset(Uri::$array[6])) $allowEdit = false;
			else $allowEdit = true;

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query($table);
			if($db->numRows() == 1){
				$row = $db->fetch();
				$db->close();

				Doc::Bind('from', Text::variable_to_string($table));

				$this->{$row['editor']}($table, $id, $row['name'], $row['notes'], $row['value'], $row['options'], $row['validation'], $returnURL, $allowEdit);

			}
			else Address::go($returnURL);

		}

		public function permissions()
		{
			$table = Uri::$array[3];
			$id = Uri::$array[4];
			$returnURL = Address::decode(Uri::$array[5]);

			$db = new DB;
			$db->where("`id` = '{$id}'");
			$db->query($table);
			if($db->numRows() == 1){
				$row = $db->fetch();
				$db->close();
				Doc::Bind('from', Text::variable_to_string($table));

				$this->editPermissions($table, $id, $row['name'], $row['rights'], $returnURL);
			}
			else Address::go($returnURL);			
		}

		public function editPermissions($table, $id, $fieldName, $fieldValue, $returnURL)
		{
			$db = new DB;

			$fieldValue = explode("\n", $fieldValue);

			$checkboxes = RightsModel::generateRightsCheckboxes($fieldValue);

			$rules = [
				'rights' => '1|0|rtf|check'
			];

			$data = [
				'rights' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){

				$checkboxes = RightsModel::generateRightsCheckboxes($_POST['rights']);
			
				if(Form::validate($rules)){
					$_POST['rights'] = RightsModel::screenRights($_POST['rights']);
					$db->where("`id` = '{$id}'");
					$db->update($table, ['rights' => $_POST['rights']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('checkboxes', $checkboxes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'permissions.html', 'main/views/cms/editors/');
		}		

		public function textbox($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|text'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'textbox.html', 'main/views/cms/editors/');
		}

		public function textarea($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;
			
			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|text'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'textarea.html', 'main/views/cms/editors/');
		}

		public function selectbox($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			//selectFieldOptions
			$db = new DB;

			$fieldOptions = explode("\n", $fieldOptions);

			$selectFieldOptions = Form::buildHTML('select', 'field', $fieldOptions, ['field' => $fieldValue], true);
			Doc::bind('selectFieldOptions', $selectFieldOptions);

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|select'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'selectbox.html', 'main/views/cms/editors/');
		}

		public function checkbox($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			//selectFieldOptions
			$db = new DB;

			$fieldOptions = explode("\n", $fieldOptions);
			$fieldOptions = array_filter($fieldOptions);
			$fieldValue = explode("\n", $fieldValue);

			$checkboxes = Form::buildHTML('check', 'field', $fieldOptions, ['field' => $fieldValue], true);
			Doc::bind('checkboxes', $checkboxes);

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|check'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'checkbox.html', 'main/views/cms/editors/');
		}		

		public function wysiwyg($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|text'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'wysiwyg.html', 'main/views/cms/editors/');
		}

		public function palette($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'field' => $fieldValidation . '|text'
			];

			$data = [
				'field' => $fieldValue
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_POST['field']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'palette.html', 'main/views/cms/editors/');
		}

		public function image($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;

			$fieldOptions = explode("\n", $fieldOptions);
			$allowedExt = "";
			$hasUrl = false;

			foreach($fieldOptions as $opt){
				if($opt == 'url') $hasUrl = true;
				else $allowedExt .= $opt . ',';
			}

			$allowedExt = Text::groom(',', $allowedExt);
			Doc::bind('extensions', $allowedExt);
			
			if($hasUrl == true) Doc::bind('hasLink', 'y');
			else Doc::bind('hasLink', 'n');

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'selectedFiles' => $fieldValidation . '|text'
			];

			if($fieldValue != '') $FieldValueArray = explode('/', $fieldValue);
			if(isset($FieldValueArray) and is_array($FieldValueArray)){
				$dataFilename = end($FieldValueArray);
				$dataPath = substr($fieldValue, 0, - (strlen($dataFilename) + 1));
				$dataFilename = Form::fromHTML($dataFilename);
			}
			else{
				$dataPath = Def::$primrix->settings->defaultFolder;
				$dataFilename = '';
			}

			$data = [
				'selectedFiles' => $dataFilename
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){
					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $_SESSION['assetManager']['folder'] . '/' . $_POST['selectedFiles']]);
					$db->close();
					Address::go($returnURL);
				}				
			}

			$_SESSION['assetManager']['folder'] = Form::fromHTML($dataPath); //predefined default
			if(Form::submit('folder')) $_SESSION['assetManager']['folder'] = $_POST['folder'];

			$directoryList = File::recursiveDirectoryList('site/files');
			$folderSelectList = Form::buildHTML('select', 'folder', $directoryList, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectList', $folderSelectList);

			if(Form::submit('viewSmall')) $_SESSION['assetManager']['viewStyle'] = 's';
			if(Form::submit('viewMedium')) $_SESSION['assetManager']['viewStyle'] = 'm';
			if(Form::submit('viewLarge')) $_SESSION['assetManager']['viewStyle'] = 'l';

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldPath', Form::fromHTML($dataPath));
			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'image.html', 'main/views/cms/editors/');
		}

		public function gallery($table, $id, $fieldName, $fieldNotes, $fieldValue, $fieldOptions, $fieldValidation, $returnURL, $allowEdit)
		{
			$db = new DB;

			$fieldOptions = explode("\n", $fieldOptions);
			$allowedExt = "";
			$hasUrl = false;

			foreach($fieldOptions as $opt){
				if($opt == 'url') $hasUrl = true;
				else $allowedExt .= $opt . ',';
			}

			$allowedExt = Text::groom(',', $allowedExt);
			Doc::bind('extensions', $allowedExt);
			
			if($hasUrl == true) Doc::bind('hasLink', 'y');
			else Doc::bind('hasLink', 'n');

			if($allowEdit){
				$editVariableLink = '/admin/primrix/edit-variable';
				$editVariableLink .= '/' . $table;
				$editVariableLink .= '/' . $id;
				$editVariableLink .= '/' . Address::encode(Uri::$uri);
				$editVariable = "<a href='{$editVariableLink}' class='right'><span class='fa fa-edit'></span></a>";
			}
			else $editVariable = "";
			
			Doc::bind('editVariable', $editVariable);

			$rules = [
				'selectedFiles' => $fieldValidation . '|text'
			];

			$dataPath = Def::$primrix->settings->defaultFolder;
			$dataFilename = '';
			$dataFilenameCSV = '';

			if($fieldValue != ''){
				$fieldValue = Form::fromHTML($fieldValue);
				$fieldValueArray = explode("\n", $fieldValue);

				foreach($fieldValueArray as $fv){

					if($fv != ''){
						$fvArray = explode('/', $fv);
					
						if(isset($fvArray) and is_array($fvArray)){
							$dataFilename = end($fvArray);
							$dataPath = Form::toHTML(substr($fv, 0, - (strlen($dataFilename) + 1)));
							$dataFilenameCSV .= Form::fromHTML($dataFilename) . ',';
						}
					}

				}
				$dataFilenameCSV = Text::groom(',', $dataFilenameCSV);
			}


			$data = [
				'selectedFiles' => $dataFilenameCSV
			];

			Form::setData($rules, $data);

			if(Form::submit('save')){
				
				if(Form::validate($rules)){

					$filepathList = '';
					if($_POST['selectedFiles'] != ''){
						$_POST['selectedFiles'] = Form::fromHTML($_POST['selectedFiles']);
						$selectedFilesArray = explode(',', $_POST['selectedFiles']);
						foreach($selectedFilesArray as $sf){
							$filepathList .= Form::toHTML($_SESSION['assetManager']['folder'] . '/' . $sf . "\n");
						}
					}

					$db->where("`id` = '{$id}'");
					$db->update($table, ['value' => $filepathList]);
					$db->close();
					Address::go($returnURL);
				}				
			}
			
			$_SESSION['assetManager']['folder'] = Form::fromHTML($dataPath); //predefined default
			if(Form::submit('folder')) $_SESSION['assetManager']['folder'] = $_POST['folder'];

			$directoryList = File::recursiveDirectoryList('site/files');
			$folderSelectList = Form::buildHTML('select', 'folder', $directoryList, $_SESSION['assetManager'], true);
			Doc::bind('folderSelectList', $folderSelectList);

			if(Form::submit('viewSmall')) $_SESSION['assetManager']['viewStyle'] = 's';
			if(Form::submit('viewMedium')) $_SESSION['assetManager']['viewStyle'] = 'm';
			if(Form::submit('viewLarge')) $_SESSION['assetManager']['viewStyle'] = 'l';

			if(Form::submit('cancel')) Address::go($returnURL);

			Doc::bind('fieldPath', Form::fromHTML($dataPath));
			Doc::bind('fieldNotes', $fieldNotes);
			Doc::bind('friendlyFieldName', Text::variable_to_string($fieldName));

			Doc::view('index.html', 'main/views/admin/');
			Doc::viewPart('worktop', 'gallery.html', 'main/views/cms/editors/');
		}
	}

//!EOF : /main/application/controllers/admin/primrix.php