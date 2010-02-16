;(function($) {
	$(function() {
		// Post Display
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
		};
		
		// Month Display
		$(".cfar-month-show").live('click', function() {
			var id = $(this).attr('id').replace('cfar-month-show-', '');
			cfar_month_showhide(id);
			return false;
		});

		$(".cfar-month-hide").live('click', function() {
			var id = $(this).attr('id').replace('cfar-month-hide-', '');
			cfar_month_showhide(id);
			return false;
		});
		
		$(".cfar-month-title").live('click', function() {
			var id = $(this).attr('id').replace('cfar-month-title-', '');
			cfar_month_showhide(id);
			return false;
		});

		cfar_month_showhide = function(id) {
			var content = $("#cfar-month-content-wrap-"+id);
			
			$("#cfar-month-show-"+id).toggleClass('cfar-item-hide');
			$("#cfar-month-hide-"+id).toggleClass('cfar-item-hide');
			content.slideToggle();

			if (!content.hasClass('cfar-month-filled')) {
				var monthyear = id.split("-");
				var month = monthyear[0];
				var year = monthyear[1];

				content.append("<div class='cfar-ajax-spinner'>"+$("#cfar-ajax-spinner").html()+"<span class='cfar-ajax-spinner-text'>Loading&hellip;</span></div>");
				$.post("index.php", {
					cf_action: "cfar_ajax_month_archive",
					cfar_year: year,
					cfar_month: month
				}, function(data) {
					$(".cfar-ajax-spinner").remove();
					content.append(data).addClass("cfar-month-filled");
				})
			}
			
			
			return false;
		};
		
	});
})(jQuery);