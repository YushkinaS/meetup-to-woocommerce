<?php

add_action( 'woocommerce_before_shop_loop_item_title', 'mtw_show_event_time', 15 );
function mtw_show_event_time() {
    global $product;
    $post_id = $product->get_id( );
    $time = get_post_meta ( $post_id, 'event_time', true );
    ?>
    <span class="event_time"><?php echo date ('M j, Y · H:i',$time); ?></span>
	<?php
}


add_filter('woocommerce_get_catalog_ordering_args', 'mtw_get_catalog_ordering_args');
function mtw_get_catalog_ordering_args( $args ) {
    if ( is_tax('product_cat','past-events') ) {
        $args['order'] = 'DESC';
        $args['meta_key'] = 'event_time';
        $args['orderby'] = 'meta_value_num';
        return $args;
    }
    if ( is_tax('product_cat','upcoming-events') ) {
        $args['order'] = 'ASC';
        $args['meta_key'] = 'event_time';
        $args['orderby'] = 'meta_value_num';
        return $args;
    }
}


add_filter( 'woocommerce_get_availability', 'mtw_get_availability', 1, 2);
function mtw_get_availability( $availability, $_product ) {
// Change Out of Stock Text
    if ( ! $_product->is_in_stock() ) {
        $availability['availability'] = __('This is unavailable event', 'woocommerce');
    }
    return $availability;
}
