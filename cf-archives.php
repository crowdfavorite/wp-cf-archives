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

## General Functions

load_plugin_textdomain('cfar');

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
			case 'cfar_month_archive':
				cfar_month_archive();
				break;
		}
	}
	
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	
}
add_action('init', 'cfar_request_handler');


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
		// echo 'count: '.$cf_archives->month_posts(12, 2008).'<br />';
		// echo $cf_archives->get_month_html(12, 2008);
		// pp($cf_archives->get_settings());
		?>
		<p class="submit" style="border-top: none;">
			<input type="submit" name="submit" value="<?php _e('Rebuild Archive', 'cfar'); ?>" id="cfar-rebuild-button" class="button-primary" />
			<span id="cfar-rebuild-status" class="cfar-rebuild-status updated"><img src="<?php echo CFAR_DIR_URL; ?>images/ajax-loader.gif" border="0" />Stuff here&hellip;</span>
		</p>
	</div>
	<?php
}

function cfar_rebuild_archive() {
	global $cf_archives;
	if (class_exists('CF_Archives') && !is_a('CF_Archives', $cf_archives)) {
		$cf_archives = new CF_Archives();
	}
	$result = $cf_archives->rebuild();
	// pp($)
	// if () {
	// 	echo 'complete';
	// 	return;
	// }
	// echo 'error';
	// return;
}









?>