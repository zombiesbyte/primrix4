<?php namespace main\application\handlers;

/**
 * File
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	
	class File
	{
		/**
		 * Extension will extract the extension from a given file name.
		 * @param  string  $filename filename (can include path)
		 * @param  boolean $withDot will return a .ext as default
		 * @return string extension
		 */
		public static function extension($filename, $withDot = true)
		{
			$filenameArray = explode('.', $filename);
			if($withDot) return '.' . end($filenameArray);
			else return end($filenameArray);
		}

		/**
		 * Check for the ext against an extArray. If the extension is found
		 * then this method will return true.
		 * @param  string $ext with or without a dot
		 * @param  array $extArray array of extensions with or without dots
		 * @return boolean true if matched
		 */
		public static function validateExt($ext, $extArray)
		{
			if($ext[0] != '.') $ext = '.' . $ext;
			foreach($extArray as $allowedExt){
				if($allowedExt[0] == '.' and $ext == $allowedExt) return true;
				else if($allowedExt[0] != '.' and $ext == '.' . $allowedExt) return true;
			}

			return false;
		}

		/**
		 * Converts a integer byte and converts it to a more appropriate human readable
		 * value with labels for b, Kb or Mb.
		 * @param  integer $filesize passed filesize in bytes
		 * @return string formated filesize in b, Kb or Mb sizes
		 */
		public static function formatFilesize($filesize)
		{
			$filesize_bytes = round($filesize); //get file size in bytes
			$filesize_kbytes = round(($filesize / 1024), 1); //get file size in kilobytes
			$filesize_mbytes = round((($filesize / 1024) / 1024), 2); //get file size in megabytes            
			if($filesize_bytes > 1023){
				if($filesize_kbytes > 1023){
					$filesize = $filesize_mbytes . 'Mb';
				}
				else $filesize = $filesize_kbytes . 'Kb';
			}
			else $filesize = $filesize_bytes . 'b';
			return($filesize);        
		}

		/**
		 * PHP Manual provided this function (Thank you)
		 * Bug as mentioned by peter [29-Jul-2008 09:25]
		 * @param  string $val string bytes
		 * @return integer bytes
		 */
		public static function returnBytes($val)
		{
			$val = trim($val);
			$last = substr($val, strlen($val / 1), 1);
			$last = strtolower($last);
			switch($last){
				case 'g': $val *= 1024;
				case 'm': $val *= 1024;
				case 'k': $val *= 1024;
			}
			return $val;
		}

		//recursive directory creation
		/**
		 * Creates folders (recursively).
		 * @param  string $path The path to check/create
		 * @return none
		 */
		public static function createDirectory($path)
		{
			$targetAccumlitive = "";
			$targetBranch = explode('/',$path);
			foreach($targetBranch as $branch){
				$targetAccumlitive .= $branch . '/';
				if($targetAccumlitive != '/'){
					if(!is_dir($targetAccumlitive)) mkdir($targetAccumlitive);
				}
			}
		}

		public static function delete($filepath)
		{
			if(is_file($filepath)){
				unlink($filepath);
				return true;
			}
			else return false;
		}

		/**
		 * This is a recursive method that scans the directory path provided
		 * and removes the files within. Should there be further directories
		 * within the filepath then we recall recursively until we get to the
		 * empty parent directory which is then removed
		 * @param string $path full path to file or directory
		 * @return boolean return state
		 */
		public static function removeDirectory($path)
		{
			$file_list = scandir($path);
			$file_list = array_diff($file_list, array('.','..')); 
			foreach($file_list as $file){
				if(is_dir($path . '/' . $file))	self::removeDirectory($path . '/' . $file);
				else unlink($path . '/' . $file);
			}
			if(rmdir($path)) return(true);
			else return(false);
		}

		public static function move($fileSource, $fileDestination, $allowOverwrites = true)
		{
			if($allowOverwrites){
				rename($fileSource, $fileDestination); //this function always overwrites if the target exists
				return true;
			}
			else{				
				if(is_file($fileSource)) return false;
				else rename($fileSource, $fileDestination);
				return true;
			}
		}

		/**
		 * alias of move
		 * @param  string  $fileName path/filename (original)
		 * @param  string  $newFileName path/filename (new)
		 * @return boolean returns true on completion
		 */
		public static function rename($fileName, $newFileName)
		{
			return self::move($fileName, $newFileName, true);
		}

		public static function copy($sourceFile, $newFile, $allowOverwrites = true)
		{
			if($allowOverwrites){
				copy($sourceFile, $newFile); //this function always overwrites if the target exists
				return true;
			}
			else{				
				if(is_file($newFile)) return false;
				else copy($sourceFile, $newFile);
				return true;
			}
		}

		public static function getFileList($location, $fileTypesArray = null, $order = 'asc')
		{
			if(substr($location, -1) != '/') $location .= '/'; //we need to make sure that the location ends with forward slash
			$fileList = scandir($location);
			
			unset($fileInfoArray);
			$fileInfoArray = array();
			$fileInfoArray['totalFiles'] = 0;
			$fileInfoArray['totalDirSize_b'] = 0;

			$ab_length = Def::$primrix->settings->filename_ab_length;

			if(strtolower($order) == 'asc') sort($fileList);
			else if(strtolower($order) == 'desc') rsort($fileList);
			
			//check if the types are csv formatted
			if($fileTypesArray != null){
				if(!is_array($fileTypesArray)){
					$temp = explode(',',$fileTypesArray);
					$fileTypesArray = $temp;
				}
			}
			
			foreach($fileList as $filenameExt){
				$ext = self::extension($filenameExt, false);
				$filename = substr($filenameExt , 0 , - strlen($ext));
 				
 				if($fileTypesArray == null or in_array(strtolower($ext) , $fileTypesArray)){ //only show specified file extensions
					if(!is_dir($filenameExt) and is_file($location . $filenameExt) and $filenameExt != ''){ //make sure this is a file

						$fileInfoArray['filename'][] = substr($filename,0,-1);
						$fileInfoArray['filename_ext'][] = $filenameExt;
						$fileInfoArray['ext'][] = $ext;
						$fileInfoArray['totalFiles']++; //count files
						
						//get the file size in both (b)ytes and (f)ormated version i.e. Mb
						$fileInfoArray['fileSize_b'][] = self::getFilesize($location . $filenameExt);
						$fileInfoArray['fileSize_f'][] = self::getFilesize($location . $filenameExt, true);

						$fileInfoArray['totalDirSize_b'] += self::getFilesize($location . $filenameExt);

						//get the image size for width + height
						$dim = Image::getDimensions($location . $filenameExt);
						$fileInfoArray['dim_x'][] = $dim[0];
						$fileInfoArray['dim_y'][] = $dim[1];

						//we check that the thumb exists if this is an image
						if($dim[0] > 0){
							if(!is_file($location . 'thumbs/s_' . $filenameExt)) Image::createThumb($location, $filenameExt, 's');
							if(!is_file($location . 'thumbs/m_' . $filenameExt)) Image::createThumb($location, $filenameExt, 'm');
							if(!is_file($location . 'thumbs/l_' . $filenameExt)) Image::createThumb($location, $filenameExt, 'l');
						}

						//set abbreviated filenames (why is abbreviated such a long word!?)
						if(strlen($filenameExt) > $ab_length){
							$abb_filename = substr($filename , 0 , $ab_length) . '...';
							$fileInfoArray['filename_ab'][] = $abb_filename;
						}
						else{
							$fileInfoArray['filename_ab'][] = substr($filename,0,-1);
						}
				
					}
				}
			}

			//create a formatted string of of the total directory size
			$fileInfoArray['totalDirSize_f'] = self::formatFilesize($fileInfoArray['totalDirSize_b']);

			//we update the session array with our findings
			$_SESSION['lastFileInfo'] = array();
			$_SESSION['lastFileInfo'] = $fileInfoArray;

			return $fileInfoArray;        
		}

		public static function getDirectoryList($location, $order = 'asc')
		{
			$dirList = scandir($location);
			unset($dirInfoArray);
			$dirInfoArray = array();
			$dirInfoArray['totalDirs'] = 0;

			$ab_length = Def::$primrix->settings->filename_ab_length;

			if(strtolower($order) == 'asc') sort($dirList);
			else if(strtolower($order) == 'desc') rsort($dirList);

			foreach($dirList as $dirname){
				if(!is_dir($dirname) and !is_file($location . $dirname) and $dirname != ''){ //make sure this is a file

					$dirInfoArray['dirname'][] = $dirname;
					$dirInfoArray['totalDirs']++; //count files
					
					//set abbreviated dirnames (why is abbreviated such a long word!?)
					if(strlen($dirname) > $ab_length){
						$abb_dirname = substr($dirname , 0 , $ab_length) . '...';
						$dirInfoArray['dirname_ab'][] = $abb_dirname;
					}
					else{
						$dirInfoArray['dirname_ab'][] = $dirname;
					}
			
				}
			}

			//we update the session array with our findings
			$_SESSION['lastDirInfo'] = array();
			$_SESSION['lastDirInfo'] = $dirInfoArray;

			return $dirInfoArray; 			
		}

		public static function recursiveDirectoryList($origin)
		{
			$crawlArray = array();
			$path = \realpath($origin);

			$realPathArray = explode('\\', $path);
			$realPathSeg = count($realPathArray);

			$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
			foreach($objects as $name => $object){
				if(!is_file($name)){
					$pathArray = explode('\\', $name);
					$pathArrayCount = count($pathArray);
					if(end($pathArray) != '.' and end($pathArray) != '..' and end($pathArray) != 'thumbs'){
						$folder = "";
						for($n = $realPathSeg; $n < $pathArrayCount; $n++){
							$folder .= '/' . $pathArray[$n];
						}
						$crawlArray[] = $folder;
					}

				}
			}

			return $crawlArray;	
		}

		public static function getFilesize($file, $format = false) //file path + name | format: true(will return 186b/199Kb/1.3Mb), false(will always return bytes as a int)
		{
			//check that the provided path includes the full server absolute path and is not just relative
			//if(substr($file,0,strlen(SERVER_BASE . SITE_DIR)) != SERVER_BASE . SITE_DIR) $file = SERVER_BASE . SITE_DIR . $file;            
			if(is_file($file)){
				$filesize = filesize($file);
				if($format) return self::formatFilesize($filesize);
				else return $filesize;
			}
			else return false;
		}

		public static function offerDownload($file)
		{
			header('Content-Description: File Transfer');
    		header('Content-Type: application/octet-stream');
    		header('Content-Disposition: attachment; filename='.basename($file));
    		header('Expires: 0');
    		header('Cache-Control: must-revalidate');
    		header('Pragma: public');
    		header('Content-Length: ' . filesize($file));
    		readfile($file);
    		exit;
		}
	}

//!EOF : /main/application/handlers/file.php