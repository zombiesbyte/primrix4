/**
 * fileViewer JavaScript
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	//initialisation
	$fVSelected = Array();	
	$fVProfiler = Array();
	$isCtrl = false;

	//options and settings
	$fVFolderChangeSubmit = true; //true = static fileViewer / false = dialogue fileViewer
	$fVReadInSelectedFiles = true; //will recognise the provided values of selectedFiles hidden field
	$allowCtrl = true; //we can lockout the use of the ctrl key
	$fVFileTypesImage = ['jpg','jpeg','png','gif'] //'tif','tiff','bmp' not supported at this time;
	$fVDynamicTargetID = false; //false = the target by default for the selected files will be selectedFiles / true will use the id of the activated dialogue
	
	//folderToolBox
	//viewToolBox
	//viewFile (button)
	//resizeToolBox
	//fileModToolBox
	//rotateImage (button)
	//future Implementation: imageCtrlToolBox
	//moveToolBox
	//deleteToolBox
	//future Implementation: restoreThumb

	//we use JSON to map out our toolbox settings. The next version of
	//this mapping will remove the need to specify image/other when no
	//selections (none) have been made as this logically can't happen
	//but for the sake of progress at this point I just kept the structure
	//of the map universal for clarity and corner-cutting
	$fVProfilerJSON = JSON.stringify({
		"folderToolBox": {
			"none": {"image": "block", "other": "block"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "block", "other": "block"}
		},
		"viewToolBox": {
			"none": {"image": "block", "other": "block"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "block", "other": "block"}
		},
		"viewFile": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "none", "other": "none"}
		},
		"resizeToolBox": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "none"},
			"multiple": {"image": "none", "other": "none"}
		},
		"fileModToolBox": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "none", "other": "none"}
		},
		"rotateImage": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "none"},
			"multiple": {"image": "none", "other": "none"}
		},
		"moveToolBox": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "block", "other": "block"}
		},
		"deleteToolBox": {
			"none": {"image": "none", "other": "none"},
			"singular": {"image": "block", "other": "block"},
			"multiple": {"image": "block", "other": "block"}
		}
	});
	
	$fVProfiler = JSON.parse($fVProfilerJSON);

	$(function(){

		//setters
		function setFVFolderChangeSubmit(val){ $fVFolderChangeSubmit = val; }

		updateToolBoxes();

		$('.controls .ctrlGrp p').click(function(){
			$('.controls .ctrlGrp .toolbox').css('display', 'none');
			$('.controls .ctrlGrp .lid').show('fast');
			$(this).parent().find('.toolbox').css('display', 'block');
			$(this).parent().find('.lid').hide('fast');
		});

		getFileList();

		$('#folder').change(function(event){
			$folder = $('#folder').val();
			
			if($fVFolderChangeSubmit) $('#form1').submit(); //the difference between dialogue fileViewer and static fileViewer
			else{
				//AJAX method of changing directory does not at this time support the updating of the moveTo box
				//which is why we simply only should allow the AJAX folder change when in a dialogue fileViewer as
				//all but the basic functionality is removed.
				var postBack = $.post('/admin/asset-manager/change-dir/', {switchTo: $folder});
				postBack.done(function(data){
					getFileList();
				});
			}
		});

		$('#deleteFolder').click(function(event){
			$folder = $('#folder').val();
			$noticeTxt = 'Are you sure you wish to delete the following folder?<br>';
			$noticeTxt += $folder + '?<br>';
			$noticeTxt += 'Warning! This will delete all files and folders within!<br>';
			$noticeTxt += '<b>This action cannot be undone</b>';
			confirmNotice(event, '#deleteFolder', $noticeTxt);
		});

		$('#createFolder').click(function(event){
			$folder = $('#folder').val();
			$noticeTxt = 'Please type a name for your new folder below.<br>';
			$noticeTxt += 'Once you click OK, your new folder will be created within:<br><br>';
			$noticeTxt += $folder + '/<br>';
			userInputNotice(event, '#createFolder', 'newFolderName', $noticeTxt);
		});

		$('#search').keyup(updateSearch);

		$('#viewFile').click(function(event){
			event.preventDefault();
			if($fVSelected.length == 1){
				$filePath = $('[data-file="' + $fVSelected[0] + '"]').data('path');
				window.open('/' + $filePath + '/' + $fVSelected[0]);
				return false;
			}
		});

		$('#delete').click(function(event){
			$selectedFiles = $('#selectedFiles').val();
			$selectedFiles = $selectedFiles.replace(/,/g, ", ");
			$noticeTxt = 'Are you sure you wish to delete the following files?<br>';
			$noticeTxt += $selectedFiles + '?<br><br>';
			$noticeTxt += '<b>Warning: This action cannot be undone</b>';
			confirmNotice(event, '#delete', $noticeTxt);
		});

		$('#resizeWidth').focus(function(){
			$('#resizeHeight').val('');
		});

		$('#resizeHeight').focus(function(){
			$('#resizeWidth').val('');
		});

		$('.fileViewerEvent').click(function(){
			initWipeOut();
			$('#fileViewDialogue').css('display', 'block');
			if($fVDynamicTargetID == true) $fVDynamicTargetID = $(this).attr('id');
		});

		$('#closeFileViewer').click(function(event){
			event.preventDefault();
			$('.primrixWipeOut').remove();
			$('#fileViewDialogue').css('display', 'none');
		});
	});

	$(document).keydown("ctrl", function(e) {
		if(e.ctrlKey && $allowCtrl){
			$isCtrl = true;
			$('#fileViewCtrlActive').show('fast');
		}
	});

	$(document).keyup("ctrl", function(e) {
		if(e.keyCode == 17 && $allowCtrl){
			$isCtrl = false;
			$('#fileViewCtrlActive').hide('fast');
		}
	});


	function fileViewTileSelector(reset = false)
	{
		if(reset == true){
			for($n = 0; $n < $fVSelected.length; $n++){
				$('[data-file="' + $fVSelected[$n] + '"]').find(">:first-child").attr('class', 'border');
			}
			$fVSelected = Array();
		}
		else{
			$fVCount = 0;
			for($n = 0; $n < $fVSelected.length; $n++){
				if($fVSelected[$n] == $(this).data('file')) $fVCount++;
			}

			if($fVCount == 0){
				$(this).find(">:first-child").attr('class', 'borderSelected');
				if($isCtrl) $fVSelected[$fVSelected.length] = $(this).data('file');
				else{
					for($n = 0; $n < $fVSelected.length; $n++){
						$('[data-file="' + $fVSelected[$n] + '"]').find(">:first-child").attr('class', 'border');
					}
					$fVSelected = Array();
					$fVSelected[0] = $(this).data('file');
				}
			}
			else{
				
				$fVSelectedTemp = Array();

				for($n = 0; $n < $fVSelected.length; $n++){
					if($fVSelected[$n] != $(this).data('file')){
						$fVSelectedTemp[$fVSelectedTemp.length] = $fVSelected[$n];
					}
					else {
						$('[data-file="' + $fVSelected[$n] + '"]').find(">:first-child").attr('class', 'border');
					}
				}
				$fVSelected = Array();
				if($isCtrl) $fVSelected = $fVSelectedTemp.slice(0);
				else {
					
					$fVSelected = Array();

					if($fVSelectedTemp.length > 0){
						$fVSelected[0] = $(this).data('file');
						$(this).find(">:first-child").attr('class', 'borderSelected');

						for($n = 0; $n < $fVSelectedTemp.length; $n++){
							$('[data-file="' + $fVSelectedTemp[$n] + '"]').find(">:first-child").attr('class', 'border');
						}
					}
				}
			}
		}
		$('#fileViewStatusSelected').html('Selected: ' + $fVSelected.length + ' file(s)');
		if($fVDynamicTargetID == false) $('#selectedFiles').val($fVSelected.join(","));
		else {
			//if we are targeting a single id then we only pass over a single selection.
			//Due to the nature of the dialogue fileViewer single image/file selections are
			//all that are required.
			$folder = $('#folder').val();			
			$('#' + $fVDynamicTargetID).val($folder + '/' + $fVSelected[0]);
		}

		updateToolBoxes();
	}


	function updateToolBoxes()
	{
		$fVSelectionTheme = '';
		if($fVSelected.length == 0) $fVSelectionTheme = 'none';
		else if($fVSelected.length == 1) $fVSelectionTheme = 'singular';
		else if($fVSelected.length > 1) $fVSelectionTheme = 'multiple';

		//we update the fileAction textbox with the selected filename
		if($fVSelectionTheme == 'singular') $('#fileAction').val($fVSelected[0]);
		else $('#fileAction').val('');

		$selectedFileTypes = 'image';

		for($n = 0; $n < $fVSelected.length; $n++){
			$ext = $fVSelected[$n].substr( ($fVSelected[$n].lastIndexOf('.') +1) );
			if(jQuery.inArray($ext, $fVFileTypesImage) == -1) $selectedFileTypes = 'other'; 
		}

		for(var a in $fVProfiler){ //a: [element id]
			for(var b in $fVProfiler[a]){ //b: none/singular/multiple [selections]
				for(var c in $fVProfiler[a][b]){ //c: image/other [filetype]
					if(c == $selectedFileTypes){
						if(b == $fVSelectionTheme){
							$('#' + a).css('display', $fVProfiler[a][b][c]);
						}
					}
				}
			}
		}
	}



	function getFileList()
	{
		var postBack = $.post('/admin/asset-manager/get-file-list/', {id: 1});

		$('#fileViewStatusTotal').text('Total Files Found: [working...]');

		$('.fileTile').remove(); //removes all fileTile's should there be any.

		$newTile = "";
		$newTile += "<div class='fileTileWorking' id='workingFileTile'>\n";
		$newTile += "<div class='border'>\n";
		$newTile += "	<div class='thumb' style='width: 60px; height: 60px; text-align: center; padding-top: 30px;'><span class='fa fa-spinner fa-spin' style='font-size:30px;'></span></div><!--thumb-->\n";
		$newTile += "	<div class='name' alt='Working...'>Working...</div><!--name-->\n";
		$newTile += "</div><!--border-->\n";
		$newTile += "</div><!--fileTile-->\n";
		$('#fileViewFolder').append($newTile);

		postBack.done(function(data) {
			if(data != ''){
				$count = data;
				$('#workingFileTile').remove();
				$('#fileViewStatusTotal').text('Total Files Found: ' + $count);
				if($count > 0) getFileTiles($count);
				else {
					$newTile = "";
					$newTile += "<div class='fileTileWorking' id='workingFileTile'>\n";
					$newTile += "<div class='border'>\n";
					$newTile += "<p><span class='fa fa-ban'></span> No files found</p>\n";
					$newTile += "</div><!--border-->\n";
					$newTile += "</div><!--fileTile-->\n";
					$('#fileViewFolder').append($newTile);					
				}
				
				getFolderSize();	
			}
		});				
	}

	function getFolderSize()
	{
		var postBack = $.post('/admin/asset-manager/get-dir-size/', {id: 1});
		$('#totalFolderSize').html("Directory size: <span class='fa fa-spinner fa-spin' style='font-size:14px;'></span>");
		postBack.done(function(data){
			$('#totalFolderSize').html('Directory size: ' + data);
		});
	}

	function getFileTiles(count)
	{
		for(var n = 0; n < count; n++){
			var postBack = $.post('/admin/asset-manager/get-files/' + n, {
				id: 1
			});
			
			$newTile = "";
			$newTile += "<div class='fileTileWorking' id='workingFileTile'>\n";
			$newTile += "<div class='border'>\n";
			$newTile += "	<div class='thumb' style='width: 60px; height: 60px; text-align: center; padding-top: 30px;'><span class='fa fa-spinner fa-spin' style='font-size:30px;'></span></div><!--thumb-->\n";
			$newTile += "	<div class='name' alt='Working...'>Working...</div><!--name-->\n";
			$newTile += "</div><!--border-->\n";
			$newTile += "</div><!--fileTile-->\n";
			$('#fileViewFolder').append($newTile);

			postBack.done(function(data) {

				if(data != ''){
					$('#workingFileTile').replaceWith(data);
					$('#workingFileTile').remove(); //need this to correct bug occurrence
					refreshPrimrixToolTips();
					$('.fileTile').unbind('click');
					$('.fileTile').click(fileViewTileSelector);
				}
			});
		}
	}

	function updateSearch()
	{
		fileViewTileSelector(true); //we can't work with anything selected

		if($('#search').val() != ''){
			var searchStr = $('#search').val();
			searchStr = searchStr.replace(/[^A-Za-z0-9\-_]/, '');
			$('[data-file]').css('display','none');
			$('[data-file*="' + searchStr + '"]').css('display','block');
			$count = $('[data-file*="' + searchStr + '"]').length;
			$('#fileViewStatusTotal').text('Total Files Found: ' + $count);
		}
		else {
			$('[data-file]').css('display','block');
			$count = $('[data-file]').length;
			$('#fileViewStatusTotal').text('Total Files Found: ' + $count);
		}
	}
