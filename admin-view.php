<?php
/*
* Plugin Page
*/

add_action('admin_menu', 'mtw_admin_menu' );
function mtw_admin_menu() {
	add_menu_page( 'MTW Edit Panel', 'MTW Edit Panel', 'manage_woocommerce', 'mtw_edit_panel', 'mtw_edit_panel_content', 'dashicons-calendar'); 
}


function mtw_edit_panel_content() {
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title() ?></h2>
		
		<form method="post" action="">
			<input type="hidden" name="load_meetup_events" value='1'>
			<input type="submit" value="Load meetup events" class="button button-primary">
		</form>
		<?php
			if (isset($_POST["load_meetup_events"])) {
					mtw_load_events();
					mtw_set_thumbnails();
			}
		?>

		<form action="options.php" method="POST">
			<?php
				settings_fields( 'mtw_option_group' );
				do_settings_sections( 'mtw_edit_panel' );
				submit_button();
			?>
		</form>
	</div>
	<?php
}


/*
* Settings Fields
*/

add_action('admin_init', 'mtw_register_setting');
function mtw_register_setting() {
	$page = 'mtw_edit_panel';
	

	register_setting( 'mtw_option_group', 'mtw_settings', 'mtw_settings_sanitize_callback' );

	add_settings_section( 'mtw_settings_section', 'Settings', '', $page );

	add_settings_field('mtw_meetup_api_key', 'Meetup API Key', 'mtw_meetup_api_key_callback', $page, 'mtw_settings_section' );
	add_settings_field('mtw_meetup_group_urlname', 'Meetup Group URL Name', 'mtw_meetup_group_urlname_callback', $page, 'mtw_settings_section' );
	//add_settings_field('mtw_meetup_load_mode', 'Load Mode', 'mtw_meetup_load_mode_callback', $page, 'mtw_settings_section' );
}


function mtw_settings_sanitize_callback( $options ) {

	return $options;
}


function mtw_meetup_api_key_callback() {
	$val = get_option('mtw_settings')['mtw_meetup_api_key'];
	?>
	<input type="password" name="mtw_settings[mtw_meetup_api_key]" value="<?php echo $val; ?>" class="form-control" />
	<?php
}


function mtw_meetup_group_urlname_callback() {
	$val = get_option('mtw_settings')['mtw_meetup_group_urlname'];
	?>
	<input type="text" name="mtw_settings[mtw_meetup_group_urlname]" value="<?php echo $val; ?>" class="form-control" />
	<?php
}


function mtw_meetup_load_mode_callback() {
	$selected = get_option('mtw_settings')['mtw_meetup_load_mode'];
	?>
	<select class="form-control" id="sel1" name="mtw_settings[mtw_meetup_load_mode]">
		<option value="once" <?php echo 'once' == $selected ? 'selected' : '' ?> >Load each event once</option>
		<option value="update" <?php echo 'update' == $selected ? 'selected' : '' ?> >Load all event's updates</option>
	</select>
	<?php
}


/*
 * Products List 
*/

add_filter('manage_product_posts_columns','mtw_add_product_posts_columns',100);
function mtw_add_product_posts_columns($cols) {
	unset($cols['sku']);
	unset($cols['date']);
	unset($cols['product_type']);
	$cols['event_time'] = 'Event time';
	$cols['meetup_url'] = 'Meetup URL';
	return $cols;
}


add_action('manage_posts_custom_column','mtw_custom_columns',10,2);
function mtw_custom_columns( $column, $post_id ) {
	
	if ( 'event_time' == $column ) {
		echo date ( 'M j, Y · H:i',get_post_meta ( $post_id, 'event_time', true ) );
	}
	
	if ( 'meetup_url' == $column ) {
		?>
		<a href="<?php echo get_post_meta ( $post_id, 'meetup_url', true ); ?>">#</a>
		<?php
	}
}


add_filter( 'manage_edit-product_sortable_columns', 'mtw_manage_sortable_columns');
function mtw_manage_sortable_columns( $cols )
{
	$cols['event_time'] = 'event_time';
	return $cols;
}

?>
