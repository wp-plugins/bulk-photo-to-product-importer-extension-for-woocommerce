;(function($) {
	var custom_uploader;
	$("#import_photos").hide();
	$('#upload-pickfiles').click(function(e) {

		e.preventDefault();

		//If the uploader object has already been created, reopen the dialog
		if (custom_uploader) {
			custom_uploader.open();
			return;
		}

		//Extend the wp.media object
		
		custom_uploader = wp.media.frames.file_frame = wp.media({
			title: 'Choose Image',
			button: {
				text: 'Choose Image'
			},
			multiple: true
		});
		custom_uploader.on('select', function() {
			var selection = custom_uploader.state().get('selection');
			selection.map(function(attachment) {
				attachment = attachment.toJSON();
				$( '.import' ).fadeOut();
				if (!$(".saving").length) {
					$("#import_photos").after('<div class="saving"><img src="' + PTPImporter_Vars.pluginurl + '/assets/images/wpspin.gif" width="18" height="18" /></div>');
				}
				$.post( PTPImporter_Vars.muajax,"id="+attachment.id, function( data ) {
					var res = $.parseJSON(data);
					$('.upload-filelist').append('<div class="uploaded-item" id="' + attachment.id + '"></div>');
					$('#' + attachment.id).append(res.content);
					$('#' + attachment.id + ' .item-link img').load(function(){
						$(this).parent().toggle();
						$(this).parent().siblings('.progress').toggle();
						$(this).parent().siblings('fieldset').find('.item-title').animate({
							marginLeft: '-=178px',
							opacity: 1
						}, 400, function(){
							$(this).addClass('item-title-active');
							$(this).parent().siblings('.item-delete').toggle();
						});
					});
					$('.users-dropdown').chosen();
					$("#import_photos").fadeIn();
					$(".saving").remove();
					$( '.import' ).fadeIn();
				});
			});
			
		});

		//Open the uploader dialog
		custom_uploader.open();

	});
})(jQuery);
