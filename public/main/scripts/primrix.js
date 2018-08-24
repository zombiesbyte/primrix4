
/**
 * Primrix JavaScript
 *
 * @author       James Dalgarno <james@imagewebdesign.co.uk>
 * @copyright    James Dalgarno 2014
 * @license      license.txt The MIT License (MIT)
 * @package      Primrix 4.0
 * @version      1.0
 */

	$(function() {
		init();
	});

	function init()
	{
		initPrimrixErrorBox(); //initialise our error box
		initPrimrixToolTips(); //initialise our tooltips
		initToggleActive(); //attach our event handlers to our toggleActive class
		initOrder(); //attach our even handlers to our order class
		initSelectAll(); //our select all tick boxes function
		initFaIconPreview(); //load fa icon preview
		initNav(); //initialise our navigation functionality
		initCaptcha();
		initSubmitLinks(); //initialise any submitLink classes
		initExpander(); //initialise expander classes
		initSwitchField(); //initialise switch fields
	}

	function initCaptcha()
	{
		if($('.captchaImage')){

			$('.captchaReload').click(function(){
				$captchaID = $(this).data('id');
				$('#captchaImg' + $captchaID).attr('src', '/vendor/primrix/securimage/securimage_show.php?' + Math.random());
				$('#captchaAudio' + $captchaID).attr('src', '/vendor/primrix/securimage/securimage_play.php?' + Math.random());
			});

			$('.captchaSound').click(function(){
				$captchaID = $(this).data('id');
				$audioElement = document.getElementById('#captchaAudio' + $captchaID);
				$('#captchaAudio' + $captchaID).trigger("play");
			});
		}
	}

	function initPrimrixToolTips()
	{
		//lets build our div for our tooltips
		$('body').prepend($('<div>',
			{
				'id'	: 'primrixToolTip',
				'class'	: 'primrixToolTip'
			})
		);

		$('*[alt]').mousemove(function(event) {
			$(this).css('cursor', 'help');
			$('#primrixToolTip').css('display','block');
			$('#primrixToolTip').html($(this).attr('alt'));

			if(event.pageX < 1300){
				$('#primrixToolTip').css({'left': event.pageX + 28, 'top': event.pageY + 12});
			}
			else {
				var $offset = parseInt($('#primrixToolTip').css('width')) + 38;
				$('#primrixToolTip').css({'left': event.pageX - $offset, 'top': event.pageY + 12});
			}
		}).mouseout(function() {
			$(this).css('cursor', '');
			$('#primrixToolTip').css('display','none');
		});
	}

	function refreshPrimrixToolTips()
	{
		$('*[alt]').unbind('mouseover mouseout');
		
		$('*[alt]').mousemove(function(event) {
			$(this).css('cursor', 'help');
			$('#primrixToolTip').css('display','block');
			$('#primrixToolTip').html($(this).attr('alt'));

			if(event.pageX < 1300){
				$('#primrixToolTip').css({'left': event.pageX + 28, 'top': event.pageY + 12});
			}
			else {
				var $offset = parseInt($('#primrixToolTip').css('width')) + 38;
				$('#primrixToolTip').css({'left': event.pageX - $offset, 'top': event.pageY + 12});
			}
		}).mouseout(function() {
			$(this).css('cursor', '');
			$('#primrixToolTip').css('display','none');
		});
	}

	function initPrimrixErrorBox()
	{
		if($.trim($('.errorBox').text()).length > 0){

			initWipeOut();
			var $n = $('.errorBox').size();
			var $errorContents = "";

			for(var i = 0; i < $n; i++){
				$errorContents += $('.errorBox:eq(' + i +')').html();
			}

			$('.errorBox').remove();

			$('.primrixWipeOut .central').append($('<div>', {'class': 'primrixErrorBox'}));
			$('.primrixErrorBox').append('<div class="icon"><span class="fa fa-warning"></span></div>');
			$('.primrixErrorBox').append('<div class="errors">' + $errorContents + '</div><br style="clear:both;">');
			$('.primrixErrorBox').append('<div class="close"><input type="button" id="closeErrorBox" value="OK" tabindex="1"></div>');

			$('.primrixErrorBox').draggable({ containment: 'body', cancel: '.errors', scroll: false });
			
			$('#closeErrorBox').click(function(){
				$('.primrixWipeOut').remove();	
			});
		}
	}

	//used for ajax generic loading box
	function initPrimrixLoader()
	{
		$('.primrixWipeOutInv .central').append($('<div>', {'class': 'primrixLoader'}));
		$('.primrixLoader').append($('<span>', {'class': 'fa fa-spinner fa-spin'})); //fa-spinner fa-repeat fa-refresh fa-unsorted fa-globe fa-asterisk
		$('.primrixLoader').append("<span class='txt'>Saving... please be patient</span><br style='clear:both;'>");
		$('.primrixLoader').css('display','block');
	}

	function initWipeOut()
	{
		$('body').prepend($('<div>', {'class': 'primrixWipeOut'}));
		$('.primrixWipeOut').append($('<div>', {'class': 'central'}));
	}

	function initWipeOutInv()
	{
		$('body').prepend($('<div>', {'class': 'primrixWipeOutInv'}));
		$('.primrixWipeOutInv').append($('<div>', {'class': 'central'}));
	}	

	function initSelectAll()
	{
		$('.selectall-selection').click(function(event) {
			if(this.checked) {
				$('.selection').each(function() {
					this.checked = true;});
			}
			else {
				$('.selection').each(function() {
					this.checked = false;});
			}
		});
	}

	function initToggleActive()
	{
		$('.toggleActive').click(toggleActive);
		$('.toggleActiveGrp').click(toggleActiveGrp);
	}
	
	function initOrder()
	{
		$('.order').click(order);
	}

	function initFaIconPreview()
	{
		if($('.faIconPreview')){
			$('.faIconPreview').keyup(faIconPreview);
			faIconPreview();
		}
	}

	function initNav()
	{
		if($('.navOpen')){
			$('.navOpen').click(navOpenSub);
		}
	}

	function initSubmitLinks()
	{
		if($('.submitLink')){
			$('.submitLink').click(function(){
				$submitAs = $(this).data('submit-as');
				$(this).after('<input type="submit" value="' + $submitAs + '" id="tempID' + $submitAs + '" name="' + $submitAs + '" style="display:none;">');
				$('#tempID' + $submitAs).trigger('click');
			});
		}
	}

	function initExpander()
	{
		if($('.expander')){
			$('.expander').click(function(){
				$expanderID = $(this).data('expander-id');

				if($expanderID != 'all'){
					if($("[data-expander-grp='" + $expanderID + "']").css('display') == 'none'){
						$(this).removeClass('fa-toggle-right');
						$(this).addClass('fa-toggle-down');
					}
					else{
						$(this).removeClass('fa-toggle-down');
						$(this).addClass('fa-toggle-right');
					}
					$("[data-expander-grp='" + $expanderID + "']").toggle('fast');
				}
				else $("[data-expander-grp]").show('fast');
				
			});
		}
	}

	function initSwitchField()
	{
		if($('.switchField')){
			$('.switchField').click(function(event){
				event.preventDefault();
				var field1 = $(this).data('f1').split('|');
				var field2 = $(this).data('f2').split('|');
				$('#' + field1[0]).toggle('fast');
				$('#' + field2[0]).toggle('fast');
				$(this).find('span').toggleClass(field1[1]).toggleClass(field2[1]);
			});
		}
	}

	function navOpenSub()
	{
		var $menuID = $(this).data('nav-id');
		if($('#nav-' + $menuID)){
			
			$('.navigation .subGrp').hide('fast');
			
			if($('#nav-' + $menuID).css('display') == 'block') $('#nav-' + $menuID).hide('fast');
			else $('#nav-' + $menuID).show('fast');
		}
	}

	function faIconPreview()
	{
		var $iconClass;
		$iconClass = $('.faIconPreview').val();
		$('.iconPreview .preview').html($('<span>', {'class': $iconClass}));
	}

	function toggleActive()
	{
		var postBack = $.post('/admin/ajax/toggleActive', {
			id: $(this).data('id'),
			table: $(this).data('table')
		});

		initWipeOutInv();
		initPrimrixLoader();

		postBack.done(function(data) {
			if(data == ''){
				location.reload();
			}
			else {
				alert('There was a problem: ' + data);
				$('.primrixWipeOutInv').remove();
			}
		});
	}

	function toggleActiveGrp()
	{
		initWipeOutInv();
		initPrimrixLoader();

		$('.selection').each(function() {
			if(this.checked == true){

				var $id = $(this).closest('tr').find('.toggleActive').data('id');
				var $table = $(this).closest('tr').find('.toggleActive').data('table');

				var $postBack = $.post('/admin/ajax/toggleActive', {
					id: $id,
					table: $table 
				});

				$postBack.done(function(data) {
					if(data != ''){
						alert('There was a problem: ' + data);
						$('.primrixWipeOutInv').remove();
					}
				});

			};
		});

		location.reload();
	}

	function order()
	{
		
		if($(this).data('order') == 'up' || $(this).data('order') == 'down'){
			var postBack = $.post('/admin/ajax/order', {
				id: $(this).data('id'),
				table: $(this).data('table'),
				order: $(this).data('order')
			});
		}
		else if($(this).data('order') == 'top' || $(this).data('order') == 'bottom'){

			var $idCSV = "";
			var $tableCSV = "";

			$('.selection').each(function() {
				if(this.checked == true){
					var $id = $(this).closest('tr').find('.order').data('id');
					var $table = $(this).closest('tr').find('.order').data('table');
					//put into a csv format
					
					$idCSV += $id + ',';
					$tableCSV += $table + ','; 

				}
			});

			var postBack = $.post('/admin/ajax/order', {
						csv: true,
						id: $idCSV,
						table: $tableCSV,
						order: $(this).data('order')
			});
		}

		initWipeOutInv();
		initPrimrixLoader();
		
		postBack.done(function(data) {
			if(data == ''){
				location.reload();
			}
			else {
				alert('There was a problem: ' + data);
				$('.primrixWipeOutInv').remove();
			}
		});		
	}

	/**
	 * Convert jQuery RGB output to Hex Color
	 * http://wowmotty.blogspot.co.uk/2009/06/convert-jquery-rgb-output-to-hex-color.html
	 * @author Rob Garrison (Thanks Rob)
	 * @param {int} rgb red green blue
	 */
	function rgb2hex(rgb)
	{
		rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
		return "#" +
		("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
		("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
		("0" + parseInt(rgb[3],10).toString(16)).slice(-2);
	}

	/**
	 * Colour Picker
	 */
	function setStdColourPicker(field)
	{
		$fColour1 = $('<p class="pFgColour1"></p>').hide().appendTo("body").css('color');
		$fColour2 = $('<p class="pFgColour2"></p>').hide().appendTo("body").css('color');
		$fColour1 = rgb2hex($fColour1);
		$fColour2 = rgb2hex($fColour2);

		$(field).spectrum({
    		color: $fColour1,
    		preferredFormat: "hex",
    		chooseText: "OK",
    		cancelText: "Cancel",
    		showPaletteOnly: true,
    		togglePaletteOnly: true,
    		hideAfterPaletteSelect:true,
    		togglePaletteMoreText: 'more',
    		togglePaletteLessText: 'less',
    		palette: [
    			[$fColour1,$fColour2,'#000000','#444444','#666666','#999999','#cccccc','#fffffe'],
    			['#ea9999','#f9cb9c','#ffe599','#b6d7a8','#a2c4c9','#9fc5e8','#b4a7d6','#d5a6bd'],
    			['#e06666','#f6b26b','#ffd966','#93c47d','#76a5af','#6fa8dc','#8e7cc3','#c27ba0'],
    			['#c00000','#e69138','#f1c232','#6aa84f','#45818e','#3d85c6','#674ea7','#a64d79'],
    			['#900000','#b45f06','#bf9000','#38761d','#134f5c','#0b5394','#351c75','#741b47'],
    			['#600000','#783f04','#7f6000','#274e13','#0c343d','#073763','#20124d','#4c1130']
    		]
		});

		$(field).css('width', '140px');
		if($(field).val() == '') $(field).val($fColour1);
		else $(field).spectrum('set', $(field).val());
		$(field).show();
	}

	/**
	 * confirmNotice handling is called from your chosen event with the
	 * button id/class passed in jQuery form. This is then prevented from
	 * the default action and the notice with yes and no buttons are shown.
	 * If the user clicks no then the primrixWipeOut is removed. If the user
	 * clicks yes then the primrixWipeOut is removed, the confirmNotice event
	 * is removed and the trigger for the onclick is provoked.
	 * @param {event} event the event object is passed
	 * @param  {string} jQClassOrID a jQuery object reference
	 * @param  {string} noticeTxt string text to be shown in dialogue this can include html markup
	 * @return {none} no actual return
	 */
	function confirmNotice(event, jQClassOrID, noticeTxt = null)
	{
		event.preventDefault();
		if(noticeTxt == null) noticeTxt = 'Are you sure?';
		confirmNoticeSetStage(noticeTxt);
		
		$('#closeNoticeBox_yes').click(function(){
			$('.primrixWipeOut').remove();
			$(jQClassOrID).unbind('click');
			$(jQClassOrID).trigger('click');				
		});
		
		$('#closeNoticeBox_no').click(function(){
			$('.primrixWipeOut').remove();
		});
	}

	function confirmNoticeSetStage(noticeTxt)
	{
		initWipeOut();
		
		$('.primrixWipeOut .central').append($('<div>', {'class': 'primrixErrorBox'}));
		$('.primrixErrorBox').append('<div class="icon"><span class="fa fa-warning"></span></div>');
		$('.primrixErrorBox').append('<div class="errors">' + noticeTxt + '</div><br style="clear:both;">');
		$('.primrixErrorBox').append('<div class="close"><input type="button" id="closeNoticeBox_no" value="No" tabindex="1" style="margin-right: 10px;"><input type="button" id="closeNoticeBox_yes" value="Yes" tabindex="1"></div>');

		$('.primrixErrorBox').draggable({ containment: 'body', cancel: '.errors', scroll: false });
	}

	function userInputNotice(event, jQClassOrID, fieldName, noticeTxt)
	{
		event.preventDefault();
		userInputNoticeSetStage(noticeTxt, fieldName);
		
		$('#closeNoticeBox_yes').click(function(){
			$('#' + fieldName).val( $('#copyTo_' + fieldName).val() );
			$('.primrixWipeOut').remove();			
			$(jQClassOrID).unbind('click');
			$(jQClassOrID).trigger('click');				
		});
		
		$('#closeNoticeBox_no').click(function(){
			$('.primrixWipeOut').remove();
		});
	}

	function userInputNoticeSetStage(noticeTxt, fieldName)
	{
		initWipeOut();
		
		$('.primrixWipeOut .central').append($('<div>', {'class': 'primrixErrorBox'}));
		$('.primrixErrorBox').append('<div class="icon"><span class="fa fa-warning"></span></div>');
		$('.primrixErrorBox').append('<div class="errors">' + noticeTxt + '</div><br style="clear:both;"><br style="clear:both;">');
		$('.primrixErrorBox').append('<input type="text" id="copyTo_' + fieldName + '" style="margin-left: 70px;"><br style="clear:both;">');
		$('.primrixErrorBox').append('<div class="close"><input type="button" id="closeNoticeBox_no" value="Cancel" tabindex="1" style="margin-right: 10px;"><input type="button" id="closeNoticeBox_yes" value="OK" tabindex="1"></div>');

		//$('.primrixErrorBox').draggable({ containment: 'body', cancel: 'input .errors', scroll: false });
	}

	/**
	 * Thanks to Tim Down for providing this little solution:
	 * http://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb
	 * Modifications to the return group were made
	 * @param  string hex colour hex
	 * @return obj returns access to obj.r obj.g obj.b
	 */
	function hexToRgb(hex) {
    var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
        rgb: parseInt(result[1], 16) + ', ' + parseInt(result[2], 16) + ', ' + parseInt(result[3], 16)
    } : null;
}

