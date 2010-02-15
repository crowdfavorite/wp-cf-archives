;(function($) {
	$(function() {
		$("#cfar-rebuild-button").live('click', function() {
			$(this).attr('disabled', 'disabled');
			$.post("index.php", {
				cf_action: "cfar_rebuild_archive"
			}, function(r) {
				if (r == 'complete') {
					$("#cfar-rebuild-status").html('Success!');
				}
				else {
					$("#cfar-rebuild-status").html('Failure&hellip;Please try again.');
				}
				$(this).removeAttr("disabled");
			});
		});
	});
})(jQuery);