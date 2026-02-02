<?php
if (!function_exists('generate_products')) {
    function generate_products($product_title, $product_description, $product_price) {
        // Check if the product with the given title already exists
        $product_id = product_exists_by_title($product_title);

        //check product modified today
        // $product_object = get_post($product_id);
        // if ($product_object) {
        //     $modified_date = date( 'Y-m-d', strtotime( $product_object->post_modified ) );
        //     $today = current_time( 'Y-m-d' ); // Uses WP timezone setting
        //     $is_updated_today = ( $modified_date === $today );
        //     if($is_updated_today){ return; }
        // }

        if ($product_id) {
            $product = wc_get_product($product_id);
            if (!$product) return 0; // Fail-safe
            $product->set_name($product_title);
            $product->set_description($product_description);
            $product->set_regular_price($product_price);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product_id = $product->save();
        } else {
            $product = new WC_Product_Simple();
            $product->set_name($product_title);
            $product->set_description($product_description);
            $product->set_regular_price($product_price);
            $product->set_status('publish');
            $product->set_catalog_visibility('visible');
            $product_id = $product->save();
        }
        //set custom product post type
        wp_set_object_terms($product_id, 'outdoor', 'product_type');
        //Ensure product ID is valid before setting terms
        
        return $product_id;
    }
}

//get product heading by name
if(!function_exists('get_heading_by_name')){
    function get_heading_by_name($fields, $target_name) {
        foreach ($fields as $field) {
            if (!empty($field['name']) && $field['name'] === $target_name) {
                return $field['heading'];
            }
        }
        return null;
    }
}

//filter only field_ data
if(!function_exists('get_custom_fields_data')){
    function get_custom_fields_data($array){
        $field_data = array_filter($array, function ($key) {
            return strpos($key, 'field_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        return $field_data;
    }
}
// Helper function to check if a product with the given title exists
if(!function_exists('product_exists_by_title')){
    function product_exists_by_title($title) {
        global $wpdb;
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'product' AND post_status = 'publish' 
                LIMIT 1",
                $title
            )
        );
        return !empty($product_id) ? $product_id : false;
    }
}