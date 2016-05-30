<?php

function mtw_load_events() {

    $meetup = new Meetup(array(
        'key' => get_option('mtw_settings')['mtw_meetup_api_key']
        ));
    

	$response = $meetup->getEvents(array(
			'status' => 'upcoming,past',
			'fields' => 'featured',
			'group_urlname' => get_option('mtw_settings')['mtw_meetup_group_urlname']
			));
	$result = mtw_process_response_page($response);
	$images_for_thumbnails = $result['images_for_thumbnails'];

    while( ($response = $meetup->getNext($response)) ) {
		$result = mtw_process_response_page($response); 
		$images_for_thumbnails = array_merge( $images_for_thumbnails,$result['images_for_thumbnails'] );
	}

	$old_images = get_option('mtw_product_thumbnails');
	if ( is_array( $old_images ) ) {
		$images_for_thumbnails = array_merge( $images_for_thumbnails, $old_images );
	}

	update_option('mtw_product_thumbnails',$images_for_thumbnails);

}


function mtw_process_response_page($response) {
    $post_event = mtw_post_to_event();
    $images = array();

    $past_product_cat = get_term_by( 'slug', 'past-events','product_cat' )->term_id;
    $upcoming_product_cat = get_term_by( 'slug', 'upcoming-events','product_cat' )->term_id;
    
    foreach ($response->results as $event) {

            if (array_key_exists($event->id,$post_event)) {
				
				//here should be some check for updates 
				if ( $post_event[$event->id]->status == $upcoming_product_cat ) {
					$post_id = $post_event[$event->id]->post_id;
					if ( $event->status == 'past' ) {
						wp_set_object_terms($post_id,'past-events','product_cat');
						//echo 'updated '.$post_id .'<br>';
						update_post_meta($post_id ,'_stock_status','outofstock');
						delete_post_meta($post_id ,'_price');
						delete_post_meta($post_id ,'_regular_price');
						delete_post_meta($post_id ,'_featured');
					}
					elseif ( $event->time/1000 < current_time('timestamp') ) {
						update_post_meta($post_id ,'_visibility','');
					}
				}
            }
            else {
				if ('past' == $event->status) {
					$post_id = mtw_create_product_for_past_event($event);
				}
				elseif ('upcoming' == $event->status) {
					$post_id = mtw_create_product_for_upcoming_event($event);
				}

				if ($post_id) {
					preg_match('/(?<=<img src=")[^"]+/',$event->description,$image_links);
					$images[] = (object) array('post_id'=>$post_id,'image_links'=> $image_links);
				}
							



            }
    }
    $returned_data = array();
    $returned_data['images_for_thumbnails'] = $images;
    return $returned_data;
}


function mtw_create_product_for_upcoming_event($event) {
	//make excerpt
	$end = 500;
	if (strlen($event->description) < $end) {
		$end = strlen($event->description);
	}
	$post_excerpt = strip_tags(mb_substr($event->description, 0, $end)) . '... ';
                 
	$my_post = array(
		'post_title'    => $event->name,
		'post_content'  => $event->description,
		'post_status'   => 'publish',
		'post_type'     => 'product',
		'post_excerpt'  => $post_excerpt,
		'meta_input'    => array(
			'event_id'      => $event->id,
			'event_time'    => $event->time / 1000,
			'_visibility'   => 'visible',
			'_stock_status' => 'instock',
			'_regular_price'=> $event->fee->amount,
			'_price'        => $event->fee->amount,
			'meetup_url'    => $event->event_url,
			'_featured'     => ($event->featured == 'true') ? 'yes':'no'
		
		)
	);
	
	$post_id = wp_insert_post( $my_post);
	wp_set_object_terms($post_id,'upcoming-events','product_cat');

	//удалить нулевые цены
	if (! isset($event->fee->amount)) {
		delete_post_meta($post_id,'_regular_price');
		delete_post_meta($post_id,'_price');
	}
	
	return $post_id;
}


function mtw_create_product_for_past_event($event) {
	//make excerpt
	$end = 500;
	if (strlen($event->description) < $end) {
		$end = strlen($event->description);
	}
	$post_excerpt = strip_tags(mb_substr($event->description, 0, $end)) . '... ';
	
	//create product
	$my_post = array(
		'post_title'    => $event->name,
		'post_content'  => $event->description,
		'post_status'   => 'publish',
		'post_type'     => 'product',
		'post_excerpt'  => $post_excerpt,
		'meta_input'    => array(
			'event_id'      => $event->id,
			'event_time'    => $event->time / 1000,
			'_visibility'   => 'visible',
			'_stock_status' => 'outofstock',
			'_regular_price'=> '',
			'_price'        => '',
			'meetup_url'    => $event->event_url,
			)
		);
	$post_id = wp_insert_post( $my_post);
	wp_set_object_terms($post_id,'past-events','product_cat');
	
	return $post_id;
}


function mtw_post_to_event() {
    global $wpdb;
    $past_product_cat = get_term_by( 'slug', 'past-events','product_cat' )->term_id;
    $upcoming_product_cat = get_term_by( 'slug', 'upcoming-events','product_cat' )->term_id;
    $res = $wpdb->get_results($wpdb->prepare("SELECT 
			posts.ID as post_id,
			postmeta.meta_value as event_id,
			term_relationships.term_taxonomy_id as term
			FROM wp_posts posts
			INNER JOIN wp_postmeta postmeta
				ON posts.ID = postmeta.post_id
				AND postmeta.meta_key LIKE 'event_id'
			LEFT JOIN wp_term_relationships term_relationships
				ON posts.ID = term_relationships.object_id
				AND term_relationships.term_taxonomy_id IN (%d,%d)",
				$past_product_cat,$upcoming_product_cat));
    $post_ids = $wpdb->get_col(null,0);
    $event_ids = $wpdb->get_col(null,1);   
    $statuses = $wpdb->get_col(null,2);
    $post_id_status = array_combine($post_ids,$statuses);
    $new_post_id_status = array();
    foreach ($post_id_status as $post_id=>$status) {
		$new_post_id_status[] = (object) array('post_id'=>$post_id,'status'=>$status);
	}
    $post_event = array_combine($event_ids,$new_post_id_status);
    return $post_event; 
}


function mtw_set_thumbnails() {
	$images_for_thumbnails = get_option('mtw_product_thumbnails');
	$thumbnailed=array();
	foreach ($images_for_thumbnails as $index=>$image) {
		mtw_set_product_thumbnail($image->image_links,$image->post_id);
		$thumbnailed[] = $index;
	}
	foreach ($thumbnailed as $index) {
		unset( $images_for_thumbnails[$index] );
	}
	
	update_option('mtw_product_thumbnails',$images_for_thumbnails);

}


function mtw_set_product_thumbnail($image_links,$post_id) {

	add_action('add_attachment','mtw_save_attachment_id');
	foreach ($image_links as $image_link) {
		media_sideload_image($image_link,$post_id,'product_'.$post_id.'_image');
	}
	remove_action('add_attachment','mtw_save_attachment_id');
}


function mtw_save_attachment_id($att_id) {
    // the post this was sideloaded into is the attachments parent!
    $p = get_post($att_id);
    update_post_meta($p->post_parent,'_thumbnail_id',$att_id);
}


?>
