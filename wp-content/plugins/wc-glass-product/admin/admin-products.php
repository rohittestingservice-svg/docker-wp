<?php
// glass product create
add_action('init', 'rb_create_glass_product_flag');
function rb_create_glass_product_flag() {

    if (!isset($_GET['create_glass'])) {
        return; // RUN ONLY WHEN TRIGGERED
    }

    // GLASS PRODUCT PRICE CONFIG
    $products = [
        2 => [
            ['width'=> 820, 'price' => 218],
            ['width'=> 900, 'price' => 218],
            ['width'=> 980, 'price' => 218],
            ['width'=> 1030, 'price' => 218],
        ],
        3 => [
            ['width'=> 820, 'price' => 327],
            ['width'=> 900, 'price' => 327],
            ['width'=> 980, 'price' => 327],
            ['width'=> 1030, 'price' => 327],
        ],
        4 => [
            ['width'=> 820, 'price' => 436],
            ['width'=> 900, 'price' => 436],
            ['width'=> 980, 'price' => 436],
            ['width'=> 1030, 'price' => 436],
        ],
        5 => [
            ['width'=> 820, 'price' => 545],
            ['width'=> 900, 'price' => 545],
            ['width'=> 980, 'price' => 545],
            ['width'=> 1030, 'price' => 545],
        ],
        6 => [
            ['width'=> 820, 'price' => 654],
            ['width'=> 900, 'price' => 654],
            ['width'=> 980, 'price' => 654],
            ['width'=> 1030, 'price' => 654],
        ],
    ];

    foreach ($products as $glass_quantity => $items) {

        foreach ($items as $item) {

            $width = $item['width'];
            $price = $item['price'];

            // Prevent duplicate product
            $existing = get_posts([
                'post_type'   => 'product',
                'numberposts' => 1,
                'meta_query'  => [
                    [
                        'key'   => '_glass_quantity',
                        'value' => $glass_quantity,
                    ],
                    [
                        'key'   => '_glass_width',
                        'value' => $width,
                    ]
                ]
            ]);

            if (!empty($existing)) {
                echo "Skipped (Duplicate Exists): {$glass_quantity} glass × {$width}mm<br>";
                continue;
            }

            // Product name
            $total_width = $glass_quantity * $width;
            $name = "{$glass_quantity}-Rail Glass Sliding Wall up to {$total_width}mm wide ({$glass_quantity} x {$width}mm glass)";

            // Create product
            $post_id = wp_insert_post([
                'post_title'   => $name,
                'post_type'    => 'product',
                'post_status'  => 'publish',
            ]);

            if (is_wp_error($post_id) || !$post_id) {
                echo "Failed creating: {$name}<br>";
                continue;
            }

            // Assign product type
            wp_set_object_terms($post_id, 'glass_product', 'product_type');

            // Initialize
            $product = new WC_Product_Glass($post_id);

            // Product main values
            $product->set_sku('GLASS-' . $post_id);
            $product->set_regular_price($price);
            $product->set_manage_stock(true);
            $product->set_stock_quantity(10);
            $product->set_catalog_visibility('visible');

            /**
             * Add attributes pa_soort-glas and pa_kleur
             */
            $tax_list = ['pa_soort-glas', 'pa_kleur'];
            $attributes = [];

            foreach ($tax_list as $taxonomy) {
                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]);

                if (empty($terms) || is_wp_error($terms)) continue;

                $term_ids = wp_list_pluck($terms, 'term_id');

                $attribute = new WC_Product_Attribute();
                $attribute->set_id(wc_attribute_taxonomy_id_by_name($taxonomy));
                $attribute->set_name($taxonomy);
                $attribute->set_options($term_ids);
                $attribute->set_visible(true);
                $attribute->set_variation(true);
                $attribute->set_position(0);

                $attributes[] = $attribute;
            }

            $product->set_attributes($attributes);
            $product->save();

            // Save meta to prevent duplicates later
            update_post_meta($post_id, '_glass_width', $width);
            update_post_meta($post_id, '_glass_quantity', $glass_quantity);

            echo "Created Product: {$name} (ID: {$post_id})<br>";
        }
    }

    wp_die("<br><br><strong>All Glass Products Created Successfully!</strong>");
}