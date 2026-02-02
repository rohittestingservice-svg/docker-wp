<?php 
include_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/image-handler.php';
include_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/helpers.php';

add_action('init', 'create_wc_product');
function create_wc_product() {
    if(!isset($_GET['import_p'])){
        return;
    }
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        echo "WooCommerce is not activated!";
        return;
    }
    
    // Check if the WC_Product_Simple class exists
    if (!class_exists('WC_Product_Simple')) {
        echo "WC_Product_Simple class not found!";
        return;
    }
    
}

//import wp products
function import_product($product, $import_all = false){
    $product_title = $product['fulltitle'];
    $product_descriptiion = $product['content'];
    $product_price = 0;
    // generate products
    $wp_product_id = generate_products($product_title, $product_descriptiion, $product_price);
    update_post_meta($wp_product_id, 'lightspeed_product_id', $product['id']);
    
    // add or update product category
    $category_names = $product['categories'];
    if(!empty($category_names)){
        $category_ids = [];
        foreach ( $category_names as $category ) {
            $cat_name = trim($category['title']);
            $term = term_exists( $cat_name, 'product_cat' );
            //var_dump($term);die;
            if ( $term === 0 || $term === null ) {
                $term = wp_insert_term( $cat_name, 'product_cat' );
            }
            if ( ! is_wp_error( $term ) ) {
                $category_ids[] = (int) $term['term_id'];
            }
        }
        wp_set_object_terms( $wp_product_id, $category_ids, 'product_cat' );
    }

    // 
    if($import_all && !empty($product) && !empty($wp_product_id)){
        update_products_details($product, $wp_product_id);
    }
        
}

//add product details like variant custom data images etc
function update_products_details($product, $wp_product_id){
    // generate variants
    if (!empty($product['variants'])) {
        $formated_variant = [
            "name"     => 'field_' . uniqid(),
            "isRequired" => true
        ];
        // Sort the variants by key (id) in ascending order
        $v_fields = $product['variants'];

        uasort($v_fields, function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });

        $product['variants'] = $v_fields;
        $color_variants = [
            "name"       => 'field_' . uniqid(),
            "isRequired" => true,
            "type"       => "radio",
            "heading"    => "Kleur",
            "values"     => []
        ];

        $other_variants = [];
        foreach ($product['variants'] as $variant) {
            if (isset($variant['title']) && strpos($variant['title'], 'Kleur') !== false) {
                // Extract heading by removing "Kleur: "
                $value_arr = [
                    "name"      => 'field_' . uniqid(),
                    "heading"   => trim(str_replace("Kleur:", "", $variant['title'])),
                    "isDefault" => $variant['isDefault'],
                    "price"     => $variant['priceIncl'],
                    
                ];
                $color_variants['values'][] = $value_arr;
            } else {
                // Store non-color variant
                $other_variants[] = [
                    "type"      => "text",
                    "heading"   => $variant['title'],
                    "isDefault" => $variant['isDefault'],
                    "price"     => $variant['priceIncl']
                ];
            }
        }
        
        // Optionally push to final formatted array
        $formatted_variants = [];
        
        if (!empty($color_variants['values'])) {
            $formatted_variants[] = $color_variants;
        }
        
        $formatted_variants = array_merge($formatted_variants, $other_variants);
    }
    $json_product_variant = json_encode($formatted_variants);
    // Update product variant meta
    update_post_meta($wp_product_id, 'custom_variants_data', sanitize_text_field($json_product_variant));

    /*------------------------------formated fields data--------------------------------------*/
    $formated_field_group = [];
    if (!empty($product['fields'])) {
        //sort the fields by id ASC
        $fields = $product['fields']; 
        uasort($fields, function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });
        $product['fields'] = $fields;
        foreach ($product['fields'] as $key => $field) {
            $formated_field = []; // reset per field
            
            $formated_field['name'] = 'field_' . uniqid();
            $formated_field['heading'] = trim($field['title']);
            $formated_field['type'] = ($field['type'] != '') ? $field['type'] : 'textarea';
            $formated_field['isRequired'] = $field['isRequired'];
            
            if (!empty($field['values'])) {
                $formated_field['values'] = []; // initialize values array
                foreach ($field['values'] as $value) {
                    $value_data = []; // reset per value
                    $value_data['heading'] = $value['title']; // assuming 'label' is the desired key
                    $value_data['price'] = $value['price'];

                    $formated_field['values'][] = $value_data;
                }
            }
            $formated_field_group[] = $formated_field;
        }
    }
    $json_product_fields = json_encode($formated_field_group);

    /*-----------------------------update product fields----------------------------------------------*/
    update_post_meta($wp_product_id, 'custom_fields_data', sanitize_text_field($json_product_fields));

    //update extra data
    update_post_meta($wp_product_id, '_custom_data1', sanitize_text_field($product['data01']));
    update_post_meta($wp_product_id, '_custom_data2', sanitize_text_field($product['data02']));
    update_post_meta($wp_product_id, '_custom_data3', sanitize_text_field($product['data03']));

    //set the product featured_image
    $featured_image = $product['image'];
    if(!empty($featured_image)){
        $attachment_id = download_image_to_media_library($featured_image['src'], $wp_product_id);
        if ($attachment_id && !is_wp_error($attachment_id)) {
            set_post_thumbnail($wp_product_id, $attachment_id);
        }
    }
    
    //update gallery images
    $images = $product['images'];
    if(!empty($images)){
        foreach ($images as $key => $image) {
            $attachment_id = download_image_to_media_library($image['src'], $wp_product_id);
            // echo "attachment-id". $attachment_id. "<br>";
            // Handle WooCommerce product image assignment
            if ($wp_product_id && get_post_type($wp_product_id) === 'product') {
                $product = wc_get_product($wp_product_id);
                if ($product) {
                    // Get existing gallery image IDs
                    $existing_gallery_ids = $product->get_gallery_image_ids();
                    $new_gallery_ids = $existing_gallery_ids;
            
                    foreach ($images as $key => $image) {
                        $attachment_id = download_image_to_media_library($image['src'], $wp_product_id);
                        // echo "attachment-id: " . $attachment_id . "<br>";
            
                        // If image is valid and not already in the gallery, add it
                        if ($attachment_id && !in_array($attachment_id, $existing_gallery_ids)) {
                            $new_gallery_ids[] = $attachment_id;
                        }
                    }
            
                    // Set updated gallery (remove duplicates)
                    $product->set_gallery_image_ids(array_unique($new_gallery_ids));
                    $product->save();
            
                    // Optional: Set product type
                    wp_set_object_terms($wp_product_id, 'outdoor', 'product_type');
                    
                }
            }
        }
    }
}
//lightspeed api call
function call_lightspeed_api($endpoint, $params = [])
{
    $apiKey = '6ae3280b9c85c82a28498a1147630b62';
    $apiSecret = '6cbd4fe515632d39dc761ae42301caa8';
    $baseUrl = 'https://' . $apiKey . ':' . $apiSecret . '@api.webshopapp.com/nl';
    
    $curl = curl_init();

    
    // Add query parameters if any
    if (!empty($params) && is_array($params)) {
        $endpoint .= '?' . http_build_query($params);
    }
    $url = $baseUrl . $endpoint;

    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
    ];
    
    curl_setopt_array($curl, $options);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return ['error' => $err];
    }

    return json_decode($response, true);
}

//delete product
add_action('init', 'delete_all_wc_products');
function delete_all_wc_products() {

    if(!isset($_GET['delete_p'])){
        return;
    }

    // Ensure WooCommerce is loaded
    if (!class_exists('WooCommerce')) {
        echo "WooCommerce is not activated!";
        return;
    }

    // Get all product IDs
    $args = [
        'post_type'   => ['product'],
        'numberposts' => -1,
        'fields'      => 'ids', // Only get post IDs
        'tax_query'   => [
            [   
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => 'outdoor', // The custom product type
            ],
        ]
    ];

    $products = get_posts($args);

    if (empty($products)) {
        echo "No products found.";
        return;
    }

    foreach ($products as $product_id) {
        wp_delete_post($product_id, true); // true for permanent delete
        echo "Deleted product ID: " . $product_id . "<br>";
    }

    echo "All products have been deleted.";
}






?>
