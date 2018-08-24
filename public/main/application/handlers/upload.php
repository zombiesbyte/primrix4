<?php namespace main\application\handlers;

/**
 * Upload
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	
	class Upload
	{
		/**
		 * Upload a file using this method.
		 * @param  string  $field The POST field name
		 * @param  string  $path The target path
		 * @param  string  $filename The target filename
		 * @param  integer $maxBytes Default maximum 2621440 = 2.5Mb
		 * @param  array  $filetypes an array of allowable filetypes
		 * @param  boolean $overwrite allow overwrite if file exists
		 * @return [type] [description]
		 */
		public static function file($field, $path, $filename = null, $maxBytes = null, $filetypes = null, $overwrite = true)
		{
			
			if($maxBytes == '') $maxBytes = 2621440;
			//get file array information
			$errors = $_FILES[$field]['error'];
			$originalFile = basename($_FILES[$field]['name']);
			$serverFile = $_FILES[$field]['tmp_name'];
			$fileSize = File::formatFilesize($_FILES[$field]['size']);
			$fileSizeBytes = $_FILES[$field]['size'];
			$fileExt = File::extension($originalFile);
			if($filename == null) $filename = $originalFile;
			//else $filename = $filename . $fileExt;

			$env = Def::$primrix->environment;
			$siteDir = Def::$primrix->settings->{$env}->site_dir;
			$rootPath = Def::$primrix->settings->{$env}->rootpath;
			$fullServerPath = $rootPath . $siteDir;

			$filename = Text::normalise($filename);

			$maxBytesLabel = File::formatFilesize($maxBytes);

			if($filetypes == null or File::validateExt($fileExt, $filetypes)){

				if($fileSizeBytes <= $maxBytes){

					if(Form::errorCodes($field, $errors)){

						if(substr($path, 0, strlen($fullServerPath)) != $fullServerPath) $path = $fullServerPath . $path;
						if(!is_dir($path)) File::createDirectory($path); //if the folder path does not exist then create it (recursive)

						$target = $path . $filename;

						if(!is_file($target) or (is_file($target) and $overwrite)){
							
							if(move_uploaded_file($serverFile, $target)){

								//still to do! $this->image->create_thumbnail($this->target_path,$this->target_file, $this->ext);

							}
							else Form::setErrors($field, $field, '', 19);

						}
						else Form::setErrors($field, $field, '', 18);

					} //the errors are already being handled for this fail

				}
				else Form::setErrors($field, $field, $maxBytesLabel, 11);

			}
			else Form::setErrors($field, $field, '', 17);

			return true;
		}

	}

//!EOF : /main/application/handlers/upload.php