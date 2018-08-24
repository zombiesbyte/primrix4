<?php namespace main\application\models\cms;

/**
 * AssetsModel
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
	use \main\application\handlers\Bunch;
	use \main\application\handlers\File;

	class AssetsModel
	{

		public static function getAssetTiles($path, $vS, $byIndex = null)
		{
			
			$thumbSize_prefix = 'thumbSize_' . $vS;
			$thumbTileWidth = Def::$primrix->settings->{$thumbSize_prefix};

			$tile = array();
			$html = '';

			if(!isset($_SESSION['lastFileInfo']['totalFiles']) or $_SESSION['lastFileInfo']['totalFiles'] <= 0) return false;

			if($byIndex != null){

				$tile['filename'] = $_SESSION['lastFileInfo']['filename'][ $byIndex ];
				$tile['filename_ext'] = $_SESSION['lastFileInfo']['filename_ext'][ $byIndex ];
				$tile['ext'] = $_SESSION['lastFileInfo']['ext'][ $byIndex ];					
				$tile['filename_ab'] = $_SESSION['lastFileInfo']['filename_ab'][ $byIndex ];
				$tile['filesize_f'] = $_SESSION['lastFileInfo']['fileSize_f'][ $byIndex ];
				$tile['dim_x'] = $_SESSION['lastFileInfo']['dim_x'][ $byIndex ];
				$tile['dim_y'] = $_SESSION['lastFileInfo']['dim_y'][ $byIndex ];

				$html = self::buildAssetTile($tile, $path, $vS, $thumbTileWidth);

			}
			else{
				for($n = 0; $n < $_SESSION['lastFileInfo']['totalFiles']; $n++){

					$tile['filename'] = $_SESSION['lastFileInfo']['filename'][ $byIndex ];
					$tile['filename_ext'] = $_SESSION['lastFileInfo']['filename_ext'][ $byIndex ];
					$tile['ext'] = $_SESSION['lastFileInfo']['ext'][ $byIndex ];					
					$tile['filename_ab'] = $_SESSION['lastFileInfo']['filename_ab'][ $byIndex ];
					$tile['filesize_f'] = $_SESSION['lastFileInfo']['fileSize_f'][ $byIndex ];
					$tile['dim_x'] = $_SESSION['lastFileInfo']['dim_x'][ $byIndex ];
					$tile['dim_y'] = $_SESSION['lastFileInfo']['dim_y'][ $byIndex ];

					$html .= self::buildAssetTile($tile, $path, $vS, $thumbTileWidth);

				}
			}

			return $html;
		}

		protected static function buildAssetTile($tile, $path, $vS, $thumbTileWidth)
		{
			//image tile
			if($tile['dim_x'] > 0){
				$html = "";
				$html .= "<div class='fileTile' data-file='{$tile['filename_ext']}' data-path='{$path}'>\n";
				$html .= "<div class='border'>\n";
				$html .= "	<div class='thumb'><img src='/{$path}thumbs/{$vS}_{$tile['filename_ext']}' alt='{$tile['filename_ext']}<br>Width: {$tile['dim_x']}px<br>Height: {$tile['dim_y']}px<br>Size: {$tile['filesize_f']}' width='{$thumbTileWidth}' height='{$thumbTileWidth}'></div><!--thumb-->\n";
				$html .= "	<div class='name' alt='{$tile['filename_ext']}'>{$tile['filename_ab']}.{$tile['ext']}</div><!--name-->\n";
				$html .= "</div><!--border-->\n";
				$html .= "</div><!--fileTile-->\n";
			}
			else{//file tile

				$ext = File::extension($tile['filename_ext'], false);

				//add further icons here
				if($ext == 'txt') $classIcon = 'fa fa-file-text-o';
				else if($ext == 'doc' or $ext == 'docx') $classIcon = 'fa fa-file-word-o';
				else if($ext == 'xls' or $ext == 'xlsx') $classIcon = 'fa fa-file-excel-o';
				else if($ext == 'pdf') $classIcon = 'fa fa-file-pdf-o';
				else if($ext == 'wav' or $ext == 'mp3') $classIcon = 'fa fa-file-sound-o';
				else if($ext == 'mov' or $ext == 'mpg') $classIcon = 'fa fa-file-movie-o';
				else if($ext == 'zip' or $ext == 'rar') $classIcon = 'fa fa-file-archive-o';
				else if($ext == 'ppt') $classIcon = 'fa fa-file-powerpoint-o';
				else $classIcon = 'fa fa-file-o';

				$html = "";
				$html .= "<div class='fileTile' data-file='{$tile['filename_ext']}' data-path='{$path}'>\n";
				$html .= "<div class='border'>\n";
				$html .= "	<div class='thumb' style='width:{$thumbTileWidth}px; height:{$thumbTileWidth}px;'><span class='{$classIcon}' alt='{$tile['filename_ext']}<br>Size: {$tile['filesize_f']}' style='font-size:{$thumbTileWidth}px'></span></div><!--thumb-->\n";
				$html .= "	<div class='name' alt='{$tile['filename_ext']}'>{$tile['filename_ab']}.{$tile['ext']}</div><!--name-->\n";
				$html .= "</div><!--border-->\n";
				$html .= "</div><!--fileTile-->\n";
			}
			return $html;
		}

	}

//!EOF : /main/application/models/cms/assetsmodel.php