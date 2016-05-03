<?php
function obrabotka_post_to_event() {
    global $wpdb;
    $res = $wpdb->get_results("SELECT * FROM `wp_postmeta` WHERE `meta_key` LIKE 'event_id'");
    $post_ids = $wpdb->get_col(null,1);
    $event_ids = $wpdb->get_col(null,3);   
    $post_event = array_combine($event_ids,$post_ids);
    return $post_event; 
}

function obrabotka_save_attachment_id($att_id) {
    // the post this was sideloaded into is the attachments parent!
    $p = get_post($att_id);
    update_post_meta($p->post_parent,'_thumbnail_id',$att_id);
}


function obrabotka_load_future_events() {
    
    require 'meetup.php';
    $meetup = new Meetup(array(
        'key' => 'meetup user api key'
        ));
    
    $response = $meetup->getEvents(array(
        'status'=>'upcoming',
        'fields' => 'featured',
        'group_urlname' => 'meetup group urlname',
        ));
        
    $post_event = obrabotka_post_to_event();
    
    foreach ($response->results as $event) {
            if (array_key_exists($event->id,$post_event)) {
              //  $post_id = $post_event[$event->id];
              //  echo 'already '.$post_id.'<br>';
            }
            else {
                $end = 500;
                if (strlen($event->description) < $end) {
                    $end = strlen($event->description);
                }
                $post_excerpt = strip_tags(substr($event->description, 0, $end)) . '... ';
                 
                //$fee = (is_null($event->fee->amount)) ? 0 : 
                
                $my_post = array(
    			  'post_title'    => $event->name,
    			  'post_content'  => $event->description,
    			  'post_status'   => 'publish',
    			  'post_type'     => 'product',
    			  'post_excerpt'  => $post_excerpt,
    			  'meta_input'    => array(
    			      'event_id'      => $event->id,
    			      'event_time'    => $event->time / 1000,
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
    			
    			//загрузить первую картинку
    			preg_match('/(?<=<img src=")[^"]+/',$event->description,$image_links);
    			
    			add_action('add_attachment','obrabotka_save_attachment_id');
    			foreach ($image_links as $image_link) {
    			    media_sideload_image($image_link,$post_id,'product_'.$post_id.'_image');
    			}
    			remove_action('add_attachment','obrabotka_save_attachment_id');

	
    			echo '<br>';
    			echo 'inserted '.$post_id;
            }
    }     
}

?>
