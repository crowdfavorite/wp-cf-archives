jQuery(function() {

	jQuery('#cfar_settings_form2').submit(function(e){
		jQuery('input[type=submit]', this).attr('disabled', 'disabled');
		jQuery(this).attr("disabled", "disabled");
		cfar_batch_rebuild_archives();
		e.preventDefault();
	});

	function cfar_batch_rebuild_archives() {
		var batch_offset = 0;
		var batch_increment = 20;

		cfar_update_status('<div id="ajax-spinner"><img src="' + cfar.wpserver + 'wp-content/plugins/cf-archives/images/ajax-loader-large.gif" border="0" /></div><h1 style="text-align:center;">DO <strong>NOT</strong> NAVIGATE AWAY FROM THIS PAGE OR CLOSE THIS WINDOW</h1><br /><br />'+'Processing archives');

		// process posts

		cfar_batch_request(batch_offset,batch_increment);
	}

	// make a request
	function cfar_batch_request(offset,increment) {
		jQuery.post('index.php',
			{
				cf_action: 'cfar_rebuild_archive_batch',
				cfar_batch_offset: offset,
				cfar_batch_increment: increment
			},
			function(response) {
				if (!response.result && !response.finished) {
					cfar_update_status('Archive processing failed. Server said: ' + response.message);
					jQuery('input[type=submit]', '#cfar_settings_form2').removeAttr('disabled');
				}
				else if (!response.result && response.finished) {
					cfar_update_status('Archive processing complete. You can now close or navigate away from this page.');
					jQuery('input[type=submit]', '#cfar_settings_form2').removeAttr('disabled');
				}
				else if (response.result) {
					cfar_update_status('<div id="ajax-spinner"><img src="' + cfar.wpserver + 'wp-content/plugins/cf-archives/images/ajax-loader-large.gif" border="0" /></div><h1 style="text-align:center;">DO <strong>NOT</strong> NAVIGATE AWAY FROM THIS PAGE OR CLOSE THIS WINDOW</h1><br /><br />'+response.message);
					next_offset = (offset + increment);
					cfar_batch_request(next_offset, increment);
				}
			}, 'json'
		);
	}

	// handle the building of indexes
	function cfar_index_build_callback(response) {
		if (response.result) {
			cfar_update_status('Archive Rebuild Complete');
		}
		else {
			cfar_update_status('Failed to rebuild archives');
		}
	}

	// update status message
	function cfar_update_status(message) {
		if (!jQuery('#index-status').hasClass('updated')) {
			jQuery('#index-status').addClass('updated');
		}
		jQuery('#index-status p').html(message);
	}

	jQuery('.cfar-year-check').each(function() {
		if (jQuery(this).is(':checked')) {
			jQuery(this).parent().parent().siblings().each(function() {
				jQuery(this).children().attr('style', 'opacity: .5;').children('input').attr('disabled','disabled');
			});
		}
	});
	cfar_year_remove_check(jQuery('.cfar-year-check'));
});
function cfar_add_category() {
	var id = new Date().valueOf();
	var section = id.toString();

	var html = jQuery('#newitem_SECTION').html().replace(/###SECTION###/g, section);

	jQuery('#cfar-categories').append(html);
	jQuery('#cfar-item-'+section).attr('style','');

	jQuery('#archive_changes').show();

	cfar_year_remove_check(jQuery('#category_'+section+' .cfar-year-check'));
}
function cfar_remove_category(id) {
	if (confirm('Are you sure you want to delete this?')) {
		jQuery('#category_'+id).remove();
		jQuery('#archive_changes').show();
	}
	return false;
}
function cfar_year_remove_check(me) {
	jQuery(me).each(function() {
		jQuery(this).click(function() {
			_this = jQuery(this);
			if (_this.is(':checked')) {
				_this.parent().parent().siblings().each(function() {
					jQuery(this).children().attr('style', 'opacity: .5;').children('input').attr('disabled','disabled');
				});
				jQuery('#archive_changes').show();
			}
			else {
				_this.parent().parent().siblings().each(function() {
					jQuery(this).children().attr('style', '').children('input').attr('disabled','');
				});
				jQuery('#archive_changes').show();
			}
		});
	});
}
