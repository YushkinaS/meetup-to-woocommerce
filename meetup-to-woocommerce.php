<?php
/*
Plugin Name: meetup-to-woocommerce
*/

//http://wp-kama.ru/function/media_sideload_image
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
//http://wp-kama.ru/function/media_sideload_image

require_once('admin-view.php');
require_once('client-view.php');
require_once('meetup-import.php');
require_once('meetup.php');


add_action('mtw_hourly_task', 'mtw_cron_load_meetup_events');
register_activation_hook(__FILE__, 'mtw_activation');
register_deactivation_hook( __FILE__, 'mtw_deactivation');

function mtw_activation() {
	wp_clear_scheduled_hook( 'mtw_hourly_task' );
	wp_schedule_event( time(), 'hourly', 'mtw_hourly_task');
	
}

function mtw_cron_load_meetup_events() {
	mtw_load_events();
	mtw_set_thumbnails();
}

function mtw_deactivation() {
	wp_clear_scheduled_hook('mtw_hourly_task');
	delete_option('mtw_product_thumbnails');
}

?>
