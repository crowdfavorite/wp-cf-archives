<?php

class CF_Archives {
	
	public function __construct() {
		add_action('init', array($this, 'request_handler'), 11);
		
		
	}
	
	
	public function request_handler() {
		
	}
	
	
	## Admin
	
	public function admin() {
		
	}
	
	public function rebuild() {
		$settings = $this->get_settings();
		
		// Get the current Month and Year
		$month = $this->get_date(false, 'm');
		$year = $this->get_date(false, 'Y');
		
		// Get the first month and year so we know when to stop searching for posts
		$first_year = $this->get_first_year_post();
		$first_month = $this->get_first_month_post();

		$post_counts = array();
		$count = 1;
		while (1) {
			$month = zeroise($month, 2); 
			$year = zeroise($year, 4);
			$count = $this->month_post_count($month, $year);

			$post_counts[$year][$month] = $count;
			
			// If we are in the first month and year, break out because we are done
			if ($year == $first_year && $month == $first_month) { break; }

			$month--;
			if ($month <= 0) {
				$month = 12;
				$year--;
			}
		}
		
		$settings['post_counts'] = $post_counts;
		return $this->save_settings($settings);
	}
	
	public function get_settings() {
		return get_option('cf_archives');
	}
	
	public function save_settings($value) {
		if (empty($value)) { return false; }
		return update_option('cf_archives', $value);
	}

	/**
	 * This function gets the count of posts for the specified year.  If the year is empty, the current
	 * year will be used
	 *
	 * @param int $year (Optional) - Year to get content from
	 * @return string - Count of posts for the passed in options
	 */
	public function year_post_count($year = 0) {
		// Check to see if the year is set using the get_date function
		$year = $this->get_date($year, 'Y');

		// Build the query parameters for the WP Query
		$posts_query = array(
			'year' => $year,
			'showposts' => -1
		);
		$posts = new WP_Query($posts_query);
		
		return $posts->post_count;
	}
	
	/**
	 * This function gets the count of posts for the specified month and year.  If either the month or year are empty, the current
	 * month/year will be used
	 *
	 * @param string $month (Optional) - Month to get content from
	 * @param string $year (Optional) - Year to get content from
	 * @return string - Count of posts for the passed in options
	 */
	public function month_post_count($month = 0, $year = 0) {
		// Check to see if the month and year are set using the get_date function
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');

		// Build the query parameters for the WP Query
		$posts_query = array(
			'monthnum' => $month,
			'year' => $year,
			'showposts' => -1
		);
		$posts = new WP_Query($posts_query);
		
		return $posts->post_count;
	}
	
	/**
	 * This function gets the count of posts for the specified week and year.  If either the week or year are empty, the current
	 * week/year will be used
	 *
	 * @param int $week (Optional) - Week to get content from
	 * @param int $year (Optional) - Year to get content from
	 * @return string - Count of posts for the passed in options
	 */
	public function week_post_count($week = 0, $year = 0) {
		// Check to see if the month and year are set using the get_date function
		$week = $this->get_date($week, 'W');
		$year = $this->get_date($year, 'Y');

		// Build the query parameters for the WP Query
		$posts_query = array(
			'w' => $week,
			'year' => $year,
			'showposts' => -1
		);
		$posts = new WP_Query($posts_query);
		
		return $posts->post_count;
	}
	
	## Front End
	
	public function display($echo = false, $args = array()) {
		$defaults = array(
			'before'								=> '<ul>',
			'after'									=> '</ul>',
			'before_year'							=> '<li>',
			'after_year'							=> '</li>',
			'year_show'								=> '',
			'before_monthlist'						=> '<ul>',
			'after_monthlist'						=> '</ul>',
			'before_month'							=> '<li>',
			'after_month'							=> '</li>',
			'show_month'							=> __('Show More', 'cfar'),
			'hide_month'							=> __('Hide', 'cfar'),
			'before_postlist'						=> '<ul>',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> '|',
			'exclude_years'							=> '', 		// Array	- Should be an array of years ex: array(2008,2002) - 4 digit years required
			'exclude_months'						=> '', 		// Array	- Should be an array of months ex: array(1,5,7)
			'exclude_year_months'					=> '', 		// Array	- Should be an array of years then an array of months ex: array(2008 => array(1,4), 2004 => array(3,9))  - 4 digit years required
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_first_month'					=> false, 	// Bool		- Will display the content of the first month if true
			'display_year_title'					=> false,	// Bool		- Will display the Year for the months if true
			'display_year_hide'						=> true,	// Bool		- Will display the year hide link if true.  Also will only display if display_year_title = true
			'display_month_hide'					=> true,	// Bool		- Will display the month hide link if true
			'display_post_hide'						=> false,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'post_content_hidden'					=> true,	// Bool		- Will hide the post content if true.
			'newest_first'							=> true		// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-display-args', $args, $defaults);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$html = '';
		
		// Get this year and the first year that we have posts
		$year = $this->get_date($year, 'Y');
		$first_year = $this->get_first_year_post();
		
		while(1) {
			$html .= $this->get_year_html($year, $args);
			if ($year == $first_year) { break; }
			$year--;
		}
		
		if ($echo) {
			echo $html;
		}
		else {
			return $html;
		}
	}

	/**
	 * This function gathers the HTML for a specified year.  If the year is empty, the current year
	 * will be used
	 *
	 * @param int $year (Optional) - Year to get content from
	 * @param array - $args 
	 * @return string - Built HTML for the passed in options
	 */
	public function get_year_html($year = 0, $args = array()) {
		$defaults = array(
			'before'								=> '<ul>',
			'after'									=> '</ul>',
			'before_year'							=> '<li>',
			'after_year'							=> '</li>',
			'show_year'								=> __('Show More', 'cfar'),
			'hide_year'								=> __('Hide', 'cfar'),
			'before_monthlist'						=> '<ul>',
			'after_monthlist'						=> '</ul>',
			'before_month'							=> '<li>',
			'after_month'							=> '</li>',
			'show_month'							=> __('Show More', 'cfar'),
			'hide_month'							=> __('Hide', 'cfar'),
			'before_postlist'						=> '<ul id="cfar-{MONTH}-{YEAR}" class="cfar-month">',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> ' | ',
			'exclude_year_months'					=> '', 		// Array	- Should be an array of years then an array of months ex: array(2008 => array(1,4), 2004 => array(3,9))  - 4 digit years required
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_first_month'					=> false, 	// Bool		- Will display the content of the first month if true
			'display_year_title'					=> false,	// Bool		- Will display the Year for the months if true
			'display_year_hide'						=> true,	// Bool		- Will display the year hide link if true.  Also will only display if display_year_title = true
			'display_month_hide'					=> true,	// Bool		- Will display the month hide link if true
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'newest_first'							=> true		// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-get-year-html-args', $args, $defaults, $year);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$content = '';
		$first_month = true;
		
		// Check to see if the year is set using the get_date function
		$year = $this->get_date($year, 'Y');

		// No need to proceed if there are no posts to display for this year
		if ($this->year_post_count($year) <= 0) { return ''; }
		
		// Remove the before and after args so the sub functions don't use them
		$args['before'] = '';
		$args['after'] = '';

		if ($newest_first) {
			for ($i = 12; $i >= 1; $i--) {
				// Check to see if we are excluding this month, and if so don't process the month
				if (is_array($exclude_year_months[$year]) && in_array($i, $exclude_year_months[$year])) { continue; }
				$content .= $this->get_month_html($i, $year, $args);
			}
		}
		else {
			for ($i = 1; $i <= 12; $i++) {
				// Check to see if we are excluding this month, and if so don't process the month
				if (is_array($exclude_year_months[$year]) && in_array($i, $exclude_year_months[$year])) { continue; }
				$content .= $this->get_month_html($i, $year, $args);
			}
		}
		
		// Gather the full HTML for processing and return
		$html = $before.$content.$after;
		return apply_filters('cfar-get-year-html', $html, $year, $args);
	}
	
	/**
	 * This function gathers the HTML for a specified month and year.  If either the month or year are empty, the current month/year
	 * will be used
	 *
	 * @param int $month (Optional) - Month to get content from
	 * @param int $year (Optional) - Year to get content from
	 * @param array - $args 
	 * @return string - Built HTML for the passed in options
	 */
	public function get_month_html($month = 0, $year = 0, $args = array()) {
		$defaults = array(
			'before'								=> '<ul>',
			'after'									=> '</ul>',
			'before_month'							=> '<li>',
			'after_month'							=> '</li>',
			'before_month_title'					=> '<h2 id="cfar-month-title-{MONTH}-{YEAR}" class="cfar-month-title">',
			'after_month_title'						=> '</h2>',
			'show_month'							=> __(' | Show More', 'cfar'),
			'hide_month'							=> __(' | Hide', 'cfar'),
			'before_postlist'						=> '<ul id="cfar-{MONTH}-{YEAR}" class="cfar-month">',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> ' | ',
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_first_month'					=> false, 	// Bool		- Will display the content of the first month if true
			'display_month_title'					=> true,	// Bool		- Will display the title of the month wrapped in the before/after_month_title
			'display_month_hide'					=> true,	// Bool		- Will display the month hide link if true
			'display_month_content'					=> true,	// Bool		- Will display the month content if true, if false it will use AJAX to display month content
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'newest_first'							=> true,	// Bool		- Will show the newest content first
			'first_month'							=> false	// Bool		- If this is the first month being displayed or not
		);
		$args = apply_filters('cfar-get-month-html-args', $args, $defaults, $month, $year);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$posts_data = array();
		
		// Check to see if the month and year are set using the get_date function
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');

		// No need to proceed if there are no posts to display for this month and year
		if ($this->month_post_count($month, $year) <= 0) { return ''; }

		// Build the query parameters for the WP Query
		$posts_query = array(
			'monthnum' => $month,
			'year' => $year,
			'showposts' => -1
		);
		
		// Check to see if we need to exclude any categories
		if (is_array($exclude_categories) && !empty($exclude_categories)) {
			$posts_query['category__not_in'] = $exclude_categories;
		}
		
		// Check to see if we need to exclude any tags
		if (is_array($exclude_tags) && !empty($exclude_tags)) {
			$posts_query['tag__not_in'] = $exclude_tags;
		}
		
		// Build the Month Title if we need to
		$month_title = '';
		if ($display_month_title) {
			// Build the Month Show/Hide links if we need to
			$month_showhide = '';
			if ($display_month_hide) {
				if ($display_month_content) {
					$show_month_class = ' cfar-item-hide';
					$hide_month_class = '';
				}
				else {
					$show_month_class = '';
					$hide_month_class = ' cfar-item-hide';
				}
				$month_showhide = '
				<span id="cfar-month-show-{MONTH}-{YEAR}" class="cfar-month-showhide cfar-month-show'.$show_month_class.'">'.$show_month.'</span>
				<span id="cfar-month-hide-{MONTH}-{YEAR}" class="cfar-month-showhide cfar-month-hide'.$hide_month_class.'">'.$hide_month.'</span>
				';
				$month_showhide = str_replace('{MONTH}', $month, str_replace('{YEAR}', $year, $month_showhide));
			}
			
			$before_month_title = str_replace('{MONTH}', $month, str_replace('{YEAR}', $year, $before_month_title));
			$month_title = '<div id="cfar-month-title-wrap-'.$month.'-'.$year.'" class="cfar-month-title-wrap">'.$before_month_title.date('F Y', mktime(0,0,0,$month,1,$year)).$month_showhide.$after_month_title.'</div>';
		}
		
		// Go through the before_postlist variable and replace the {MONTH} and {YEAR} items with the month and year being displayed
		$before_postlist = str_replace('{MONTH}', $month, str_replace('{YEAR}', $year, $before_postlist));
		
		$month_content_wrap_class = '';
		if ($display_month_content) {
			$month_content_wrap_class = ' cfar-month-filled';
		}
		
		// Let's build the HTML for the month now that we have the data we need
		$html = $before.$before_month.$month_title.'<div id="cfar-month-content-wrap-'.$month.'-'.$year.'" class="cfar-month-content-wrap'.$month_content_wrap_class.'">';

		// Get the Post Data for the current month using the query vars
		if (is_array($posts_query) && !empty($posts_query) && $display_month_content) {
			$posts_data = $this->get_query_posts($posts_query, $args);
		}

		if (is_array($posts_data) && !empty($posts_data)) {
			$html .= $before_postlist;
			// Go through the built post data and display it
			foreach ($posts_data as $post_id => $post_data) {
				$html .= $this->get_post_html($post_data, $args);
			}
			$html .= $after_postlist;
		}

		$html .= '</div>'.$after_month.$after;
		
		return apply_filters('cfar-get-month-html', $html, $month, $year, $args);
	}
	
	/**
	 * This function gathers the content for a specified month and year.  If either the month or year are empty, the current month/year
	 * will be used.  This function is designed to work with the AJAX display of month content
	 *
	 * @param int $month (Optional) - Month to get content from
	 * @param int $year (Optional) - Year to get content from
	 * @param array - $args 
	 * @return string - Built content for the passed in options
	 */
	public function get_month_content($month = 0, $year = 0, $args = array()) {
		$defaults = array(
			'before_postlist'						=> '<ul id="cfar-{MONTH}-{YEAR}" class="cfar-month">',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> ' | ',
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'newest_first'							=> true,	// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-get-month-content-args', $args, $defaults, $month, $year);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$posts_data = array();
		$html = '';
		
		// Check to see if the month and year are set using the get_date function
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');

		// No need to proceed if there are no posts to display for this month and year
		if ($this->month_post_count($month, $year) <= 0) { return ''; }

		// Build the query parameters for the WP Query
		$posts_query = array(
			'monthnum' => $month,
			'year' => $year,
			'showposts' => -1
		);
		
		// Check to see if we need to exclude any categories
		if (is_array($exclude_categories) && !empty($exclude_categories)) {
			$posts_query['category__not_in'] = $exclude_categories;
		}
		
		// Check to see if we need to exclude any tags
		if (is_array($exclude_tags) && !empty($exclude_tags)) {
			$posts_query['tag__not_in'] = $exclude_tags;
		}
		
		// Get the Post Data for the current month using the query vars
		if (is_array($posts_query) && !empty($posts_query)) {
			$posts_data = $this->get_query_posts($posts_query, $args);
		}

		if (is_array($posts_data) && !empty($posts_data)) {
			// Go through the before_postlist variable and replace the {MONTH} and {YEAR} items with the month and year being displayed
			$before_postlist = str_replace('{MONTH}', $month, str_replace('{YEAR}', $year, $before_postlist));

			$html .= $before_postlist;
			// Go through the built post data and display it
			foreach ($posts_data as $post_id => $post_data) {
				$html .= $this->get_post_html($post_data, $args);
			}
			$html .= $after_postlist;
		}

		return apply_filters('cfar-get-month-content', $html, $month, $year, $args);
	}
	
	/**
	 * This function gathers the HTML for a specified week and year.  If either the week or year are empty, the current week/year
	 * will be used
	 *
	 * @param int $week (Optional) - Week to get content from
	 * @param int $year (Optional) - Year to get content from
	 * @param array - $args 
	 * @return string - Built HTML for the passed in options
	 */
	public function get_week_html($week = 0, $year = 0, $args = array()) {
		$defaults = array(
			'before'								=> '<ul>',
			'after'									=> '</ul>',
			'before_week'							=> '<li>',
			'after_week'							=> '</li>',
			'before_week_title'						=> '<h2 id="cfar-week-title-{MONTH}-{YEAR}" class="cfar-week-title">',
			'after_week_title'						=> '</h2>',
			'show_week'								=> __(' | Show More', 'cfar'),
			'hide_week'								=> __(' | Hide', 'cfar'),
			'before_postlist'						=> '<ul id="cfar-{WEEK}-{YEAR}" class="cfar-week">',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> ' | ',
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_week_hide'						=> true,	// Bool		- Will display the week hide link if true
			'display_week_title'					=> true,	// Bool		- Will display the title of the week wrapped in the before/after_week_title
			'display_week_content'					=> true,	// Bool		- Will display the week content if true, if false it will use AJAX to display week content
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'newest_first'							=> true		// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-get-week-html-args', $args, $defaults, $week, $year);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$posts_data = array();
		
		// Check to see if the week and year are set using the get_date function
		$week = $this->get_date($week, 'W');
		$year = $this->get_date($year, 'Y');
		
		// No need to proceed if there are no posts to display for this week and year
		if ($this->week_post_count($week, $year) <= 0) { return ''; }
		
		// Build the query parameters for the WP Query
		$posts_query = array(
			'w' => $week,
			'year' => $year,
			'showposts' => -1
		);
		
		// Check to see if we need to exclude any categories
		if (is_array($exclude_categories) && !empty($exclude_categories)) {
			$posts_query['category__not_in'] = $exclude_categories;
		}
		
		// Check to see if we need to exclude any tags
		if (is_array($exclude_tags) && !empty($exclude_tags)) {
			$posts_query['tag__not_in'] = $exclude_tags;
		}
		
		// Get the Post Data for the current week using the query vars
		if (is_array($posts_query) && !empty($posts_query)) {
			$posts_data = $this->get_query_posts($posts_query, $args);
		}
		
		// If we don't have any posts to show there is no need to proceed
		if (!is_array($posts_data) || empty($posts_data)) { return ''; }
		
		// Build the Month Title if we need to
		$week_title = '';
		if ($display_week_title) {
			// Build the week Show/Hide links if we need to
			$week_showhide = '';
			if ($display_week_hide) {
				if ($display_week_content) {
					$show_week_class = ' cfar-item-hide';
					$hide_week_class = '';
				}
				else {
					$show_week_class = '';
					$hide_week_class = ' cfar-item-hide';
				}
				$week_showhide = '
				<span id="cfar-week-show-{WEEK}-{YEAR}" class="cfar-week-showhide cfar-week-show'.$show_week_class.'">'.$show_week.'</span>
				<span id="cfar-week-hide-{week}-{YEAR}" class="cfar-week-showhide cfar-week-hide'.$hide_week_class.'">'.$hide_week.'</span>
				';
				$week_showhide = str_replace('{WEEK}', $week, str_replace('{YEAR}', $year, $week_showhide));
			}
			
			$before_week_title = str_replace('{WEEK}', $week, str_replace('{YEAR}', $year, $before_week_title));
			$week_title = '<div id="cfar-week-title-wrap-'.$week.'-'.$year.'" class="cfar-week-title-wrap">'.$before_week_title.$this->get_week_start_date($week, $year).' to '.$this->get_week_end_date($week, $year).$week_showhide.$after_week_title.'</div>';
		}
		
		// Go through the before_postlist variable and replace the {WEEK} and {YEAR} items with the week and year being displayed
		$before_postlist = str_replace('{WEEK}', $week, str_replace('{YEAR}', $year, $before_postlist));
		
		// Let's build the HTML for the month now that we have the data we need
		$html = $before.$before_week.$week_title.'<div id="cfar-week-content-wrap-'.$week.'-'.$year.'" class="cfar-week-content-wrap'.$week_content_wrap_class.'">';
		
		if (is_array($posts_data) && !empty($posts_data)) {
			$html .= $before_postlist;
			// Go through the built post data and display it
			foreach ($posts_data as $post_id => $post_data) {
				$html .= $this->get_post_html($post_data, $args);
			}
			$html .= $after_postlist;
		}
		
		$html .= '</div>'.$after_week.$after;
		
		return apply_filters('cfar-get-week-html', $html, $week, $year, $args);
	}

	/**
	 * This function gathers the content for a specified week and year.  If either the week or year are empty, the current week/year
	 * will be used.  This function is designed to work with the AJAX display of week content
	 *
	 * @param int $week (Optional) - Week to get content from
	 * @param int $year (Optional) - Year to get content from
	 * @param array - $args 
	 * @return string - Built content for the passed in options
	 */
	public function get_week_content($week = 0, $year = 0, $args = array()) {
		$defaults = array(
			'before_postlist'						=> '<ul id="cfar-{WEEK}-{YEAR}" class="cfar-week">',
			'after_postlist'						=> '</ul>',
			'before_post'							=> '<li>',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_date_format'						=> get_option('date_format'),
			'post_display_separator'				=> ' | ',
			'exclude_categories'					=> '', 		// Array	- Should be an array of category ids ex: array(1,3)
			'exclude_tags'							=> '', 		// Array	- Should be an array of tag slugs ex: array('slug-1', 'tag-4')
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'newest_first'							=> true		// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-get-week-html-args', $args, $defaults, $week, $year);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$posts_data = array();
		$html = '';
		
		// Check to see if the week and year are set using the get_date function
		$week = $this->get_date($week, 'W');
		$year = $this->get_date($year, 'Y');
		
		// No need to proceed if there are no posts to display for this week and year
		if ($this->week_post_count($week, $year) <= 0) { return ''; }
		
		// Build the query parameters for the WP Query
		$posts_query = array(
			'w' => $week,
			'year' => $year,
			'showposts' => -1
		);
		
		// Check to see if we need to exclude any categories
		if (is_array($exclude_categories) && !empty($exclude_categories)) {
			$posts_query['category__not_in'] = $exclude_categories;
		}
		
		// Check to see if we need to exclude any tags
		if (is_array($exclude_tags) && !empty($exclude_tags)) {
			$posts_query['tag__not_in'] = $exclude_tags;
		}
		
		// Get the Post Data for the current week using the query vars
		if (is_array($posts_query) && !empty($posts_query)) {
			$posts_data = $this->get_query_posts($posts_query, $args);
		}
		
		if (is_array($posts_data) && !empty($posts_data)) {
			// Go through the before_postlist variable and replace the {WEEK} and {YEAR} items with the week and year being displayed
			$before_postlist = str_replace('{WEEK}', $week, str_replace('{YEAR}', $year, $before_postlist));

			$html = $before_postlist;
			// Go through the built post data and display it
			foreach ($posts_data as $post_id => $post_data) {
				$html .= $this->get_post_html($post_data, $args);
			}
			$html .= $after_postlist;
		}
		
		return apply_filters('cfar-get-week-html', $html, $week, $year, $args);
	}
		
	/**
	 * This function builds the HTML for each post based on the Data passed in
	 *
	 * @param string $data - Post Data to process
	 * @param string $args 
	 * @return string - Compiled HTML for the Data passed in
	 */
	public function get_post_html($data, $args = array()) {
		if (!is_array($data) || empty($data)) { return ''; }
		$defaults = array(
			'before_post'							=> '<li id="cf-post-{post_ID}" class="cf-post">',
			'after_post'							=> '</li>',
			'show_post'								=> __('Show Preview', 'cfar'),
			'hide_post'								=> __('Hide Preview', 'cfar'),
			'post_display_separator'				=> ' | ',
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true,	// Bool		- Will display the post date if true.
			'post_content_hidden'					=> true		// Bool		- Will hide the post content if true.
		);
		$args = apply_filters('cfar-get-post-html-args', $args, $defaults, $data);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$html = '';
		// If the post title is empty, there is no need to do anything
		if (empty($data['title'])) { return ''; }
		
		// Let's build the post content
		$html .= str_replace('{post_ID}', $data['id'], $before_post);
		$html .= '<a href="'.$data['link'].'" id="cfar-post-title-'.$data['id'].'" class="cfar-post-title">'.$data['title'].'</a>';
		
		if ($display_post_date) {
			$html .= '
			<span id="cfar-post-data-'.$data['id'].'" class="cfar-post-date">
				'.$post_display_separator.$data['postdate'].'
			</span>
			';
		}
		
		// Only show the Show/Hide links if we need them, this will also add the post excerpt if it is available
		if (!empty($data['excerpt']) && $display_post_hide) {
			$html .= '
			<span id="cfar-post-showhide-links-'.$data['id'].'" class="cfar-post-showhide-links">
				'.$post_display_separator.'<a href="#" class="cfar-post-show hide-if-no-js" id="cfar-post-show-'.$data['id'].'" rel="cfar-post-show">'.$show_post.'</a><a href="#" class="cfar-post-hide hide-if-no-js cfar-item-hide" id="cfar-post-hide-'.$data['id'].'" rel="cfar-post-hide">'.$hide_post.'</a>
			</span>
			';
			$post_content_class = '';
			if ($post_content_hidden) {
				$post_content_class = ' cfar-item-hide';
			}
			$html .= '<div class="cfar-post-content'.$post_content_class.'" id="cfar-post-content-'.$data['id'].'">'.$data['excerpt'].'</div>';
		}

		// Add the filter on return so we can play with the content as needed
		return apply_filters('cfar-get-post-html', $html, $data, $args);
	}
	
	/**
	 * This function gathers all of the information needed for the post ID being passed in.  Other args include the
	 * post data format.
	 *
	 * @param string $post_id - Post ID to get information for
	 * @param string $args
	 * @return array - Array of post data for the ID passed in
	 */
	public function get_post_data($post_id, $args) {
		$defaults = array(
			'post_date_format'						=> get_option('date_format'),
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
		);
		$args = apply_filters('cfar-get-post-data-args', $args, $defaults, $post_id);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$post_info = array(
			'id' 			=> $post_id,
			'title' 		=> get_the_title(),
			'link' 			=> get_permalink(),
			'author'		=> get_the_author(),
			'postdate'		=> get_the_time($post_date_format),
			'categories'	=> wp_get_post_categories($post_id),
			'tags'			=> wp_get_post_tags($post_id)
		);
		
		if ($display_post_hide) {
			ob_start();
			the_excerpt();
			$post_info['excerpt'] = ob_get_contents();
			ob_end_clean();
		}
		
		return apply_filters('cfar-get-post-data', $post_info, $post_id, $args);
	}
	
	/**
	 * This function runs the WP Query based on the query parameters passed in
	 *
	 * @param array $posts_query - WP Query Parameters
	 * @return array - Built post data based on the query parameters passed in
	 */
	public function get_query_posts($posts_query, $args) {
		$defaults = array(
			'newest_first'							=> true		// Bool		- Will show the newest content first
		);
		$args = apply_filters('cfar-get-query-posts-args', $args, $defaults, $posts_query);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		if (!is_array($posts_query) || empty($posts_query)) { return ''; }
		
		// Lets only get published posts if the specific post_status has not been set
		if (empty($posts_query['post_status'])) {
			$posts_query['post_status'] = 'publish';
		}
		$post_data = array();
		$posts = new WP_Query($posts_query);
		if ($posts->have_posts()) {
			while($posts->have_posts()) {
				$posts->the_post();
				$post_data[get_the_ID()] = $this->get_post_data(get_the_ID(), $args);
			}
		}
		unset($posts);
		wp_reset_query();
		
		if (!$newest_first) {
			$post_data = array_reverse($post_data);
		}
		
		return apply_filters('cfar-get-query-posts', $post_data, $posts_query);
	}
	
	
	## Aux Functions
	
	/**
	 * get_date - Checks to see if the date inputted is present, and if not gets the Date format for the current day
	 *
	 * @param string $date - Date to check
	 * @param string $format - Format to use if the date is not present
	 * @return string - Date passed in if present, if not the current date using the format passed in
	 */
	public function get_date($date = false, $format = 'd') {
		if (!$date) {
			$date = date($format);
		}
		return $date;
	}
	
	/**
	 * This function gets the first month that a posts exists
	 *
	 * @return int - First month a post exists
	 */
	public function get_first_month_post() {
		global $wpdb;
		$query = "SELECT MONTH(post_date) AS `month` FROM $wpdb->posts GROUP BY MONTH(post_date) ORDER BY post_date ASC LIMIT 1";
		$results = $wpdb->get_results($query);
		return $results[0]->month;
	}
	
	/**
	 * This function gets the first year that a post exists
	 *
	 * @return int - First year a post exists
	 */
	public function get_first_year_post() {
		global $wpdb;
		$query = "SELECT YEAR(post_date) AS `year` FROM $wpdb->posts GROUP BY YEAR(post_date) ORDER BY post_date ASC LIMIT 1";
		$results = $wpdb->get_results($query);
		return $results[0]->year;
	}
	
	/**
	 * This function gets the first day of the week for the week and year passed in.  The first day of the week is determined by the
	 * start_of_week option.  If the week or year is empty, the function will get the latest week and year
	 *
	 * @param int $week (Optional) - Week to get date for
	 * @param int $year (Optional) - Year to get date for
	 * @return string - Start Date for the week/year passed in.  Date format controlled by args and date_format option
	 */
	public function get_week_start_date($week = 0, $year = 0) {
		$defaults = array(
			'date_format'						=> get_option('date_format'),
		);
		$defaults = apply_filters('cfar-get-week-start-date-args', $defaults, $week, $year);
		extract($defaults, EXTR_SKIP);
		
		$day = get_option('start_of_week');

		// Check to see if the week and year are set using the get_date function
		$week = $this->get_date($week, 'W');
		$year = $this->get_date($year, 'Y');
		
		return date($date_format, strtotime($year."W".$week.$day));
	}

	/**
	 * This function gets the last day of the week for the week and year passed in.  The last day of the week is determined by the
	 * start_of_week option.  If the week or year is empty, the function will get the latest week and year
	 *
	 * @param int $week (Optional) - Week to get date for
	 * @param int $year (Optional) - Year to get date for
	 * @return string - End Date for the week/year passed in.  Date format controlled by args and date_format option
	 */
	public function get_week_end_date($week = 0, $year = 0) {
		$defaults = array(
			'date_format'						=> get_option('date_format'),
		);
		$defaults = apply_filters('cfar-get-week-end-date-args', $defaults, $week, $year);
		extract($defaults, EXTR_SKIP);
		
		$day = get_option('start_of_week');

		// Check to see if the week and year are set using the get_date function
		$week = $this->get_date($week, 'W');
		$year = $this->get_date($year, 'Y');
		$week++;
		
		return date($date_format, strtotime($year."W".$week.$day));
	}
	
}














































?>