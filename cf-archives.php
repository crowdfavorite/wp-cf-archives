<?php
/*
Plugin Name: CF Archives 
Plugin URI: http://crowdfavorite.com 
Description: Advanced features for Archives. 
Version: 2.0
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

	// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

## Constants
	define('CFAR_VERSION', '2.0');
	define('CFAR_DIR',trailingslashit(realpath(dirname(__FILE__))));
	define('CFAR_DIR_URL', trailingslashit(get_bloginfo('wpurl')).trailingslashit(PLUGINDIR).trailingslashit(basename(dirname(__FILE__))));

## Includes
	include('classes/archives.class.php');
	// include('classes/message.class.php');

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR', 'wp-content/plugins');
}

load_plugin_textdomain('cfar');


## General Functions

function cfar_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfar_admin_css':
				cfar_admin_css();
				die();
				break;
			case 'cfar_admin_js':
				cfar_admin_js();
				die();
				break;
			case 'cfar_css':
				cfar_css();
				die();
				break;
			case 'cfar_js':
				cfar_js();
				die();
				break;
		}
	}
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfar_update_settings':
				cfar_update_settings();
				break;
			case 'cfar_rebuild_archive':
				cfar_rebuild_archive();
				die();
				break;
			case 'cfar-month-ajax-archive':
				pp($_POST);
				die();
				$month = 0;
				$year = 0;
				if (!empty($_POST['cfar_year']) && is_numeric($_POST['cfar_year'])) {
					$year = $_POST['cfar_year'];
				}
				if (!empty($_POST['cfar_other']) && is_numeric($_POST['cfar_other'])) {
					$month = $_POST['cfar_other'];
				}
				cfar_month_ajax_archive($month, $year);
				die();
				break;
			case 'cfar-week-ajax-archive':
				$week = 0;
				$year = 0;
				if (!empty($_POST['cfar_year']) && is_numeric($_POST['cfar_year'])) {
					$year = $_POST['cfar_year'];
				}
				if (!empty($_POST['cfar_other']) && is_numeric($_POST['cfar_other'])) {
					$week = $_POST['cfar_other'];
				}
				cfar_week_ajax_archive($week, $year);
				die();
				break;
		}
	}
	
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	
}
add_action('init', 'cfar_request_handler');

function cfar_plugin_action_links($links, $file) {
	$plugin_file = trailingslashit(basename(dirname(__FILE__))).basename(__FILE__);
	if ($file == $plugin_file) {
		$settings_link = '<a href="options-general.php?page=cf-archives">'.__('Settings', 'cfar').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'cfar_plugin_action_links', 10, 2);


## CSS/JS Functions

if (!empty($_GET['page']) && $_GET['page'] == 'cf-archives') {
	wp_enqueue_script('jquery');
	wp_enqueue_script('cfar-admin-js', trailingslashit(get_bloginfo('url')).'?cf_action=cfar_admin_js', array('jquery'), CFAR_VERSION);
	wp_enqueue_style('cfar-admin-css', trailingslashit(get_bloginfo('url')).'?cf_action=cfar_admin_css', array(), CFAR_VERSION, 'screen');
	wp_enqueue_style('cfar-css', trailingslashit(get_bloginfo('url')).'?cf_action=cfar_css', array(), CFAR_VERSION, 'screen');
	wp_enqueue_script('cfar-js', trailingslashit(get_bloginfo('url')).'?cf_action=cfar_js', array('jquery'), CFAR_VERSION);
}

function cfar_admin_css() {
	header('Content-type: text/css');
	echo file_get_contents(CFAR_DIR.'css/admin.css');
	do_action('cfar-admin-css');
	die();
}

function cfar_admin_js() {
	header('Content-type: text/javascript');
	echo file_get_contents(CFAR_DIR.'js/admin.js');
	do_action('cfar-admin-js');
	die();
}

function cfar_css() {
	header('Content-type: text/css');
	echo file_get_contents(CFAR_DIR.'css/content.css');
	do_action('cfar-css');
	die();
}

function cfar_js() {
	header('Content-type: text/javascript');
	echo file_get_contents(CFAR_DIR.'js/behavior.js');
	do_action('cfar-js');
	die();
}

function cfar_month_ajax_archive($month = 0, $year = 0) {
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	echo $cf_archives->get_month_content($month, $year);
}

function cfar_week_ajax_archive($week = 0, $year = 0) {
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	echo $cf_archives->get_week_content($week, $year);
}


## Admin Functions

function cfar_admin_menu() {
	add_options_page(
		__('CF Archives', 'cfar'),
		__('CF Archives', 'cfar'),
		10,
		'cf-archives',
		'cfar_options'
	);
}
add_action('admin_menu', 'cfar_admin_menu');

function cfar_options() {
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	?>
	<div class="wrap">
		<?php echo screen_icon().'<h2>'.__('CF Archives', 'cfar').'</h2>'; ?>
		<?php
		// pp($cf_archives->get_settings());
		// echo 'count: '.$cf_archives->month_posts(12, 2008).'<br />';
		// $cf_archives->display(true);
		
		$args = array(
			'display_month_content' => false, 
		// 	// 'exclude_year_months' => array(
		// 	// 	2009 => array(
		// 	// 		1,2,3,4,5,6,7,8,9,10,11,12
		// 	// 	), 
		// 	// 	2008 => array(
		// 	// 		1,2,3,4,5,6,7,8,9,10,11,12
		// 	// 	), 
		// 	// 	2007 => array(
		// 	// 		1,2,3,4,5,6,7,8,9,10,11,12
		// 	// 	)
		// 	// )
			'exclude_years' => array(
				2009,2008,2007
			),
			'exclude_categories' => array(
				822
			)
		);
		
		echo $cf_archives->display(false, $args);
		/*
		?>
		<p class="submit" style="border-top: none;">
			<input type="submit" name="submit" value="<?php _e('Rebuild Archive', 'cfar'); ?>" id="cfar-rebuild-button" class="button-primary" />
			<span id="cfar-rebuild-status" class="cfar-rebuild-status updated"><img src="<?php echo CFAR_DIR_URL; ?>images/ajax-loader.gif" border="0" />Stuff here&hellip;</span>
		</p>
		*/ ?>
	</div>
	<div id="cfar-ajax-spinner" style="display:none;">
		<img src="<?php echo CFAR_DIR_URL; ?>images/ajax-loader.gif" border="0" />
	</div>
	<?php
}

function cfar_rebuild_archive() {
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	$result = $cf_archives->rebuild();
	if ($result) {
		echo 'complete';
		return;
	}
	echo 'error';
	return;
}







?>