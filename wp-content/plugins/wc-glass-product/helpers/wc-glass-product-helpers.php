<?php
/**
 * WC Glass Product Helper Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!function_exists('wc_glass_get_attribute_terms')) {
    function get_taxonomy_title_by_slug( $taxonomy_slug ) {
        // Ensure the function is called after taxonomies are registered.
        if ( ! taxonomy_exists( $taxonomy_slug ) ) {
            return '';
        }

        // Get the full taxonomy object.
        $taxonomy = get_taxonomy( $taxonomy_slug );

        // Check if the taxonomy object was found and has a label.
        if ( $taxonomy && isset( $taxonomy->labels->name ) ) {
            return $taxonomy->labels->name;
        }

        return '';
    }
}

if (!function_exists('wc_glass_get_attribute_terms')) {
    function wc_glass_get_attribute_terms($taxonomy_slug) {
        // Check if the taxonomy exists to prevent errors.
        if (!taxonomy_exists($taxonomy_slug)) {
            return [];
        }

        $args = array(
            'taxonomy'   => $taxonomy_slug,
            'hide_empty' => false, // Set to false to include attributes not assigned to a product yet.
        );

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return [];
        }

        return $terms;
    }

}

function get_product_attribute_terms_by_id($product_id, $taxonomy_slug) {
    // Check if the product ID is valid.
    if (empty($product_id)) {
        return [];
    }
    
    // Use the WooCommerce wrapper function to get terms.
    $terms = wc_get_product_terms($product_id, $taxonomy_slug);

    if (is_wp_error($terms)) {
        return []; // Return an empty array on error.
    }

    return $terms;
}

if (!function_exists('get_term_image_url')) {
    function get_term_image_url($term_id) {
        // Replace 'glass_image' with the meta key you use to store the image URL.
        $image_id = get_term_meta($term_id, 'glass_image', true);
        
        if ($image_id) {
            return wp_get_attachment_url($image_id);
        }
        return '';
    }
}

if (!function_exists('get_glass_products')) {
    function get_glass_products($frame_width) {

        // Fetch all published glass products
        $glass_products = get_posts([
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'glass_product',
                ],
            ],
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key'     => '_glass_width',
                    'value'   => '',
                    'compare' => '!=',
                ],
                [
                    'key'     => '_glass_quantity',
                    'value'   => '',
                    'compare' => '!=',
                ],
            ],
        ]);

        $closest_product_id = null;
        $closest_diff       = PHP_FLOAT_MAX;
        $closest_overlap    = 0;

        // Loop through all products
        foreach ($glass_products as $product_post) {
            $product       = wc_get_product($product_post->ID);
            $glass_width   = floatval($product->get_meta('_glass_width'));
            $no_of_panels  = intval($product->get_meta('_glass_quantity'));

            if ($glass_width <= 0 || $no_of_panels <= 0) {
                continue; // skip invalid data
            }

            // Step 1: Calculate panel total width (no overlap)
            $panel_width = $no_of_panels * $glass_width ;

            // Step 2: Calculate total overlap (basic formula)
            $total_overlap = ($no_of_panels * $glass_width) - $frame_width;
            
            // Step 3: Per overlap width (if >1 panels)
            $per_overlap = ($no_of_panels > 1) ? $total_overlap / ($no_of_panels - 1) : 0;

            // Step 4: Calculate adjusted total width (max breedte)
            // Assuming each overlap reduces 2mm width per join (custom rule)
            $overlap_width = ($no_of_panels - 1) * 2;
            $total_width   = $panel_width - $overlap_width;

            // Step 5: Find product whose total width is >= frame width and closest to it
            if ($total_width >= $frame_width) {
                $diff = $total_width - $frame_width; // only positive difference

                if ($diff < $closest_diff) {
                    $closest_diff       = $diff;
                    $closest_product_id = $product->get_id();
                    $closest_overlap    = $per_overlap;
                }
            }

        }

        if (!$closest_product_id) {
            return null; // no valid product found
        }
        
        return [
            'product_id'  => $closest_product_id,
            'overlapping' => $closest_overlap,
        ];
    }

}



/**
 * Helper: Get single value
 * Example: wc_glass_get_price(2, 820, 'handgreep');
 */
function wc_glass_get_price($rails, $width, $key) {
    return (float) get_option("wc_glass_{$rails}_{$width}_{$key}", 0);
}

/**
 * Get ALL prices for a rails + width group
 */
function wc_glass_get_prices($rails, $width) {

    $keys = [
        'price',
        'handgreep',
        'tochstrips',
        'u_profielen',
        'meenemers',
        'funderingsbalk',
        'verlengde'
    ];

    $data = [];
    foreach ($keys as $key) {
        $data[$key] = wc_glass_get_price($rails, $width, $key);
    }

    return $data;
}