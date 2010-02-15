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
		
		// Get the current Month
		$month = $this->get_date(false, 'm');
		// Get the current Year
		$year = $this->get_date(false, 'Y');
		
		global $wpdb;
		
		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month` FROM $wpdb->posts GROUP BY MONTH(post_date) ORDER BY post_date ASC LIMIT 1";
		$results = $wpdb->get_results($query);
		$first_year = $results[0]->year;
		$first_month = $results[0]->month;

		$post_counts = array();
		$count = 1;
		while (1) {
			$month = zeroise($month, 2); 
			$year = zeroise($year, 4);
			$count = $this->month_posts($month, $year);

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
	 * This function gets the count of posts for the specified month and year.  If either the month or year are empty, the current
	 * month/year will be used
	 *
	 * @param string $month (Optional) - Month to get content from
	 * @param string $year (Optional) - Year to get content from
	 * @return string - Count of posts for the passed in options
	 */
	public function month_posts($month = 0, $year = 0) {
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
			'post_content_hidden'					=> true		// Bool		- Will hide the post content if true.
		);
		
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		
		
		
	}
	
	/**
	 * This function gathers the HTML for a specified month and year.  If either the month or year are empty, the current month/year
	 * will be used
	 *
	 * @param string $month (Optional) - Month to get content from
	 * @param string $year (Optional) - Year to get content from
	 * @param string $args 
	 * @return string - Built HTML for the passed in options
	 */
	public function get_month_html($month = 0, $year = 0, $args = array()) {
		$defaults = array(
			'before_month'							=> '<li>',
			'after_month'							=> '</li>',
			'show_month'							=> __('Show More', 'cfar'),
			'hide_month'							=> __('Hide', 'cfar'),
			'before_postlist'						=> '<ul id="cf-archives-{MONTH}-{YEAR}" class="cf-archives-month">',
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
			'display_month_hide'					=> true,	// Bool		- Will display the month hide link if true
			'display_post_hide'						=> true,	// Bool		- Will display the post show/hide link if true.  If false the post excerpt will not be loaded
			'display_post_date'						=> true		// Bool		- Will display the post date if true.
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
		
		$posts_data = array();
		
		// Check to see if the month and year are set using the get_date function
		$month = $this->get_date($month, 'm');
		$year = $this->get_date($year, 'Y');

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
		
		// If we don't have any posts to show there is no need to proceed
		if (!is_array($posts_data) || empty($posts_data)) { return ''; }
		
		// Go through the before_postlist variable and replace the {MONTH} and {YEAR} items with the month and year being displayed
		$before_postlist = str_replace('{MONTH}', $month, str_replace('{YEAR}', $year, $before_postlist));
		
		// Let's build the HTML for the month now that we have the data we need
		$html = $before_month.$before_postlist;
		// Go through the built post data and display it
		foreach ($posts_data as $post_id => $post_data) {
			$html .= $this->get_post_html($post_data, $args);
		}
		$html .= $after_postlist.$after_month;
		
		return apply_filters('cfar-get-month-html', $html, $month, $year, $args);
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
			$post_info['excerpt'] = get_the_excerpt();
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
		if (!is_array($posts_query) || empty($posts_query)) { return ''; }
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
	
}














































?>