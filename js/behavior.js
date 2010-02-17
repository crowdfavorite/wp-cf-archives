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
			cfar_display_showhide(id, 'cfar-month');
			return false;
		});

		$(".cfar-month-hide").live('click', function() {
			var id = $(this).attr('id').replace('cfar-month-hide-', '');
			cfar_display_showhide(id, 'cfar-month');
			return false;
		});
		
		$(".cfar-month-title").live('click', function() {
			var id = $(this).attr('id').replace('cfar-month-title-', '');
			cfar_display_showhide(id, 'cfar-month');
			return false;
		});

		// Week Display
		$(".cfar-week-show").live('click', function() {
			var id = $(this).attr('id').replace('cfar-week-show-', '');
			cfar_display_showhide(id, 'cfar-week');
			return false;
		});

		$(".cfar-week-hide").live('click', function() {
			var id = $(this).attr('id').replace('cfar-week-hide-', '');
			cfar_display_showhide(id, 'cfar-week');
			return false;
		});
		
		$(".cfar-week-title").live('click', function() {
			var id = $(this).attr('id').replace('cfar-week-title-', '');
			cfar_display_showhide(id, 'cfar-week');
			return false;
		});
		
		
		// Show/Hide function for content areas
		cfar_display_showhide = function(id, base) {
			var content = $("#"+base+"-content-wrap-"+id);
			
			$("#"+base+"-show-"+id).toggleClass('cfar-item-hide');
			$("#"+base+"-hide-"+id).toggleClass('cfar-item-hide');
			content.slideToggle();

			if (!content.hasClass(base+'-filled')) {
				var items = id.split("-");
				var other = items[0];
				var year = items[1];
				var host = window.location.host;
				var options = new Object();
				
				options.cf_action = base+"-ajax-archive";
				options.cfar_year = year;
				options.cfar_other = other;
				
				content.append("<div class='cfar-ajax-spinner'>"+$("#cfar-ajax-spinner").html()+"<span class='cfar-ajax-spinner-text'>Loading&hellip;</span></div>");
				$.post("http://"+host+"/index.php", options, function(data) {
					$(".cfar-ajax-spinner").remove();
					content.append(data).addClass(base+"-filled");
				});
			}
			
			
			return false;
		};
		
	});
})(jQuery);