;(function($) {
	$(function() {
		$(".cfar-post-show").live('click', function() {
			var id = $(this).attr('id').replace('cfar-post-show-', '');
			cfar_post_showhide(id);
			return false;
		});
		
		$(".cfar-post-hide").live('click', function() {
			var id = $(this).attr('id').replace('cfar-post-hide-', '');
			cfar_post_showhide(id);
			return false;
		});
		
		cfar_post_showhide = function(id) {
			$("#cfar-post-show-"+id).toggleClass('cfar-item-hide');
			$("#cfar-post-hide-"+id).toggleClass('cfar-item-hide');
			$("#cfar-post-content-"+id).slideToggle();
			return false;
		}
	});
})(jQuery);