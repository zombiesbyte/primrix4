<?php namespace main\application\handlers;

/**
 * Image
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	use \site\config\Def;
	
	class Image
	{
		public static function getDimensions($file)
		{
			if(is_file($file)){
				if(!function_exists('exif_imagetype')){
					function exif_imagetype($file){
						if((list($width, $height, $type, $attr) = getimagesize($file)) !== false){
							return [$width, $height];
						}
						else return [0, 0];
					}
				}
				else{
					if(filesize($file) > 11 and exif_imagetype($file)){
						if((list($width, $height, $type, $attr) = getimagesize($file)) !== false){
							return [$width, $height];
						}
						else return [0, 0];
					}
					else return [0, 0];
				}
			}
			else return ['file not found', 'file not found'];
		}

		public static function createThumb($sourcePath, $sourceFile, $prefix)
		{
			$thumbSize_prefix = 'thumbSize_' . $prefix;
			$thumbTileWidth = Def::$primrix->settings->{$thumbSize_prefix};
			$thumbTileHeight = $thumbTileWidth;
			
			$thumbPlaqueColour = Def::$primrix->settings->thumbPlaqueColour;
			list($thumb_r, $thumb_g, $thumb_b) = self::hex2RGB($thumbPlaqueColour);
			
			$outputPath = $sourcePath . 'thumbs/';
			$outputFile = $prefix . '_' . $sourceFile;

			$modifier_width = 0;
			$modifier_height = 0;
			$thumb_yoffset = 0;
			$thumb_xoffset = 0;

			$targetExt = File::extension($sourceFile, false);

			$thumbRatio = Def::$primrix->settings->thumbRatio;
			
			if(!is_dir($outputPath)) File::createDirectory($outputPath); //if the folder path does not exist then create it (recursive)
			
			// Get new dimensions
			list($master_width, $master_height) = getimagesize($sourcePath . $sourceFile);
			
			if($master_width > $master_height) $orient = 1; //landscape
			else if($master_width < $master_height) $orient = 2; //portrait
			else $orient = 3; //square
			
			if($master_width < $thumbTileWidth) $modifier_width = $thumbTileWidth - $master_width;
			if($master_height < $thumbTileHeight) $modifier_height = $thumbTileHeight - $master_height;                
			
			//echo "master_height: {$master_height} - thumbTileHeight: {$thumbTileHeight}<br>";
		   
			if($thumbRatio == 'shrink'){                    
				if($orient == 1){
					$thumb_height = $master_height * ($thumbTileWidth - $modifier_width) / $master_width;
					$thumb_width = ($thumbTileWidth - $modifier_width);
					if($thumb_width < $thumbTileWidth) $thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					else $thumb_xoffset = 0;
					$thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
				}
				else if($orient == 2){
					$thumb_width = $master_width * ($thumbTileHeight - $modifier_height) / $master_height;
					$thumb_height = ($thumbTileHeight - $modifier_height);
					$thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					if($thumb_height < $thumbTileHeight) $thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
					else $thumb_yoffset = 0;
				}
				else{
					if($thumbTileWidth > $thumbTileHeight){
						$thumb_height = ($thumbTileHeight - $modifier_height);
						$thumb_width = $thumb_height;
					}
					else {
						$thumb_width = ($thumbTileWidth - $modifier_width);
						$thumb_height = $thumb_width;                   
					}
					if($thumb_width < $thumbTileWidth) $thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					else $thumb_xoffset = 0;
					if($thumb_height < $thumbTileHeight) $thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
					else $thumb_yoffset = 0;
				}
			}
			else if($thumbRatio == 'full'){
				if($orient == 2){   //switched processes from landscape to portrait
					$thumb_height = $master_height * ($thumbTileWidth - $modifier_width) / $master_width;
					$thumb_width = ($thumbTileWidth - $modifier_width);
					$thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					if($thumb_height < $thumbTileHeight) $thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
					else $thumb_yoffset = 0;
				}
				else if($orient == 1){  //switched processes from portrait to landscape
					$thumb_width = $master_width * ($thumbTileHeight - $modifier_height) / $master_height;
					$thumb_height = ($thumbTileHeight - $modifier_height);
					$thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
					if($thumb_width < $thumbTileWidth) $thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					else $thumb_xoffset = 0;                        
				}
				else{
					if($thumbTileWidth > $thumbTileHeight){
						$thumb_height = ($thumbTileHeight - $modifier_height);
						$thumb_width = $thumb_height;
					}
					else {
						$thumb_width = ($thumbTileWidth - $modifier_width);
						$thumb_height = $thumb_width;                  
					}
					if($thumb_width < $thumbTileWidth) $thumb_xoffset = round(($thumbTileWidth - $thumb_width) / 2);
					else $thumb_xoffset = 0;
					if($thumb_height < $thumbTileHeight) $thumb_yoffset = round(($thumbTileHeight - $thumb_height) / 2);
					else $thumb_yoffset = 0;
				}
			}
			
			// Resample jpg
			$image_base = imagecreatetruecolor($thumbTileWidth, $thumbTileHeight);
			$grey = imagecolorallocate($image_base, $thumb_r, $thumb_g, $thumb_b);
			imagefill($image_base, 0, 0, $grey);
			
			if($targetExt == 'jpg' or $targetExt == 'jpeg'){
				$thumb_image = imagecreatefromjpeg($sourcePath . $sourceFile);
				imagecopyresampled($image_base, $thumb_image, $thumb_xoffset, $thumb_yoffset, 0, 0, $thumb_width, $thumb_height, $master_width, $master_height);
				imagejpeg($image_base, $outputPath . $outputFile, 100);
			}
			else if($targetExt == 'gif'){
				$thumb_image = imagecreatefromgif($sourcePath . $sourceFile);
				imagecopyresampled($image_base, $thumb_image, $thumb_xoffset, $thumb_yoffset, 0, 0, $thumb_width, $thumb_height, $master_width, $master_height);
				imagegif($image_base, $outputPath . $outputFile);
			}
			else if($targetExt == 'png'){
				$thumb_image = imagecreatefrompng($sourcePath . $sourceFile);
				imagecopyresampled($image_base, $thumb_image, $thumb_xoffset, $thumb_yoffset, 0, 0, $thumb_width, $thumb_height, $master_width, $master_height);
				imagepng($image_base, $outputPath . $outputFile, 9);
			}
		}

		public static function hex2RGB($hex)
		{
			$hex = str_replace('#', '', $hex);
			$hex = str_replace('&num;', '', $hex);
			$r = 0;
			$g = 0;
			$b = 0;
			
			if(strlen($hex) == 3){ //for that annoying shorthand version
				$r = hexdec(substr($hex,0,1).substr($hex,0,1));
				$g = hexdec(substr($hex,1,1).substr($hex,1,1));
				$b = hexdec(substr($hex,2,1).substr($hex,2,1));
			}
			else if(strlen($hex) == 6){
				$r = hexdec(substr($hex,0,2));
				$g = hexdec(substr($hex,2,2));
				$b = hexdec(substr($hex,4,2));
			}
			return array($r, $g, $b); //return with rgb
		}

		public static function rotate($filename, $angle)
		{
			if(is_file($filename)){
				$Ext = File::extension($filename, false);
				
				if($Ext == 'jpg' or $Ext == 'jpeg'){
					$source = imagecreatefromjpeg($filename);
					$rotate = imagerotate($source, $angle, 0);
					imagejpeg($rotate, $filename, 100);
				}
				else if($Ext == 'png'){
					$source = imagecreatefrompng($filename);
					$rotate = imagerotate($source, $angle, 0);
					imagepng($rotate, $filename, 0);
				}
				else if($Ext == 'gif'){
					$source = imagecreatefromgif($filename);
					$rotate = imagerotate($source, $angle, 0);
					imagegif($rotate, $filename);
				}
			}
		}



		/**
		 * resize an image. This method resamples the original and at this time doesn't support
		 * transparent or animated images. The forceRatio flag at present does nothing.
		 * @param  [type] $filename   [description]
		 * @param  [type] $newWidth   [description]
		 * @param  [type] $newHeight  [description]
		 * @param  string $forceRatio w/h/n by (w)idth, (h)eight, (n)one
		 * @return [type]             [description]
		 */
		public static function resize($filename, $newWidth, $newHeight, $forceRatio = 'w')
		{
			$thumbPlaqueColour = Def::$primrix->settings->thumbPlaqueColour;
			list($baseR, $baseG, $baseB) = self::hex2RGB($thumbPlaqueColour);
			
			$modifierWidth = 0;
			$modifierHeight = 0;
			$widthCheck = false;
			$heightCheck = false;
			
			$ext = File::extension($filename, false);
			$extAllowed = ['jpg', 'jpeg', 'gif', 'png'];

			if(File::validateExt($ext, $extAllowed)){

				// Get new dimensions
				list($originalWidth, $originalHeight) = getimagesize($filename);
				
				if($originalWidth > $originalHeight) $orient = 1; //landscape
				else if($originalWidth < $originalHeight) $orient = 2; //portrait
				else $orient = 3; //square
				
				if($originalWidth < $newWidth) $modifierWidth = $newWidth - $originalWidth;
				if($originalHeight < $newHeight) $modifierHeight = $newHeight - $originalHeight;

				if($newWidth > 0) $widthCheck = true;
				if($newHeight > 0) $heightCheck = true;
				
				if(!$widthCheck or !$heightCheck){
					if($orient != 3){
						if(!$widthCheck and $heightCheck){
							$newWidth = $originalWidth * ($newHeight - $modifierHeight) / $originalHeight;
						}
						else if(!$heightCheck){
							$newHeight = $originalHeight * ($newWidth - $modifierWidth) / $originalWidth;
						}
					}
					else if($orient == 3){
						if(!$widthCheck and $heightCheck){
							$newWidth = $newHeight;
						}
						else if(!$heightCheck){
							$newHeight = $newWidth;
						}
					}
				}
				
				if($orient == 1){
					$imageHeight = $originalHeight * ($newWidth - $modifierWidth) / $originalWidth;
					$imageWidth = ($newWidth - $modifierWidth);
					if($imageWidth < $newWidth) $imageXOffset = round(($newWidth - $imageWidth) / 2);
					else $imageXOffset = 0;
					$imageYOffset = round(($newHeight - $imageHeight) / 2);
				}
				else if($orient == 2){
					$imageWidth = $originalWidth * ($newHeight - $modifierHeight) / $originalHeight;
					$imageHeight = ($newHeight - $modifierHeight);
					$imageXOffset = round(($newWidth - $imageWidth) / 2);
					if($imageHeight < $newHeight) $imageYOffset = round(($newHeight - $imageHeight) / 2);
					else $imageYOffset = 0;
				}
				else{
					if($newWidth > $newHeight){
						$imageHeight = ($newHeight - $modifierHeight);
						$imageWidth = $imageHeight;
					}
					else {
						$imageWidth = ($newWidth - $modifierWidth);
						$imageHeight = $imageWidth;                   
					}
					if($imageWidth < $newWidth) $imageXOffset = round(($newWidth - $imageWidth) / 2);
					else $imageXOffset = 0;
					if($imageHeight < $newHeight) $imageYOffset = round(($newHeight - $imageHeight) / 2);
					else $imageYOffset = 0;
				}

				// Resample jpg
				$imageBase = imagecreatetruecolor($newWidth, $newHeight);
				$grey = imagecolorallocate($imageBase, $baseR, $baseG, $baseB);
				imagefill($imageBase, 0, 0, $grey);
				
				if($ext == 'jpg' or $ext == 'jpeg'){
					$newImage = imagecreatefromjpeg($filename);
					imagecopyresampled($imageBase, $newImage, $imageXOffset, $imageYOffset, 0, 0, $imageWidth, $imageHeight, $originalWidth, $originalHeight);
					imagejpeg($imageBase, $filename, 100);
				}
				else if($ext == 'gif'){
					$newImage = imagecreatefromgif($filename);
					imagecopyresampled($imageBase, $newImage, $imageXOffset, $imageYOffset, 0, 0, $imageWidth, $imageHeight, $originalWidth, $originalHeight);
					imagegif($imageBase, $filename);
				}
				else if($ext == 'png'){
					$newImage = imagecreatefrompng($filename);
					imagecopyresampled($imageBase, $newImage, $imageXOffset, $imageYOffset, 0, 0, $imageWidth, $imageHeight, $originalWidth, $originalHeight);
					imagepng($imageBase, $filename, 0);                
				}
			}
		}		
	}

//!EOF : /main/application/handlers/image.php