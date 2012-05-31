<?php


$wpserver = get_bloginfo('url');
if(strpos($_SERVER['SERVER_NAME'],'www.') !== false && strpos($wpserver,'www.') === false) {
	$wpserver = str_replace('http://','http://www.',$wpserver);
}

$script_data = array(
	'wpserver' => trailingslashit($wpserver)
);

wp_enqueue_script('jquery');

wp_enqueue_script('admin.js');
wp_localize_script('admin.js', 'cfar', $script_data);

wp_enqueue_script('head.js');
wp_localize_script('head.js', 'cfar', $script_data);