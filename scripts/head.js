
function showContent(id) {
	jQuery('#content-'+id).slideDown();
	jQuery('#hide-'+id).attr('style','');
	jQuery('#show-'+id).attr('style','display:none;');
	return false;
}
function hideContent(id) {
	jQuery('#content-'+id).slideUp();
	jQuery('#hide-'+id).attr('style','display:none;');
	jQuery('#show-'+id).attr('style','');
	return false;
}
function showPreview(id) {
	jQuery('#post-'+id).slideDown();
	jQuery('#hide-'+id).attr('style','');
	jQuery('#show-'+id).attr('style','display:none;');
	return false;
}
function hidePreview(id) {
	jQuery('#post-'+id).slideUp();
	jQuery('#hide-'+id).attr('style','display:none;');
	jQuery('#show-'+id).attr('style','');
	return false;
}
function showMonth(year,month) {
	var category = jQuery("#cfar-category").html();
	if(category === '') {
		category = 0;
	}
	var addContent = jQuery("#content-"+year+"-"+month);
	var ajaxSpinner = '<div id="ajax-spinner"><img src="' + cfar.wpserver + 'wp-content/plugins/cf-archives/images/ajax-loader.gif" border="0" /> <span class="ajax-loading">Loading...</span></div>';
	if(!addContent.hasClass("filled")) {
		addContent.append(ajaxSpinner);
		jQuery.get(cfar.wpserver, { cf_action: 'cfar_ajax_month_archive', cfar_year: year, cfar_month: month, cfar_show_heads: 'no', cfar_add_div: 'no', cfar_add_ul: 'show', cfar_print_month_content: 'show', cfar_category: category, cfar_show_author: 'yes' },function(data){
			jQuery('#ajax-spinner').remove();

			data = jQuery(data);
			jQuery('a.month-post-show', data).each(function() {
				jQuery(this).click(function(){
					var ids = jQuery(this).attr('id').split('-');
					var showhide = ids[0];
					var post_id = ids[1];

					if (showhide == 'show') {
						showPreview(post_id);
					}
					else {
						hidePreview(post_id);
					}
					return false;
				});
			});

			addContent.append(data).addClass('filled');
		},'html');
	}
}
jQuery(document).ready(function() {
	jQuery('a.month-post-show').each(function() {
		jQuery(this).click(function(){
			var ids = jQuery(this).attr('id').split('-');
			var showhide = ids[0];
			var post_id = ids[1];

			if (showhide == 'show') {
				showPreview(post_id);
			}
			else {
				hidePreview(post_id);
			}
			return false;
		});
	});
});