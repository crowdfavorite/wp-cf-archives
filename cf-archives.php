<?php
/*
Plugin Name: CF Archives 
Plugin URI: http://crowdfavorite.com 
Description: Advanced features for Archives. 
Version: 1.2b1
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// 	ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR', 'wp-content/plugins');
}
global $wpdb;
define('CF_ARCHIVETABLE', $wpdb->options);

load_plugin_textdomain('cf-archives');

if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'cf-archives.php')) {
	define('CFAR_FILE', trailingslashit(ABSPATH.PLUGINDIR).'cf-archives.php');
}
else if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'cf-archives/cf-archives.php')) {
	define('CFAR_FILE', trailingslashit(ABSPATH.PLUGINDIR).'cf-archives/cf-archives.php');
}

function cfar_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {
			case 'cfar_admin_js':
				cfar_admin_js();
				break;
			case 'cfar_head_js':
				cfar_head_js();
				break;
			case 'cfar_head_css':
				cfar_head_css();
				die();
				break;
			case 'cfar_admin_css':
				cfar_admin_css();
				die();
				break;
		}
	}
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {
			case 'cfar_update_settings':
				cfar_save_settings($_POST['cfar_settings']);
				wp_redirect(trailingslashit(get_bloginfo('wpurl')).'wp-admin/options-general.php?page=cf-archives.php&updated=true');
				die();
				break;
			case 'cfar_rebuild_archive':
				cfar_rebuild_archive();
				wp_redirect(trailingslashit(get_bloginfo('wpurl')).'wp-admin/options-general.php?page=cf-archives.php&cf_message=archive_rebuilt');
				die();
				break;
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
			case 'cfar_ajax_month_archive':
				$args = array();
				$year = (int) $_POST['cfar_year'];
				$month = (int) $_POST['cfar_month'];
				$args['year_show'] = $_POST['cfar_year_show'];
				$args['year_hide'] = $_POST['cfar_year_hide'];
				$args['month_show'] = $_POST['cfar_month_show'];
				$args['month_hide'] = $_POST['cfar_month_hide'];
				$args['post_show'] = $_POST['cfar_post_show'];
				$args['post_hide'] = $_POST['cfar_post_hide'];
				$args['category'] = $_POST['cfar_category'];
				$args['show_heads'] = $_POST['cfar_show_heads'];
				$args['add_div'] = $_POST['cfar_add_div'];
				$args['add_ul'] = $_POST['cfar_add_ul'];
				$args['print_month_content'] = $_POST['cfar_print_month_content'];
				cfar_month_archive($year,$month,$args);
				die();
				break;
		}
	}
}
add_action('init', 'cfar_request_handler');

wp_enqueue_script('jquery');
function cfar_admin_js() {
	header('Content-type: text/javascript');
?>
jQuery(function() {
		
	jQuery('#cfar_settings_form2').submit(function(){
		cfar_batch_rebuild_archives();
		return false;
	});

	function cfar_batch_rebuild_archives() {
		var batch_offset = 0;
		var batch_increment = 20;
		var finished = false;
		
		params = {'cfar_rebuild_indexes':'1',
				  'cfar_rebuild_offset':'0'
				 }
		cfar_update_status('Processing archives');
		
		// process posts
		while(!finished) {
			response = cfar_batch_request(batch_offset,batch_increment);
			if (!response.result && !response.finished) {
				cfar_update_status('Archive processing failed. Server said: ' + response.message);
				return;
			}
			else if (!response.result && response.finished) {
				cfar_update_status('Archive processing complete.');
				finished = true;
			}
			else if (response.result) {
				cfar_update_status(response.message);
				batch_offset = (batch_offset + batch_increment);
			}
		}
	}
	
	// make a request
	function cfar_batch_request(offset,increment) {
		var r = jQuery.ajax({type:'post',
								url:'index.php',
								dataType:'json',
								async:false,
								data:'cf_action=cfar_rebuild_archive_batch&cfar_batch_offset=' + offset + '&cfar_batch_increment=' + increment
							}).responseText;
		var j = eval( '(' + r + ')' );
		return j;
	}
	
	// handle the building of indexes
	function cfar_index_build_callback(response) {
		if (response.result) {
			cfar_update_status('Archive Rebuild Complete');
		}
		else {
			cfar_update_status('Failed to rebuild archives');
		}
	}
	
	// update status message
	function cfar_update_status(message) {
		if (!jQuery('#index-status').hasClass('updated')) {
			jQuery('#index-status').addClass('updated');
		}
		jQuery('#index-status p').html(message);
	}
	
});
<?php
	die();
}

function cfar_head_js() {
	$wpserver = get_bloginfo('url');
	if(strpos($_SERVER['SERVER_NAME'],'www.') !== false && strpos($wpserver,'www.') === false) {
		$wpserver = str_replace('http://','http://www.',$wpserver);
	}
	
	header('Content-type: text/javascript');
	?>
	function showContent(id) {
		jQuery('#content-'+id).slideDown();
		jQuery('#hide-'+id).attr('style','');
		jQuery('#show-'+id).attr('style','display:none;');
		return false;
	}
	function hideContent(id) {
		jQuery('#content-'+id).slideUp();
		jQuery('#hide-'+id).attr('style','display:none;');
		jQuery('#show-'+id).attr('style','');
		return false;
	}
	function showPreview(id) {
		jQuery('#post-'+id).slideDown();
		jQuery('#hide-'+id).attr('style','');
		jQuery('#show-'+id).attr('style','display:none;');
		return false;
	}
	function hidePreview(id) {
		jQuery('#post-'+id).slideUp();
		jQuery('#hide-'+id).attr('style','display:none;');
		jQuery('#show-'+id).attr('style','');
		return false;
	}
	function showMonth(year,month) {
		var category = jQuery("#cfar-category").html();
		if(category == '') {
			category = 0;
		}
		var addContent = jQuery("#content-"+year+"-"+month);
		var ajaxSpinner = '<div id="ajax-spinner"><img src="<?php echo trailingslashit($wpserver); ?>wp-content/plugins/cf-archives/images/ajax-loader.gif" border="0" /> <span class="ajax-loading"><?php _e('Loading...','cf-archives'); ?></span></div>';
		if(!addContent.hasClass("filled")) {
			addContent.append(ajaxSpinner);
			jQuery.post("<?php echo trailingslashit($wpserver); ?>", { cf_action: 'cfar_ajax_month_archive', cfar_year: year, cfar_month: month, cfar_show_heads: 'no', cfar_add_div: 'no', cfar_add_ul: 'show', cfar_print_month_content: 'show', cfar_category: category, cfar_show_author: 'yes' },function(data){
				jQuery('#ajax-spinner').remove();
				addContent.append(data).addClass('filled');
			});	
		}
	}
	<?php
	die();
}

function cfar_admin_css() {
	header('Content-type: text/css');
?>
<?php
	die();
}

function cfar_head_css() {
	header('Content-type: text/css');
?>
	.month-show {
		cursor:pointer;
		font-size:10px;
	}
	.month-post-show {
		cursor:pointer;
	}
	#ajax-spinner {
		text-align: center;
	}
<?php
	die();
}

function cfar_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?cf_action=cfar_admin_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('url')).'index.php?cf_action=cfar_admin_js"></script>';
}
add_action('admin_head', 'cfar_admin_head');

function cfar_wp_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?cf_action=cfar_head_css" />';
	echo '<script type="text/javascript" src="'.trailingslashit(get_bloginfo('url')).'index.php?cf_action=cfar_head_js"></script>';
}
add_action('wp_head','cfar_wp_head');

function cfar_rebuild_archive() {
	global $wpdb;
	$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'post'");
	foreach($posts as $post) {
		cfar_add_archive($post->ID);
	}
}

function cfar_rebuild_archive_batch($increment=0,$offset=0) {
	global $wpdb;
	if ($offset == 0) {
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '19%'");
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '20%'");
	}
	$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY ID ASC LIMIT ".$offset.",".$increment);
	foreach($posts as $post) {
		$add_result = cfar_add_archive($post->ID);
		if (!$add_result) {
			echo cf_json_encode(array('result'=>false,'finished'=>false,'message'=>'Failed to complete rebuild'));
			exit();
		}
	}
	$total_count = $increment+$offset;
	if ($total_count >= cfar_get_posts_count()) {
		echo cf_json_encode(array('result'=>false,'finished'=>true,'message'=>true));
	}
	else {
		$message = 'Processing archives ';
		for($i=0;$i<($offset/20);$i++) {
			$message .= ' . ';
		}
		echo cf_json_encode(array('result'=>true,'finished'=>false,'message'=>$message));
	}
	exit();
}

function cfar_get_posts_count() {
	global $wpdb;
	$posts = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish'");
	if (!count($posts)) { return false; }
	return count($posts);
}

function cfar_add_archive($post_id) {
	global $wpdb;
	$save_post = new WP_Query('p='.$post_id);
	$orig_post = $post;
	while($save_post->have_posts()) {
		$save_post->the_post();
		if ($save_post->post->post_type == 'revision' || $save_post->post->post_status == 'draft') { break; }
		$year = date('Y',strtotime($save_post->post->post_date));
		$month = date('m',strtotime($save_post->post->post_date));
		$month_string = date('M',strtotime($save_post->post->post_date));
		$archive_date = $year.'-'.$month;
		$new_post = true;
		
		$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE '".$archive_date."'");
		$start = maybe_unserialize($archives[0]->option_value);

		$excerpt = cfar_trim_excerpt($save_post->post->post_excerpt,$save_post->post->post_content);
		$category_list = array();
		$categories = get_the_category();
		foreach($categories as $category) {
			array_push($category_list, $category->cat_ID);
		}

		$insert = array($save_post->post->post_date.'--'.$save_post->post->ID => array('id' => $save_post->post->ID,'title' => $save_post->post->post_title,'author' => $save_post->post->post_author,'post_date' => $save_post->post->post_date,'excerpt' => $excerpt,'guid' => $save_post->post->guid,'categories' => $category_list));
		if (!is_array($start)) {
			$query = "INSERT INTO ".CF_ARCHIVETABLE." (`option_id` ,`blog_id` ,`option_name` ,`option_value` ,`autoload`)VALUES (NULL , '0', '".$archive_date."', '".$wpdb->escape(serialize($insert))."', 'no');";
			$wpdb->query($query);
		}
		else {
			foreach($start as $key => $item) {
				foreach($item as $item_key => $post_info) {
					$check_key = $save_post->post->ID;
					if ($post_info == $check_key) {
						unset($start[$key]);
						$new_post = false;
						break;
					}
				}
			}
			$result = array_merge($start,$insert);
			ksort($result);
			$query = "UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($result))."' WHERE option_name = '".$archive_date."'";
			$wpdb->query($query);
		}
		$archives_year_list = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
		$year_list = maybe_unserialize($archives_year_list[0]->option_value);
		if (!is_array($year_list)) {
			$yearcheck = $year.'_';
			$new_year_list = array($yearcheck => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0));
			$wpdb->query("INSERT INTO ".CF_ARCHIVETABLE." (option_id,blog_id,option_name,option_value,autoload)VALUES (NULL,'0','year_list','".$wpdb->escape($new_year_list)."','no');");
			foreach($new_year_list[$yearcheck] as $key => $list_month) {
				if ($key == $month) {
					$new_year_list[$yearcheck][$key] = $new_year_list[$yearcheck][$key] + 1;
				}
			}
			$wpdb->query("UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($new_year_list))."' WHERE option_name = 'year_list'");
		}
		else {
			if ($new_post) {
				$yearcheck = $year.'_';
				if (is_array($year_list[$yearcheck])) {
					foreach($year_list[$yearcheck] as $key => $list_month) {
						if ($key == $month) {
							$year_list[$yearcheck][$key] = $year_list[$yearcheck][$key] + 1;
						}
					}
					$wpdb->query("UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($year_list))."' WHERE option_name = 'year_list'");
				}
				else {
					$new_year_list = array($yearcheck => array(1=>0,2=>0,3=>0,4=>0,5=>0,6=>0,7=>0,8=>0,9=>0,10=>0,11=>0,12=>0));
					foreach($new_year_list[$yearcheck] as $key => $list_month) {
						if ($key == $month) {
							$new_year_list[$yearcheck][$key] = $new_year_list[$yearcheck][$key] + 1;
						}
					}
					$insert_year_list = array_merge($year_list,$new_year_list);
					krsort($insert_year_list);
					$wpdb->query("UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($insert_year_list))."' WHERE option_name = 'year_list'");
				}
			}
		}
	}
	$post = $orig_post;
	return true;
}
add_action('save_post', 'cfar_add_archive');

function cfar_remove_archive($post_id) {
	global $wpdb;
	$save_post = new WP_Query('p='.$post_id);
	$orig_post = $post;
	while($save_post->have_posts()) {
		$save_post->the_post();
		if ($save_post->post->post_type == 'revision' || $save_post->post->post_status == 'draft') { break; }
		$year = date('Y',strtotime($save_post->post->post_date));
		$month = date('m',strtotime($save_post->post->post_date));
		$month_string = date('M',strtotime($save_post->post->post_date));
		$archive_date = $year.'-'.$month;
		$new_post = true;

		$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE '".$archive_date."'");
		$start = maybe_unserialize($archives[0]->option_value);
		unset($start[$save_post->post->post_date.'--'.$save_post->post->ID]);
		$wpdb->query("UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($start))."' WHERE option_name = '".$archive_date."'");

		$archives_year_list = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
		$year_list = maybe_unserialize($archives_year_list[0]->option_value);
		$yearcheck = $year.'_';
		if (is_array($year_list[$yearcheck])) {
			foreach($year_list[$yearcheck] as $key => $list_month) {
				if ($key == $month) {
					$year_list[$yearcheck][$key] = $year_list[$yearcheck][$key] - 1;
				}
			}
			$wpdb->query("UPDATE ".CF_ARCHIVETABLE." SET option_value = '".$wpdb->escape(serialize($year_list))."' WHERE option_name = 'year_list'");
		}
	}
	$post = $orig_post;
	return true;
}
add_action('delete_post', 'cfar_remove_archive');

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
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br/></div><h2>'.__('CF Archives', 'cf-archives').'</h2>
		<form id="cfar_settings_form" name="cfar_settings_form" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
			<input type="hidden" name="cf_action" value="cfar_update_settings" />
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
	</div>
	');
}

function cfar_save_settings($settings) {
	if (!current_user_can('manage_options')) {
		return;
	}
	update_option('cf_archives', array('excerpt' => $settings['excerpt'],'showyear' => $settings['showyear'],'yearhide' => $settings['yearhide']));
}

function cfar_trim_excerpt($excerpt,$content,$length = 250) {
	if ($excerpt != '') { return $excerpt; }
	if ($excerpt == '') { $excerpt = strip_tags($content); }
	$excerpt = apply_filters('the_content', $excerpt);
	$excerpt = strip_shortcodes($excerpt);
	$excerpt = str_replace(']]>', ']]&gt;', $excerpt);
	$excerpt = str_replace('[/caption]','', $excerpt);
	$excerpt = strip_tags($excerpt);
	if (strlen($excerpt) > $length) {
	  $excerpt = substr($excerpt, 0, $length);
	  $excerpt = substr($excerpt, 0, strrpos($excerpt, ' ')).'&hellip; ';
	}
	return $excerpt;
}

function cfar_get_head_list($args=null) {
	global $wpdb;
	
	$defaults = array('before'=>'<ul>','after'=>'</ul>','before_year'=>'<li>','after_year'=>'</li>','before_monthlist'=>'<ul>','after_monthlist'=>'</ul>','before_month'=>'<li>','after_month'=>'</li>');
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	
	$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
	$yearlist = maybe_unserialize($archives[0]->option_value);
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
		, 'category' => ''
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
	$yearlist = maybe_unserialize($archives[0]->option_value);

	$return .= $before;
	foreach($yearlist as $year => $months) {
		$yearoutput = str_replace('_','',$year);
		$return .= $before_year.'<a class="year-link" href="#_'.$yearoutput.'">'.$yearoutput.'</a>'.$after_year.$before_monthlist;
		foreach($months as $month => $count) {
			$return .= $before_month;
			$month_name = date('M', mktime(0,0,0,$month,1,$yearoutput));
			if($category != 0) {
				$count = 0;
				$post_archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE '".$yearoutput."-".date('m', mktime(0,0,0,$month,1,$yearoutput))."' ORDER BY option_name DESC");
				$posts = maybe_unserialize($post_archives[0]->option_value);
				if (is_array($posts)) {
					foreach($posts as $post) {
						if (is_array($post['categories']) && in_array($category,$post['categories'])) {
							$count++;
						}
					}
				}
			}
			if ($count > 0) {
				$return .= '<a class="month-link" href="#_'.$yearoutput.'-'.$month.'">'.$month_name.'</a>';
			}
			else {
				$return .= '<span class="month-nolink">'.$month_name.'</span>';
			}
			$return .= $after_month;
		}
		$return .= $after_monthlist.$after_year;
	}
	$return .= $after;
	return $return;
}

function cfar_archive_list($args = null) {
	global $wpdb;
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
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	
	$years = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
	$yearlist = maybe_unserialize($years[0]->option_value);
	if($category != 0) {
		print('<span id="cfar-category" style="display:none;">'.$category.'</span>');
	}
	foreach($yearlist as $year => $months) {
		$yearoutput = str_replace('_','',$year);
		cfar_year_archive($yearoutput,$args);
	}
}

function cfar_year_archive($yearinput='',$args = null) {
	global $wpdb;
	$settings = maybe_unserialize(get_option('cf_archives'));
	$defaults = array('show_year_header' => ''
				);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	
	if ($show_year_header != '') {
		$settings['showyear'] = $show_year_header;
	}
	
	if ($yearinput != '') {
		$years = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
		$yearlist = maybe_unserialize($years[0]->option_value);
		$print = '';
		foreach($yearlist as $year => $months) {
			$yearcount = 0;
			$yearoutput = str_replace('_','',$year);
			if ($yearoutput == $yearinput ) {
				krsort($months);
				foreach($months as $month => $count) {
					if ($count > 0) {
						$print .= cfar_month_get_archive($yearoutput,date('m', mktime(0,0,0,$month,1,$yearoutput)),$args);
						$yearcount++;
					}
				}
			}
			if ($yearcount > 0) {
				if (htmlspecialchars($settings['showyear']) == 'yes') {
					print('<h2 class="yearhead" id="_'.$yearoutput.'">'.$yearoutput.'</h2>');
					print($print);
				}
				else {
					print('<div id="_'.$yearoutput.'">'.$print.'</div>');
				}
			}
		}
	}
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
	);
	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );
	
	if ($year != '' && $month != '') {
		$return = '';
		$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE '".$year."-".$month."' ORDER BY option_name DESC");
		$posts = maybe_unserialize($archives[0]->option_value);
		$content = '';
		$settings = maybe_unserialize(get_option('cf_archives'));
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
			$showyear = '<span class="month-show" id="show-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'" onClick="showContent(\''.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'\');"> | '.$month_show_text.'</span>';
			$showyear .= '<span class="month-show" id="hide-'.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'" onClick="hideContent(\''.$year.'-'.date('n', mktime(0,0,0,$month,1,$year)).'\')" style="display:none;"> | '.$month_hide_text.'</span>';
			$hidemonth = ' style="display: none;"';
		}
		else {
			$showyear = '';
		}
		$get_posts = cfar_get_month_posts($year,$month,$args);
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

	$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE '".$year."-".$month."' ORDER BY option_name DESC");
	$posts = maybe_unserialize($archives[0]->option_value);
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
				$showexcerpt = '<span class="month-post-show" id="show-'.$post['id'].'" onClick="showPreview(\''.$post['id'].'\')" style=""> | '.$post_show_text.'</span>';
				$showexcerpt .= '<span class="month-post-show" id="hide-'.$post['id'].'" onClick="hidePreview(\''.$post['id'].'\')" style="display: none"> | '.$post_hide_text.'</span>';
			}
			else {
				$showexcerpt = '';
			}
			if ($category != 0) {
				if (is_array($post['categories']) && in_array($category,$post['categories'])) {
					if ($print_month_content == 'show') {
						$author_info = get_userdata($post['author']);
						$content .= '<li>';
						$content .= '<a href="'.get_permalink($post['id']).'">'.$post['title'].'</a>'.' | '.date('M j, Y',strtotime($post['post_date']));
						if ($show_author == 'yes') {
							$content .= ' | '.__('By: ','cf-archives').$author_info->display_name;
						}
						$content .= $showexcerpt;
						if (htmlspecialchars($settings['excerpt']) == 'yes') {
							$content .= '<br /><div id="post-'.$post['id'].'" class="postexcerpt" style="display: none;">'.$post['excerpt'].'</div>';
						}
						$content .= '</li>';
						$category_ID = $post['categories'];
					}
					$post_count++;
				}
			}
			else {
				if ($print_month_content == 'show') {
					$author_info = get_userdata($post['author']);
					$content .= '<li>';
					$content .= '<a href="'.get_permalink($post['id']).'">'.$post['title'].'</a>'.' | '.date('M j, Y',strtotime($post['post_date']));
					if ($show_author == 'yes') {
						$content .= ' | '.__('By: ','wp-archives').$author_info->display_name;
					}
					$content .= $showexcerpt;
					if (htmlspecialchars($settings['excerpt']) == 'yes') {
						$content .= '<br /><div id="post-'.$post['id'].'" class="postexcerpt" style="display: none;">'.$post['excerpt'].'</div>';
					}
					$content .= '</li>';
				}
				$post_count++;
			}
		}
	}
	
	$return = array('content' => $content, 'count' => $post_count);
	
	return $return;
}

function cfar_widget($args) {
	global $wpdb;	
	extract($args);
	$options = get_option('cfar_widget');
	$title = empty($options['title']) ? __('WP Archives','cf-archives') : apply_filters('widget_title', $options['title']);
	echo $before_widget.$before_title.$title.$after_title;
	
	$archives = $wpdb->get_results("SELECT option_value FROM ".CF_ARCHIVETABLE." WHERE option_name LIKE 'year_list'");
	$yearlist = maybe_unserialize($archives[0]->option_value);
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
	}
	echo $after_widget;
}

function cfar_widget_control() {
	global $wpdb;
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
	$pages = $wpdb->get_results("SELECT ID,post_title,post_status,post_type FROM $wpdb->posts WHERE post_status='publish' AND post_type='page' ORDER BY post_date ASC");
	?>
		<p>
			<label for="title"><?php _e('Title:','cf-archives'); ?></label><input class="widefat" id="title" name="title" type="text" value="<?php print(htmlspecialchars($title)); ?>" />
		</p>
		<p>
			<label for="cfar-archive-id"><?php _e('Archive Page:','cf-archives'); ?></label>
			<br />
			<select id="cfar-archive-id" name="cfar-archive-id" class="widefat" style="width: 230px;">
				<?php
				foreach($pages as $page) {
					if ($cfar_archive_id == $page->ID) {
						$selected = ' selected=selected';
					}
					else {
						$selected = '';
					}
					?>
					<option value="<?php print(htmlspecialchars($page->ID)); ?>" <?php print($selected); ?>><?php print(htmlspecialchars($page->post_title)); ?></option>
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
}

function cfar_widget_init() {
	$widget_ops = array('classname' => 'widget_cfar_archive', 'description' => __('Widget for linking to the custom archives','cf-archives'));
	wp_register_sidebar_widget('cf-archives',__('WP Archives','cf-archives'),'cfar_widget',$widget_ops);
	wp_register_widget_control('cf-archives',__('WP Archives','cf-archives'),'cfar_widget_control');
}
add_action('init','cfar_widget_init');


?>