<?php
/*
Plugin Name: CF Archives
Plugin URI: http://crowdfavorite.com
Description: Advanced features for Archives.
Version: 1.5
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// 	ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR', 'wp-content/plugins');
}

load_plugin_textdomain('cf-archives');

// Scripts/Styles

function cfar_head_assets() {
	$wpserver = get_bloginfo('url');
	if(strpos($_SERVER['SERVER_NAME'],'www.') !== false && strpos($wpserver,'www.') === false) {
		$wpserver = str_replace('http://','http://www.',$wpserver);
	}

	$script_data = array(
		'wpserver' => trailingslashit($wpserver)
	);

	wp_enqueue_script('cfar-head', plugins_url('cf-archives/scripts/head.js'));
	wp_localize_script('cfar-head', 'cfar', $script_data);

	wp_enqueue_style('cfar-head', plugins_url('cf-archives/styles/head.css'));
}
add_action('wp_enqueue_scripts', 'cfar_head_assets');

function cfar_admin_assets() {
	$wpserver = get_bloginfo('url');
	if(strpos($_SERVER['SERVER_NAME'],'www.') !== false && strpos($wpserver,'www.') === false) {
		$wpserver = str_replace('http://','http://www.',$wpserver);
	}

	$script_data = array(
		'wpserver' => trailingslashit($wpserver)
	);

	wp_enqueue_script('cfar-admin', plugins_url('cf-archives/scripts/admin.js'));
	wp_localize_script('cfar-admin', 'cfar', $script_data);

	wp_enqueue_style('cfar-admin', plugins_url('cf-archives/styles/admin.css'));
}
add_action('admin_enqueue_scripts', 'cfar_admin_assets');


if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'cf-archives.php')) {
	define('CFAR_FILE', trailingslashit(ABSPATH.PLUGINDIR).'cf-archives.php');
}
else if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'cf-archives/cf-archives.php')) {
	define('CFAR_FILE', trailingslashit(ABSPATH.PLUGINDIR).'cf-archives/cf-archives.php');
}

function cfar_request_handler() {
	if (current_user_can('manage_options')) {
		$blogurl = '';
		if (is_ssl()) {
			$blogurl = str_replace('http://','https://',get_bloginfo('wpurl'));
		}
		else {
			$blogurl = get_bloginfo('wpurl');
		}
		if ($_POST) {
			if (!empty($_POST['cf_action'])) {
				switch ($_POST['cf_action']) {
					case 'cfar_rebuild_archive_batch':
						if (!is_numeric($_POST['cfar_batch_increment']) || !is_numeric($_POST['cfar_batch_offset'])) {
							echo cf_json_encode(array('result'=>false,'message'=>'Invalid quantity or offset'));
							exit();
						}
						$increment = (int) $_POST['cfar_batch_increment'];
						$offset = (int) $_POST['cfar_batch_offset'];
						cfar_rebuild_archive_batch($increment,$offset);
						die();
						break;
					case 'cfar_update_settings':
						cfar_save_settings($_POST['cfar_settings']);
						wp_redirect(trailingslashit($blogurl).'wp-admin/options-general.php?page=cf-archives.php&updated=true');
						die();
						break;
				}
			}
		}
	}
	else {
		if (!empty($_GET['cf_action'])) {
			switch ($_GET['cf_action']) {
				case 'cfar_ajax_month_archive':
					$args = array();
					$year = (int) $_GET['cfar_year'];
					$month = (int) $_GET['cfar_month'];
					$args['year_show'] = $wpdb->escape($_GET['cfar_year_show']);
					$args['year_hide'] = $wpdb->escape($_GET['cfar_year_hide']);
					$args['month_show'] = $wpdb->escape($_GET['cfar_month_show']);
					$args['month_hide'] = $wpdb->escape($_GET['cfar_month_hide']);
					$args['post_show'] = $wpdb->escape($_GET['cfar_post_show']);
					$args['post_hide'] = $wpdb->escape($_GET['cfar_post_hide']);
					$args['category'] = $wpdb->escape($_GET['cfar_category']);
					$args['show_heads'] = $wpdb->escape($_GET['cfar_show_heads']);
					$args['add_div'] = $wpdb->escape($_GET['cfar_add_div']);
					$args['add_ul'] = $wpdb->escape($_GET['cfar_add_ul']);
					$args['print_month_content'] = $wpdb->escape($_GET['cfar_print_month_content']);
					cfar_month_archive($year,$month,$args);
					die();
					break;
			}
		}
	}
}
add_action('wp_loaded', 'cfar_request_handler');

function cfar_rebuild_archive_batch($increment=0,$offset=0) {
	global $wpdb;
	if ($offset == 0) {
		$time = time();
		delete_option('cfar_year_list');
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'cfar_arch_%'");
	}

	// Let the rest of the plugin know that we are rebuilding the archive
	define('CFAR_REBUILDING_ARCHIVE', true);

	// Get posts we want to archive
	$posts_query = new WP_Query(array(
		'posts_per_page' => $increment,
		'offset' => $offset,
		'post_type' => 'post',
		'post_status' => 'publish',
		'suppress_filters' => true,
		'orderby' => 'date',
	));

	$year_list = get_option('cfar_year_list');
	$year_list = is_array($year_list) ? $year_list : array();
	$archives = array();

	$archived_posts_count = 0;
	foreach ($posts_query->posts as $p) {

		$year = get_the_time('Y', $p);
		$month = get_the_time('m', $p);
		$full_date = get_the_time("Y-m-d H:i:s", $p);
		$archive_date = $year.'-'.$month;
		$archive_key = 'cfar_arch_'.$archive_date;
		if (!array_key_exists($archive_key, $archives)) {
			$archives[$archive_key] = get_option($archive_key);
		}

		update_post_meta($p->ID, '_cfar_publish_date', $full_date);

		// Now that we have gathered relevant info, lets build an array for insertion
		$p_key = $full_date.'--'.$p->ID;
		$insert = array(
			'id' => $p->ID,
			'title' => $p->post_title,
			'author' => $p->post_author,
			'link' => get_permalink($p->ID),
			'post_date' => get_the_time('Y-m-d H:i:s', $p),
			'excerpt' => cfar_trim_excerpt($p->post_excerpt, $p->post_content),
			'guid' => $p->guid,
			'categories' => wp_get_post_categories($p->ID),
			'status' => $p->post_status
		);

		$insert = apply_filters('cfar_archive_post', $insert);

		if ($archives[$archive_key] === false) {
			$archives[$archive_key] = array();
			$archives[$archive_key][$p_key] = $insert;
		}
		else {
			$archives[$archive_key][$p_key] = $insert;
		}

		// Update year list
		$yearcheck = $year . '_';

		// Check to see if the current posts year has the post count array
		if (is_array($year_list[$yearcheck]) && !empty($year_list[$yearcheck])) {
			// Increment the proper year/month
			$year_list[$yearcheck][intval($month)]++;
		}
		else {
			// If the current posts year is empty, create the array, and increment as needed
			$year_list[$yearcheck] = array();
			for ($i = 1; $i <= 12; $i++) {
				$year_list[$yearcheck][$i] = 0;
			}
			$year_list[$yearcheck][intval($month)]++;
		}
		// Sort the list so the newest year is first
		krsort($year_list);
		$archived_posts_count++;
	}

	// Lets update the options now
	update_option('cfar_year_list', $year_list);

	foreach($archives as $archive_key => $a) {
		ksort($a);
		update_option($archive_key, $a);
	}

	$total_archived = $offset + $archived_posts_count;
	if ($total_archived >= cfar_get_posts_count()) {
		echo cf_json_encode(array('result'=>false,'finished'=>true,'message'=>true));
	}
	else {
		$message = 'Processing archives ';
		for($i=0;$i<($offset/20);$i++) {
			$message .= ' . ';
		}
		echo cf_json_encode(array('result'=>true,'finished'=>false,'message'=>$message));
	}
	$memory = memory_get_peak_usage();
	$query_count = count($wpdb->queries);
	exit();
}

function cfar_get_posts_count() {
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'");
}

function cfar_add_archive($post) {
	// supply a filter to allow posts to be excluded from archiving passing the full post object
	if (!apply_filters('cfar_do_archive', true, $post)) { return true; }

	// We don't need to add revisions and drafts to the archive
	if ($post->post_status == 'revision' || $post->post_status == 'draft') { return true; }

	$year = get_the_time('Y', $post);
	$month = get_the_time('m', $post);
	$month_string = get_the_time('M', $post);
	$archive_date = $year.'-'.$month;
	$full_date = get_the_time("Y-m-d H:i:s", $post);
	$old_full_date = get_post_meta($post->ID, '_cfar_publish_date', true);

	// Check to see if we are dealing with a scheduled post
	if ($post->post_status == 'future') {
		// If we are updating a post that was once published, lets remove that post from the archives as well
		if ($old_full_date) {
			cfar_remove_old_post_from_archive($post->ID, $old_full_date);
		}

		// Finally let's skip this future post
		return true;
	}

	if ($old_full_date && $full_date != $old_full_date) {
		cfar_remove_old_post_from_archive($post->ID, $old_full_date);
	}

	// Get the archives from the DB related to this post's date
	$archives = get_option('cfar_arch_'.$archive_date);

	// Process Categories for addition
	$category_list = array();
	$categories = wp_get_post_categories($post->ID);

	if (is_array($categories) && !empty($categories)) {
		foreach ($categories as $category) {
			$category_list[] = $category;
		}
	}

	// Now that we have gathered relevant info, lets build an array for insertion
	$post_key = $full_date.'--'.$post->ID;
	$insert[$post_key] = array(
		'id' => $post->ID,
		'title' => $post->post_title,
		'author' => $post->post_author,
		'link' => get_permalink($post->ID),
		'post_date' => get_the_time('Y-m-d H:i:s', $post),
		'excerpt' => cfar_trim_excerpt($post->post_excerpt, $post->post_content),
		'guid' => $post->guid,
		'categories' => $category_list,
		'status' => $post->post_status
	);

	$insert[$post_key] = apply_filters('cfar_archive_post', $insert[$post_key]);

	delete_post_meta($post->ID, '_cfar_publish_date');
	add_post_meta($post->ID, '_cfar_publish_date', $full_date, true);

	// If the archives haven't been setup for this month, add them to the DB now
	if ($archives === false) {
		// NOTE: Autoload has been set to no so this does not get loaded into the WP cache, which could overwhelm it
		add_option('cfar_arch_'.$archive_date, $insert, '', 'no');
	}
	else {
		// If the post is already in the archives, remove it so we can insert our updated post
		if (isset($archives[$post_key])) {
			unset($archives[$post_key]);
		}

		// Lets insert the post into the archives
		$archives[$post_key] = $insert[$post_key];
		ksort($archives);
		// Finally lets insert the updated archives into the DB
		update_option('cfar_arch_'.$archive_date, $archives);
	}

	// Lets update the year list
	$year_list = get_option('cfar_year_list');

	// The current year we will use in the array creation
	$yearcheck = $year.'_';

	if (!is_array($year_list) || empty($year_list)) {
		$year_list = array();

		// Create an array with month numbers and a base count for each month of 0 except the current posts month which needs to be incremented
		for ($i = 1; $i <= 12; $i++) {
			$year_list[$yearcheck][$i] = 0;
			if ($i == intval($month)) {
				$year_list[$yearcheck][$i]++;
			}
		}

		// Insert the new year list into the DB
		// NOTE: Autoload has been set to no so this does not get loaded into the WP cache, which could overwhelm it
		add_option('cfar_year_list', $year_list, '', 'no');
	}
	else {
		// Check to see if the current posts year has the post count array
		if (is_array($year_list[$yearcheck]) && !empty($year_list[$yearcheck])) {
			// Increment the proper year/month
			$year_list[$yearcheck][intval($month)]++;
		}
		else {
			// If the current posts year is empty, create the array, and increment as needed
			for ($i = 1; $i <= 12; $i++) {
				$year_list[$yearcheck][$i] = 0;
				if ($i == intval($month)) {
					$year_list[$yearcheck][$i]++;
				}
			}
		}

		// Sort the list so the newest year is first
		krsort($year_list);

		// Lets update the option now
		update_option('cfar_year_list', $year_list);
	}
	return true;
}

function cfar_publish_post($post_id) {
	cfar_add_archive(get_post($post_id));
}
add_action('publish_post', 'cfar_publish_post', 10, 1);

function cfar_post_transition_status($new_status, $old_status, $post) {
	if ($old_status == 'publish' && $new_status != $old_status) {
		// This is being "unpublished"
		cfar_remove_archive($post_id);
	}
}
add_action('transition_post_status', 'cfar_post_transition_status', 10, 3);

function cfar_remove_archive($post_id) {
	global $wpdb;

	$delete_post = get_post($post_id);
	// If we don't have anything to work with, no need to proceed
	if (empty($delete_post)) { return; }

	if ($delete_post->post_type != 'post') {
		return;
	}

	$year = date('Y',strtotime($delete_post->post_date));
	$month = date('m',strtotime($delete_post->post_date));
	$archive_date = $year.'-'.$month;

	// Get the archives that the post is inside of
	$archives = get_option('cfar_arch_'.$archive_date, true);

	if (is_array($archives) && !empty($archives)) {
		// Remove the post from the archives for the month and year
		unset($archives[$delete_post->post_date.'--'.$delete_post->ID]);
		update_option('cfar_arch_'.$archive_date, $archives);

		$year_list = get_option('cfar_year_list', true);
		$yearcheck = $year.'_';
		if (is_array($year_list) && !empty($year_list)) {
			foreach ($year_list[$yearcheck] as $key => $list_month) {
				if ($key == $month) {
					$year_list[$yearcheck][$key] = $year_list[$yearcheck][$key] - 1;
				}
			}
			update_option('cfar_year_list', $year_list);
		}
	}
	wp_reset_query();
	return true;
}

function cfar_delete_post($post_id) {
	$post = get_post($post_id);
	if (empty($post) || $post->post_status != 'publish') {
		cfar_remove_archive($post_id);
	}
}
add_action('delete_post', 'cfar_remove_archive');

function cfar_remove_old_post_from_archive($post_id, $old_full_date) {
	if (defined('CFAR_REBUILDING_ARCHIVE') && CFAR_REBUILDING_ARCHIVE) { return; }
	$old_date = strtotime($old_full_date);
	$old_year = date('Y', $old_date);
	$old_month = date('m', $old_date);
	$old_month_string = date('M', $old_date);
	$old_archive_date = $old_year.'-'.$old_month;
	$old_key = $old_full_date.'--'.$post_id;

	$archives = get_option('cfar_arch_'.$old_archive_date);

	// If we have something to remove, remove the old post from the old archive
	if (is_array($archives) && !empty($archives)) {
		unset($archives[$old_key]);
		ksort($archives);
		update_option('cfar_arch_'.$old_archive_date, $archives);
	}

	// Lets update the year list
	$year_list = get_option('cfar_year_list');

	// The current year we will use in the array creation
	$yearcheck = $old_year.'_';

	// Check to see if the current posts year has the post count array
	if (is_array($year_list[$yearcheck]) && !empty($year_list[$yearcheck]) && !empty($year_list[$yearcheck][intval($old_month)])) {
		// Decrement the proper year/month
		$year_list[$yearcheck][intval($old_month)]--;

		// Sort the list so the newest year is first
		krsort($year_list);

		// Lets update the option now
		update_option('cfar_year_list', $year_list);
	}
}

function cfar_setting($option) {
	$value = get_option($option);
	if (empty($value)) {
		global $cfar_settings;
		$value = $cfar_settings[$option]['default'];
	}
	return $value;
}

function cfar_admin_menu() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('CF Archives', 'cf-archives')
			, __('CF Archives', 'cf-archives')
			, 10
			, basename(__FILE__)
			, 'cfar_settings_form'
		);
	}
}
add_action('admin_menu', 'cfar_admin_menu');

function cfar_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if ($file == $plugin_file) {
		$settings_link = '<a href="options-general.php?page='.$plugin_file.'">'.__('Settings', 'cf-archives').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'cfar_plugin_action_links', 10, 2);

function cfar_settings_form() {
	global $wpdb;

	$yearlist = get_option('cfar_year_list');

	$settings = maybe_unserialize(get_option('cf_archives'));
	if (htmlspecialchars($settings['excerpt']) == 'yes') {
		$excerpt_yes = ' selected=selected';
		$excerpt_no = '';
	}
	else {
		$excerpt_yes = '';
		$excerpt_no = ' selected=selected';
	}
	if (htmlspecialchars($settings['showyear']) == 'yes') {
		$showyear_yes = ' selected=selected';
		$showyear_no = '';
	}
	else {
		$showyear_yes = '';
		$showyear_no = ' selected=selected';
	}
	if (htmlspecialchars($settings['yearhide']) == 'yes') {
		$yearhide_yes = ' selected=selected';
		$yearhide_no = '';
	}
	else {
		$yearhide_yes = '';
		$yearhide_no = ' selected=selected';
	}
	if (!is_array($settings['exclude_years'])) {
		$settings['exclude_years'] = array();
	}
	if ( isset($_GET['cf_message']) ) {
		switch($_GET['cf_message']) {
			case 'archive_rebuilt':
				print('
					<script type="text/javascript">
						jQuery(document).ready(function() {
							jQuery("#archive_rebuilt").attr("style","");
						});
					</script>
				');
				break;
			default:
				break;
		}
	}
	print('
	<div id="archive_rebuilt" class="updated fade" style="display: none;">
		<p>'.__('Archive Rebuilt.', 'cf-archives').'</p>
	</div>
	<div id="archive_changes" class="updated fade" style="display: none;">
		<p>'.__('To save changes, click the "Save Settings" button at the bottom of the page.', 'cf-archives').'</p>
	</div>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div><h2>'.__('CF Archives', 'cf-archives').'</h2>
		<form id="cfar_settings_form" name="cfar_settings_form" action="" method="post">
			<input type="hidden" name="cf_action" value="cfar_update_settings" />
			<div style="float: left; margin: 20px;">
			<table class="widefat" style="width: 400px; margin-top: 10px;">
				<thead>
					<tr>
						<th scope="col">'.__('Setting','cf-archives').'</th>
						<th scope="col" width="100px" style="text-align:center;">'.__('Value','cf-archives').'</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="vertical-align: middle;">
							<p>
								'.__('Show Post Preview: ','cf-archives').'
							</p>
							<p>
								'.__('When set to "Yes," this will show a link to show/hide the preview for each post.','cf-archives').'
							</p>
						</td>
						<td style="text-align: center; vertical-align: middle;">
							<select name="cfar_settings[excerpt]">
								<option value="yes"'.$excerpt_yes.'>'.__('Yes','cf-archives').'</option>
								<option value="no"'.$excerpt_no.'>'.__('No','cf-archives').'</option>
							</select>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: middle;">
							<p>
								'.__('Show Year Header: ','cf-archives').'
							</p>
							<p>
								'.__('When set to "Yes," this will show the year header for each year.','cf-archives').'
							</p>
						</td>
						<td style="text-align: center; vertical-align: middle;">
							<select name="cfar_settings[showyear]">
								<option value="yes"'.$showyear_yes.'>'.__('Yes','cf-archives').'</option>
								<option value="no"'.$showyear_no.'>'.__('No','cf-archives').'</option>
							</select>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: middle;">
							<p>
								'.__('Display year/month hide links: ','cf-archives').'
							</p>
							<p>
								'.__('When set to "Yes," this will hide the posts until the user clicks on a "Show" link next to the year or month.','cf-archives').'
							</p>
						</td>
						<td style="text-align: center; vertical-align: middle;">
							<select name="cfar_settings[yearhide]">
								<option value="yes"'.$yearhide_yes.'>'.__('Yes','cf-archives').'</option>
								<option value="no"'.$yearhide_no.'>'.__('No','cf-archives').'</option>
							</select>
						</td>
					</tr>
				</tbody>
			</table>
			</div>
			');
			if (is_array($yearlist) && !empty($yearlist)) {
				print('
				<div style="float: left; margin: 20px;">
				<table class="widefat" style="width: 200px; margin-top: 10px; float: left;">
					<thead>
						<tr>
							<th scope="col">'.__('Year','cf-archives').'</th>
							<th scope="col" style="text-align:center;">'.__('No Display','cf-archives').'</th>
						</tr>
					</thead>
					');
					foreach($yearlist as $year => $months) {
						$value = '';
						$yearoutput = str_replace('_','',$year);

						if (in_array($year,$settings['exclude_years'])) {
							$value = 'checked=checked';
						}
						print('
						<tr>
							<td>
								'.$yearoutput.'
							</td>
							<td style="text-align:center;">
								<input type="checkbox" name="cfar_settings[year_exclude]['.$yearoutput.']"'.$value.' />
							</td>
						</tr>
						');
					}
					print('
				</table>
				</div>
				');
			}
			print('
			<div class="clear"></div>
			<h3>Remove Years/Months by Category</h3>
			<table class="widefat archive-table-top">
				<thead>
					<tr>
						<th scope="col" style="text-align:center; width:200px;">'.__('Category','cf-archives').'</th>
						<th scope="col">'.__('Remove Display','cf-archives').'</th>
					</tr>
				</thead>
				<tbody>
				');
				if (!is_array($settings['category_exclude'])) {
					$settings['category_exclude'] = array();
				}
				foreach ($settings['category_exclude'] as $exclude) {
					print('
					<tr id="category_'.$exclude['category'].'">
						<td style="vertical-align:middle; width:200px;">
							<select name="cfar_settings[category_exclude]['.$exclude['category'].'][category_id]" style="max-width:175px;">
							');
							$categories = get_categories(array('hide_empty' => false));
							foreach ($categories as $category) {
								$selected = '';
								if ($category->term_id == $exclude['category']) {
									$selected = ' SELECTED';
								}
								print('<option value="'.$category->term_id.'"'.$selected.'>'.$category->name.'</option>');
							}
							print('
							</select>
							<br />
							<br />
							<input type="button" class="button" value="Remove Category" onClick="cfar_remove_category('.$exclude['category'].')" />
						</td>
						<td style="vertical-align:middle; padding:0;">
							');
							$i = 0;
							foreach ($yearlist as $year => $months) {
								$striping = $i++%2 ? ' alternate' : NULL;
								$yearoutput = str_replace('_','',$year);
								$yearselected = '';
								if (count($exclude['excludes'][$yearoutput]) == 12) {
									$yearselected = ' checked="checked"';
								}
								print('
									<div class="archive-category-year'.$striping.'">
										<div class="archive-year">
											<label>
												<input type="checkbox" class="cfar-year-check" name="cfar_settings[category_exclude]['.$exclude['category'].'][year]['.$yearoutput.']"'.$yearselected.' />
												'.$yearoutput.'
											</label>
										</div>
								');
								foreach ($months as $month => $count) {
									$timestamp = mktime(0, 0, 0, $month, 1, $yearoutput);
								    $month_display = date("M", $timestamp);
									$monthselected = '';
									if (!empty($exclude['excludes'][$yearoutput][$month])) {
										$monthselected = ' checked="checked"';
									}
									print('
										<div class="archive-month">
											<label>
												<input type="checkbox" name="cfar_settings[category_exclude]['.$exclude['category'].'][yearmonth]['.$yearoutput.']['.$month.']"'.$monthselected.' />
												'.$month_display.'
											</label>
										</div>
									');
								}
								print('
								</div>
								');
							}
							print('
							<div class="clear"></div>
						</td>
					</tr>
					');
				}
				print('
				</tbody>
			</table>
			<div id="cfar-categories">
			</div>
			<table class="widefat archive-table-bottom">
				<tfoot>
					<tr>
						<td colspan="2">
							<input type="button" class="button" value="Add New Category" onClick="cfar_add_category()" />
						</td>
					</tr>
				</tfoot>
			</table>
			<p class="submit" style="border-top: none;">
				<input type="submit" name="submit" value="'.__('Save Settings', 'cf-archives').'" />
			</p>
		</form>
		<form id="cfar_settings_form2" name="cfar_settings_form2" action="" method="post">
			<p class="submit" style="border-top: none;">
				<input type="submit" name="submit" value="'.__('Rebuild Archive', 'cf-archives').'" />
			</p>
			<div id="index-status">
				<p></p>
			</div>
		</form>
		<div style="display:none;" id="newitem_SECTION">
			<table class="widefat archive-table" id="category_###SECTION###">
				<tbody>
					<tr>
						<td style="vertical-align:middle; width:200px;">
							<select name="cfar_settings[category_exclude][###SECTION###][category_id]" style="max-width:175px;">
							');
							$categories = get_categories(array('hide_empty' => false));
							foreach ($categories as $category) {
								print('<option value="'.$category->term_id.'">'.$category->name.'</option>');
							}
							print('
							</select>
							<br />
							<br />
							<input type="button" class="button" value="Remove Category" onClick="cfar_remove_category(###SECTION###)" />
						</td>
						<td style="vertical-align:middle; padding:0;">
							');
							$i = 0;
							if (is_array($yearlist) && !empty($yearlist)) {
								foreach ($yearlist as $year => $months) {
									$striping = $i++%2 ? ' alternate' : NULL;
									$yearoutput = str_replace('_','',$year);
									print('
										<div class="archive-category-year'.$striping.'">
											<div class="archive-year">
												<label>
													<input type="checkbox" class="cfar-year-check" name="cfar_settings[category_exclude][###SECTION###][year]['.$yearoutput.']" />
													'.$yearoutput.'
												</label>
											</div>
									');
									foreach ($months as $month => $count) {
										$timestamp = mktime(0, 0, 0, $month, 1, $yearoutput);
									    $month_display = date("M", $timestamp);
										print('
											<div class="archive-month">
												<label>
													<input type="checkbox" name="cfar_settings[category_exclude][###SECTION###][yearmonth]['.$yearoutput.']['.$month.']" />
													'.$month_display.'
												</label>
											</div>
										');
									}
									print('
									</div>
									');
								}
							}
							print('
							<div class="clear"></div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	');
}

function cfar_save_settings($settings) {
	if (!current_user_can('manage_options')) {
		return;
	}
	$exclude_years = array();

	if (is_array($settings['year_exclude'])) {
		foreach ($settings['year_exclude'] as $year => $value) {
			$exclude_years[] = $year;
		}
	}

	if (is_array($settings['category_exclude'])) {
		$category_exclude = array();
		foreach ($settings['category_exclude'] as $exclude) {
			$excludes = array();
			if (is_array($exclude['yearmonth']) && !empty($exclude['yearmonth'])) {
				foreach ($exclude['yearmonth'] as $year => $months) {
					$excludes[$year] = array();
					foreach ($months as $month => $month_status) {
						$excludes[$year][$month] = $month;
					}
				}
			}

			if (is_array($exclude['year']) && !empty($exclude['year'])) {
				foreach ($exclude['year'] as $year => $status) {
					$excludes[$year] = array(
						'1' => '1',
						'2' => '2',
						'3' => '3',
						'4' => '4',
						'5' => '5',
						'6' => '6',
						'7' => '7',
						'8' => '8',
						'9' => '9',
						'10' => '10',
						'11' => '11',
						'12' => '12'
					);
				}
			}

			$category_exclude[$exclude['category_id']] = array(
				'category' => $exclude['category_id'],
				'excludes' => $excludes
			);
		}
	}

	update_option('cf_archives', array(
		'excerpt' => $settings['excerpt'],
		'showyear' => $settings['showyear'],
		'yearhide' => $settings['yearhide'],
		'exclude_years' => $exclude_years,
		'category_exclude' => $category_exclude
	));
}

function cfar_trim_excerpt($excerpt = '', $content = '',$length = 250) {
	if (!empty($excerpt)) { return $excerpt; }
	$content = str_replace(']]>', ']]&gt;', $content);
	$content = preg_replace('/<img[^>]*>/','',$content);
	$content = preg_replace('/\[(.*?)\]/','',$content);
	$content = strip_tags($content);

	if(strlen($content) > $length) {
		$content = substr($content, 0, $length);
		$content = substr($content, 0, strrpos($content, ' '));
	}
	$content = cfar_close_opened_tags($content);
	return $content;
}

/**
 * Function to close any opened tags in a string
 * Makes no attempt to put them in the proper place, just makes sure that everything closes
 *
 * @param string $string
 * @return string
 */
function cfar_close_opened_tags($string) {
	preg_match_all('/<(\w+)/',$string,$open_tags);
	preg_match_all('/<\/(\w+)/',$string,$close_tags);

	// if open & close match then get out quickly
	if(count($open_tags[1]) == count($close_tags[1])) {
		return $string;
	}

	// log found open tags
	$tags = array();
	foreach($open_tags[1] as $found) {
		if(!isset($tags[$found])) {
			$tags[$found] = 0;
		}
		$tags[$found]++;
	}

	// process found close tags
	foreach($close_tags[1] as $found) {
		$tags[$found]--;
		if($tags[$found] == 0) { unset($tags[$found]); }
	}

	// feeble attempt to get a semblance of order
	$tags = array_reverse($tags,true);
	foreach($tags as $tagname => $tag_count) {
		if($tag_count) {
			$string .= '</'.$tagname.'>';
		}
	}
	return $string;
}

function cfar_get_head_list($args=null) {
	global $wpdb;

	$defaults = array(
		'before'=>'<ul>',
		'after'=>'</ul>',
		'before_year'=>'<li>',
		'after_year'=>'</li>',
		'before_monthlist'=>'<ul>',
		'after_monthlist'=>'</ul>',
		'before_month'=>'<li>',
		'after_month'=>'</li>'
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	$yearlist = get_option('cfar_year_list');
	$return = '';
	$return .= $before;
	foreach($yearlist as $year => $months) {
		$yearoutput = str_replace('_','',$year);
		$return .= $before_year;
		$return .= '<a class="year-link" href="#'.$yearoutput.'">'.$yearoutput.'</a>';
		$return .= $after_year.$before_monthlist;
		foreach($months as $month => $count) {
			$return .= $before_month;
			if ($count > 0) {
				$return .= '<a class="month-link" href="#'.$yearoutput.'-'.date('F', mktime(0,0,0,$month)).'">'.$month.'</a>';
			}
			else {
				$return .= '<span class="month-nolink">'.$month.'</span>';
			}
			$return .= $after_month;
		}
		$return .= $after_monthlist.$after_year;
	}
	$return .= $after;

	return $return;
}

function cfar_yearly_list($args = null) {
	echo cfar_get_yearly_list($args);
}

function cfar_get_yearly_list($args=null) {
	global $wpdb;
	$return = '';
	$defaults = array(
		'before' => '<ul>'
		, 'after' => '</ul>'
		, 'before_year' => '<li>'
		, 'after_year' => '</li>'
		, 'before_monthlist' => '<li><ul>'
		, 'after_monthlist' => '</ul></li>'
		, 'before_month' => '<li>'
		, 'after_month' => '</li>'
		, 'month_php_format' => 'M'
		, 'category' => ''
		, 'exclude_years' => array()
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	$yearlist = get_option('cfar_year_list');
	$settings = get_option('cf_archives');

	if (empty($exclude_years) && is_array($settings['exclude_years'])) {
		$exclude_years = $settings['exclude_years'];
	}

	$return .= $before;

	if (is_array($yearlist) && !empty($yearlist)) {
		foreach($yearlist as $year => $months) {
			$yearcount = 0;
			$yearreturn = '';
			$yearoutput = str_replace('_','',$year);
			if (!in_array($yearoutput, $exclude_years)) {
				$yearreturn .= $before_year.'<a class="year-link" href="#_'.$yearoutput.'">'.$yearoutput.'</a>'.$after_year.$before_monthlist;
				foreach($months as $month => $count) {
					$yearreturn .= $before_month;
					$month_name = date($month_php_format, mktime(0,0,0,$month,1,$yearoutput));
					if($category != 0) {
						$count = 0;
						$posts = get_option("cfar_arch_".$yearoutput."-".date('m', mktime(0,0,0,$month,1,$yearoutput)));
						if (is_array($posts)) {
							foreach($posts as $post) {
								if (is_array($post['categories']) && in_array($category,$post['categories'])) {
									$count++;
								}
							}
						}
					}

					if (is_array($settings['category_exclude']) && isset($settings['category_exclude']) && cfar_check_exclude($settings['category_exclude'], $category, $yearoutput, $month)) {
						$count = 0;
					}
					if ($count > 0) {
						$yearreturn .= '<a class="month-link" href="#_'.$yearoutput.'-'.$month.'">'.$month_name.'</a>';
					}
					else {
						$yearreturn .= '<span class="month-nolink">'.$month_name.'</span>';
					}
					$yearreturn .= $after_month;
					$yearcount += $count;
				}
				$yearreturn .= $after_monthlist.$after_year;
			}
			if ($yearcount > 0) {
				$return .= $yearreturn;
			}
		}
	}
	$return .= $after;
	return $return;
}

function cfar_archive_list($args = null) {
	echo cfar_get_archive_list($args);
}

function cfar_get_archive_list($args = null) {
	global $wpdb;
	$return = '';
	$defaults = array(
		'year_show' => ''
		, 'year_hide' => ''
		, 'month_show' => ''
		, 'month_hide' => ''
		, 'post_show' => ''
		, 'post_hide' => ''
		, 'category' => ''
		, 'show_heads' => 'show'
		, 'print_month_content' => 'show'
		, 'add_div' => 'show'
		, 'add_ul' => 'show'
		, 'newest_first' => true
		, 'show_excerpt' => ''
		, 'show_year_header' => ''
		, 'show_month_hide' => ''
		, 'exclude_years' => ''
		, 'show_first_month' => false
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	$yearlist = get_option('cfar_year_list');
	if($category != 0) {
		$return .= '<span id="cfar-category" style="display:none;">'.$category.'</span>';
	}
	$first = true;
	if (is_array($yearlist) && !empty($yearlist)) {
		foreach($yearlist as $year => $months) {
			$yearoutput = str_replace('_','',$year);

			if ($first) {
				$args['first_year'] = $yearoutput;
				$first = false;
			}

			$return .= cfar_get_year_archive($yearoutput,$args);
		}
	}
	return $return;
}

function cfar_year_archive($yearinput='',$args = null) {
	echo cfar_get_year_archive($yearinput,$args);
}

function cfar_get_year_archive($yearinput='',$args = null) {
	global $wpdb;
	$return = '';
	$settings = maybe_unserialize(get_option('cf_archives'));
	$defaults = array(
		'show_year_header' => ''
		, 'exclude_years' => array()
		, 'show_first_month' => false
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	$settings = maybe_unserialize(get_option('cf_archives'));

	if (empty($exclude_years) && is_array($settings['exclude_years'])) {
		$exclude_years = $settings['exclude_years'];
	}
	if ($show_year_header != '') {
		$settings['showyear'] = $show_year_header;
	}

	if ($yearinput != '') {
		$yearlist = get_option('cfar_year_list');
		$print = '';
		$first = true;

		global $cfar_first_month_posts;
		$cfar_first_month_posts = true;

		if (is_array($yearlist) && !empty($yearlist)) {
			foreach($yearlist as $year => $months) {
				$yearcount = 0;
				$yearoutput = str_replace('_','',$year);

				if ($yearoutput != $first_year) {
					$first = false;
					$cfar_first_month_posts = false;
				}

				if (!in_array($yearoutput, $exclude_years)) {
					if ($yearoutput == $yearinput ) {
						krsort($months);
						foreach($months as $month => $count) {
							if ($count > 0) {
								$args['found_first_month'] = false;
								if ($first || $cfar_first_month_posts) {
									$first = false;
									$cfar_first_month_posts = false;
									$args['found_first_month'] = true;
								}
								$print .= cfar_month_get_archive($yearoutput,date('m', mktime(0,0,0,$month,1,$yearoutput)),$args);
								$yearcount++;
							}
						}
					}
					if ($yearcount > 0) {
						if (htmlspecialchars($settings['showyear']) == 'yes') {
							$return .= '<h2 class="yearhead" id="_'.$yearoutput.'">'.$yearoutput.'</h2>';
							$return .= $print;
						}
						else {
							$return .= '<div id="_'.$yearoutput.'">'.$print.'</div>';
						}
					}
				}
			}
		}
	}
	return $return;
}

function cfar_month_archive($year = '',$month = '',$args = null) {
	$month = date('m', mktime(0,0,0,$month,1,$year));
	echo cfar_month_get_archive($year,$month,$args);
}

function cfar_month_get_archive($year='',$month='',$args = null) {
	global $wpdb;
	$defaults = array(
		'month_show' => ''
		, 'month_hide' => ''
		, 'show_heads' => 'show'
		, 'print_month_content' => 'show'
		, 'add_div' => 'show'
		, 'add_ul' => 'show'
		, 'show_month_hide' => ''
		, 'show_first_month' => false
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	if ($year != '' && $month != '') {
		$return = '';
		$posts = get_option("cfar_arch_".$year."-".$month);
		$content = '';
		$settings = maybe_unserialize(get_option('cf_archives'));

		if (is_array($settings['category_exclude']) && isset($settings['category_exclude']) && cfar_check_exclude($settings['category_exclude'], $category, $year, $month)) {
			return false;
		}

		if ($show_month_hide != '') {
			$settings['yearhide'] = $show_month_hide;
		}
		$showyear = '';
		$hidemonth = '';
		if (htmlspecialchars($settings['yearhide']) == 'yes') {
			if ($month_show != '') {
				$month_show_text = $month_show;
			}
			else {
				$month_show_text = __('Show More','cf-archives');
			}
			if ($month_hide != '') {
				$month_hide_text = $month_hide;
			}
			else {
				$month_hide_text = __('Hide','cf-archives');
			}

			if ($found_first_month && $show_first_month && $first_year == $year) {
				$show_show_text = ' style="display:none;"';
				$show_hide_text = '';
				$hidemonth = '';
				$print_month_content = 'show';
				$args['print_month_content'] = 'show';
			}
			else {
				$show_show_text = '';
				$show_hide_text = ' style="display:none;"';
				$hidemonth = ' style="display: none;"';
			}

			$showyear = '<span class="month-show" id="show-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'" onClick="showContent(\''.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'\');"'.$show_show_text.'> | '.$month_show_text.'</span>';
			$showyear .= '<span class="month-show" id="hide-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'" onClick="hideContent(\''.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'\')"'.$show_hide_text.'> | '.$month_hide_text.'</span>';
		}
		else {
			$showyear = '';
		}
		$get_posts = cfar_get_month_posts($year,$month,$args);

		global $cfar_first_month_posts;
		if ($get_posts['count'] == 0 && $found_first_month) {
			$cfar_first_month_posts = true;
		}

		if ($print_month_content == 'show' && $get_posts['content'] != '') {
			if ($show_heads == 'show') {
				$return .= '<h2 class="monthhead" id="_'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'">'.date('F', mktime(0,0,0,$month,1,$year)).' '.$year.$showyear.'</h2>';
			}
			else {
				$hidemonth = '';
			}
			if ($add_div == 'show') {
				$return .= '<div class="month-content" id="content-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'"'.$hidemonth.'>';
			}
			if ($add_ul == 'show') {
				$return .= '<ul id="ul-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'">';
			}
			$return .= $get_posts['content'];
			if ($add_ul == 'show') {
				$return .= '</ul>';
			}
			if ($add_div == 'show') {
				$return .= '</div>';
			}
		}
		if($print_month_content != 'show' && $get_posts['count'] > 0) {
			if($show_heads == 'show') {
				$return .= '<h2 class="monthhead" id="_'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'" onClick="showMonth('.$year.','.date('n', mktime(0,0,0,$month,1,$year)).');"><span onClick="showContent(\''.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'\');">'.date('F', mktime(0,0,0,$month,1,$year)).' '.$year.'</span>'.$showyear.'</h2>';
			}
			else {
				$hidemonth = '';
			}
			if($add_div == 'show') {
				$return .= '<div class="month-content" id="content-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'"></div>';
			}
		}

		return $return;
	}
}

function cfar_get_month_posts($year='',$month='',$args = null) {
	global $wpdb;
	$defaults = array(
		'post_show' => ''
		, 'post_hide' => ''
		, 'newest_first' => true
		, 'category' => ''
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	$posts = get_option('cfar_arch_'.$year.'-'.$month);
	$content = '';
	$settings = maybe_unserialize(get_option('cf_archives'));
	$showyear = '';
	$hidemonth = '';
	$post_count = 0;
	$return = '';

	if ($show_excerpt != '') {
		$settings['excerpt'] = $show_excerpt;
	}

	if (is_array($posts)) {
		if ($newest_first) {
			$posts = array_reverse($posts,true);
		}
		foreach($posts as $post) {
			if ($post['status'] != 'publish') { continue; }
			if (htmlspecialchars($settings['excerpt']) == 'yes') {
				if ($post_show != '') {
					$post_show_text = $post_show;
				}
				else {
					$post_show_text = __('Show Preview','cf-archives');
				}
				if ($post_hide != '') {
					$post_hide_text = $post_hide;
				}
				else {
					$post_hide_text = __('Hide','cf-archives');
				}
				$showexcerpt = '<a href="#" class="month-post-show cf-show" id="show-'.$post['id'].'" style=""> | '.$post_show_text.'</a>';
				$showexcerpt .= '<a href="#" class="month-post-show cf-hide" id="hide-'.$post['id'].'" style="display: none"> | '.$post_hide_text.'</a>';

			}
			else {
				$showexcerpt = '';
			}
			$link = '';
			$title = '';
			$author = '';
			$postdate = '';
			$excerpt = '';

			if ($category != 0) {
				if (is_array($post['categories']) && in_array($category,$post['categories'])) {
					if ($print_month_content == 'show') {
						$category_ID = $post['categories'];
						$link = $post['link'];
						$title = $post['title'];
						$author = $author_info->display_name;
						$postdate = date('M j, Y',strtotime($post['post_date']));
						if (htmlspecialchars($settings['excerpt']) == 'yes') {
							$excerpt = '<br /><div id="post-'.$post['id'].'" class="postexcerpt" style="display: none;">'.$post['excerpt'].'</div>';
						}
					}
					$post_count++;
				}
			}
			else {
				if ($print_month_content == 'show') {
					$link = $post['link'];
					$title = $post['title'];
					$author = $author_info->display_name;
					$postdate = date('M j, Y',strtotime($post['post_date']));
					if (htmlspecialchars($settings['excerpt']) == 'yes') {
						$excerpt = '<br /><div id="post-'.$post['id'].'" class="postexcerpt" style="display: none;">'.$post['excerpt'].'</div>';
					}
				}
				$post_count++;
			}

			if ($print_month_content == 'show') {
				$authoroutput = '';
				$dateoutput = '';

				$post_settings = array(
					'id' => $post['id'],
					'title' => $title,
					'link' => $link,
					'author' => $author,
					'postdate' => $postdate,
					'showlink' => $showexcerpt,
					'excerpt' => $excerpt,
					'category' => $category,
				);

				$post_settings = apply_filters('cfar_display_post', $post_settings);

				if (!empty($post_settings['title']) && !empty($post_settings['link'])) {
					if ($show_author == 'yes' && !empty($post_settings['author'])) {
						$authoroutput = ' | '.__('By: ','cf-archives').$post_settings['author'];
					}
					if (!empty($post_settings['postdate'])) {
						$dateoutput = ' | '.$post_settings['postdate'];
					}
					$output = '<li><a href="'.$post_settings['link'].'">'.$post_settings['title'].'</a>'.$dateoutput.$authoroutput.$post_settings['showlink'].$post_settings['excerpt'].'</li>';

					$post_settings['dateoutput'] = $dateoutput;
					$post_settings['authoroutput'] = $authoroutput;

					$output = apply_filters('cfar_display_output', $output, $post_settings);
					$content .= $output;
				}
			}

		}
	}

	$return = array('content' => $content, 'count' => $post_count);

	return $return;
}

function cfar_check_exclude($settings, $category, $yearoutput, $month) {
	// Filter the output from the categories by month and by year
	if (is_array($settings) && !empty($settings)) {
		foreach ($settings as $category_id => $exclude) {
			// We don't need to worry about filtering if the categories don't match
			if ($category_id != $category) { continue; }
			if (is_array($exclude['excludes'][$yearoutput])) {
				if (!empty($exclude['excludes'][$yearoutput][intval($month)])) {
					return true;
				}
			}
		}
	}
	return false;
}

function cfar_widget($args) {
	global $wpdb;
	extract($args);
	$options = get_option('cfar_widget');
	$title = empty($options['title']) ? __('WP Archives','cf-archives') : apply_filters('widget_title', $options['title']);
	echo $before_widget.$before_title.$title.$after_title;

	$yearlist = get_option('cfar_year_list');

	if (is_array($yearlist) && !empty($yearlist)) {
		foreach($yearlist as $year => $months) {
			$yearoutput = str_replace('_','',$year);
			if ($options['cfar-hidemonthly'] && $options['cfar-monthly']) {
				$after_year = '<span class="cfar-widget-hidemonthly" onClick="showContent(\'months-'.$yearoutput.'\')"> | '.__('More','cf-archives').'</span>';
			}
			else {
				$after_year = '';
			}
			print('<ul><li><a class="year-link" href="'.get_permalink($options['archive-id']).'#_'.$yearoutput.'">'.$yearoutput.'</a>'.$after_year);
			if ($options['cfar-monthly']) {
				if ($options['cfar-hidemonthly']) {
					print('<ul class="cfar-widget-month-hidden" id="months-'.$yearoutput.'">');
				}
				else {
					print('<ul class="cfar-widget-month">');
				}
				foreach($months as $month => $count) {
					print('<li>');
					$month_name = date('M', mktime(0,0,0,$month,1,$year));
					if ($count > 0) {
						print('<a class="month-link" href="'.get_permalink($options['archive-id']).'#_'.$yearoutput.'-'.$month.'">'.$month_name.'</a>');
					}
					else {
						print('<span class="month-nolink">'.$month_name.'</span>');
					}
					print('</li>');
				}
				print('</ul>');
			}
			print('</li></ul>');
		}       }
	echo $after_widget;
}

function cfar_widget_control() {
	global $wpdb, $post;
	$options = $newoptions = get_option('cfar_widget');
	if ($_POST['cfar-submit']) {
		$newoptions['title'] = strip_tags(stripslashes($_POST['title']));
		$newoptions['cfar-monthly'] = isset($_POST['cfar-monthly']);
		$newoptions['cfar-hidemonthly'] = isset($_POST['cfar-hidemonthly']);
		$newoptions['cfar-archive-id'] = strip_tags(stripslashes($_POST['cfar-archive-id']));
	}
	if ($options != $newoptions) {
		$options = $newoptions;
		update_option('cfar_widget',$options);
	}
	$title = attribute_escape($options['title']);
	$cfar_monthly = $options['cfar-monthly'] ? 'checked="checked"' : '';
	$cfar_hidemonthly = $options['cfar-hidemonthly'] ? 'checked="checked"' : '';
	$cfar_archive_id = attribute_escape($options['cfar-archive-id']);

	$old_post = $post;

	$pages = new WP_Query(array(
		'post_type' => 'page'
	));

	?>
		<p>
			<label for="title"><?php _e('Title:','cf-archives'); ?></label><input class="widefat" id="title" name="title" type="text" value="<?php print(htmlspecialchars($title)); ?>" />
		</p>
		<p>
			<label for="cfar-archive-id"><?php _e('Archive Page:','cf-archives'); ?></label>
			<br />
			<select id="cfar-archive-id" name="cfar-archive-id" class="widefat" style="width: 230px;">
				<option value="empty"><?php _e('--Select Page--', 'cf-archives'); ?></option>
				<?php
				while ($pages->have_posts()) {
					$pages->the_post();

					$selected = '';
					if ($cfar_archive_id == get_the_ID()) {
						$selected = ' selected=selected';
					}

					?>
					<option value="<?php echo esc_attr(get_the_ID()); ?>"<?php echo $selected; ?>><?php the_title(); ?></option>
					<?php
				}
				?>
			</select>
		</p>
		<p>
			<input class="checkbox" type="checkbox" id="cfar-monthly" name="cfar-monthly" <?php echo $cfar_monthly; ?> /> <label for="cfar-monthly"><?php _e('Show Monthly','cf-archives'); ?></label>
			<br />
			<input class="checkbox" type="checkbox" id="cfar-hidemonthly" name="cfar-hidemonthly" <?php echo $cfar_hidemonthly; ?> /> <label for="cfar-hidemonth"><?php _e('Hide Months','cf-archives'); ?></label>
		</p>
		<input type="hidden" id="cfar-submit" name="cfar-submit" value="1" />
	<?php

	$post = $old_post;
	wp_reset_query();
}

function cfar_widget_init() {
	$widget_ops = array('classname' => 'widget_cfar_archive', 'description' => __('Widget for linking to the custom archives','cf-archives'));
	wp_register_sidebar_widget('cf-archives',__('CF Archives','cf-archives'),'cfar_widget',$widget_ops);
	wp_register_widget_control('cf-archives',__('CF Archives','cf-archives'),'cfar_widget_control');
}
add_action('init','cfar_widget_init');


/**
 *
 * Other Plugin Integration
 *
 **/

// README HANDLING
add_action('admin_init','cfar_add_readme');

/**
 * Enqueue the readme function
 */
function cfar_add_readme() {
	if(function_exists('cfreadme_enqueue')) {
		cfreadme_enqueue('cf-archives','cfar_readme');
	}
}

/**
 * return the contents of the links readme file
 * replace the image urls with full paths to this plugin install
 *
 * @return string
 */
function cfar_readme() {
	$file = realpath(dirname(__FILE__)).'/README.txt';
	if(is_file($file) && is_readable($file)) {
		$markdown = file_get_contents($file);
		$markdown = preg_replace('|!\[(.*?)\]\((.*?)\)|','![$1]('.WP_PLUGIN_URL.'/cf-archives/$2)',$markdown);
		return $markdown;
	}
	return null;
}



?>
