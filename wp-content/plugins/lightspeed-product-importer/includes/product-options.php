<?php
add_action('wp_enqueue_scripts', 'enqueue_custom_price_update_script');
function enqueue_custom_price_update_script() {
    if (is_product()) {
        wp_enqueue_script(
            'custom-price-update',
            LIGHTSPEED_IMPORTER_PLUGIN_URL . 'assets/js/custom-price-update.js',
            array('jquery'),
            null,
            true
        );
        wp_enqueue_style( 'product-options', 
            LIGHTSPEED_IMPORTER_PLUGIN_URL . 'assets/css/product-options.css',
            [],
            '1.1',
            'all'
        );
    }
}

// add_filter('woocommerce_get_price_html', 'custom_wrap_sale_price_amount', 10, 2);
function custom_wrap_sale_price_amount($price, $product) {
  if (is_product()) {
    if ($product->is_on_sale()) {
      $sale_price = $product->get_sale_price();
      $currency_code = get_woocommerce_currency_symbol();
      $price = "<span>".$currency_code."</span><span id='product-price'>".$sale_price."</span>";
    }
  }
  return $price;
}


// 1. Add hidden input field before the Add to Cart button
add_action( 'woocommerce_before_add_to_cart_button', 'custom_html_before_add_to_cart' );
function custom_html_before_add_to_cart() {
    global $product;
    
    $product_id = $product->get_id();

    // Retrieve the custom meta field 'custom_variants_data' for the product
    $custom_variants_json = get_post_meta($product_id, 'custom_variants_data', true);
    if(!empty($custom_variants_json)){
      $custom_variants_data = json_decode($custom_variants_json, true);
      $custom_class = "custm_field";
      foreach ($custom_variants_data as $key => $custom_variants) {
        $isRequired = $custom_variants['isRequired'] ?? false;
        
        if($custom_variants['type'] === 'radio'){
          echo '<div style="padding: 15px 0;">';
          echo '<h5>' . htmlspecialchars($custom_variants['heading']).':</h5>';
          foreach ($custom_variants['values'] as $optIndex => $option) {
            $uniq = uniqid('opt_');
            $checked = isset($option['isDefault']) && $option['isDefault'] ? 'checked' : '';
            echo '<div class="option-row">';
            echo '<input id="'.$uniq.'" class="'.$custom_class.'" type="radio" name="'.$custom_variants['name'].'" value="'.htmlspecialchars($option['heading']).'" data-price="'.htmlspecialchars($option['price']).'" '.$checked.'>';
            echo '<label for="'.$uniq.'">' . htmlspecialchars($option['heading']);
            echo (isset($option['price']) && !empty($option['price'])) ? ' (+' . htmlspecialchars($option['price']) . ')' : '';
            echo '</label>';
            echo '</div>';
          }
          echo '</div>';
        }else if($custom_variants['type'] === 'text'){
          echo '<div style="padding: 15px 0;">';
          echo (trim($custom_variants['heading']) != 'Default') ? '<h5>' . htmlspecialchars($custom_variants['heading']).':</h5>' : '';
          echo '<input type="hidden" class="'.$custom_class.'" data-price="'.$custom_variants['price'].'">';
          echo '</div>';
        }
      }
    }


    // Retrieve the custom meta field 'custom_fields_data' for the product
    $custom_fields_json = get_post_meta($product_id, 'custom_fields_data', true);

    // Check if the meta field has a value and display it
    if (!empty($custom_fields_json)) {
      $data = json_decode($custom_fields_json, true);
      // echo "<pre>";print_r($data);die;
      // Get the current price (will be sale price if set)
      $price = $product->get_price();
      echo '<input type="hidden" id="base_product_price" value="' . esc_attr($price) . '">';
      echo '<input type="hidden" name="custom_price">';
      foreach ($data as $index => $input) {
          // print_r($input);die;
          echo '<div class="input_container">';
          $isRequired = ($input['isRequired']) ? 'required' : '';
          $custom_class = "custm_field";
          switch ($input['type']) {
              case 'text':
                echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
                echo '<input class="'.$custom_class.'" type="text" name="' . $input['name'] . '" ' . $isRequired . '><br>';
                break;
                
              case 'textarea':
                echo '<p class="'.$custom_class.'" name="' . $input['name'] . '">'.htmlspecialchars($input['heading']).'</p>';
                break;
                
              case 'radio':
                  echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
                  foreach ($input['values'] as $optIndex => $option) {
                      $uniq = uniqid('opt_');
                      echo '<div class="option-row">';
                      echo '<input id="'.$uniq.'" class="'.$custom_class.'" type="radio" name="'.$input['name'].'" value="'.htmlspecialchars($option['heading']).'" data-price="'.htmlspecialchars($option['price']).'" '.$isRequired.'>';
                      echo '<label for="'.$uniq.'">' . htmlspecialchars($option['heading']);
                      if (!empty($option['price'])) {
                          echo ' (+' . htmlspecialchars($option['price']) . ')';
                      }
                      echo '</label>';
                      echo '</div>';
                  }
                  break;
              
              case 'select':
                  echo '<label>' . htmlspecialchars($input['heading']) . '</label><br>';
                  echo '<select class="'.$custom_class.'" name="'.$input['name'].'" '.$isRequired.'>';
                  echo '<option value="">Make a choice..</option>';
                  foreach ($input['values'] as $option) {
                      echo '<option value="' . htmlspecialchars($option['heading']) . '" data-price="'.htmlspecialchars($option['price']).'">';
                      echo htmlspecialchars($option['heading']);
                      if (!empty($option['price'])) {
                          echo ' (+' . htmlspecialchars($option['price']) . ')';
                      }
                      echo '</option>';
                  }
                  echo '</select><br>';
                  break;
      
              default:
                  echo '<p>Unsupported input type: ' . htmlspecialchars($input['type']) . '</p>';
                  break;
          } 
          echo '</div><br>';
      }
    }
}

// 2. Capture custom price from POST data and add it to the cart item
add_filter( 'woocommerce_add_cart_item_data', 'capture_custom_price_on_add_to_cart', 10, 2 );
function capture_custom_price_on_add_to_cart( $cart_item_data, $product_id ) {
    //filter the data only custom fields
    // print_r($_POST);die;
    $field_data = get_custom_fields_data($_POST);
    // print_r($field_data);die;
    if(!empty($field_data)){
      foreach ($field_data as $key => $value) {
        $cart_item_data[$key] = sanitize_text_field($value);
      }
    }
    if ( isset( $_POST['custom_price'] ) ) {
        $cart_item_data['custom_price'] = floatval( sanitize_text_field( $_POST['custom_price'] ) );
    }
    return $cart_item_data;
}

/**
 * Display custom item data in the cart
 */
add_filter( 'woocommerce_get_item_data', 'wk_get_item_data', 10, 2 );
function wk_get_item_data( $item_data, $cart_item_data ) {
    $product = $cart_item_data['data'];
    $product_id = $product->get_id();
    $custom_variants_json = get_post_meta($product_id, 'custom_variants_data', true);
    $custom_variants[] = json_decode($custom_variants_json, true);
    // echo "<pre>";print_r($custom_variants);
    $custom_fields_json = get_post_meta($product_id, 'custom_fields_data', true);
    $custom_fields = json_decode($custom_fields_json, true);

    //merge all custom data
    $custom_fields_data = array_merge($custom_fields, $custom_variants);
    // echo "<pre>";print_r($custom_fields_data);
    // Check if the meta field has a value and display it
    if (!empty($cart_item_data)) {
      $field_data = get_custom_fields_data($cart_item_data);
      if(!empty($field_data)){
        foreach ($field_data as $key => $value) {
          // echo $key."<br>";
          $heading = get_heading_by_name($custom_fields_data, $key);
          $item_data[] = array(
            'key'   => __( $heading, 'woocommerce' ),
            'value' => wc_clean( $value ),
          );
        }
      }
    }
  return $item_data;
}


// 3. Preserve custom price when loading cart from session
add_filter( 'woocommerce_get_cart_item_from_session', 'get_custom_price_from_session', 20, 2 );
function get_custom_price_from_session( $cart_item, $values ) {
    if ( isset( $values['custom_price'] ) ) {
        $cart_item['custom_price'] = $values['custom_price'];
    }
    return $cart_item;
}

// 4. Override cart item price with custom price
add_action( 'woocommerce_before_calculate_totals', 'override_price_with_custom_price', 10 );
function override_price_with_custom_price( $cart ) {
    if ( is_admin() && !defined( 'DOING_AJAX' ) ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        if ( isset( $cart_item['custom_price'] ) ) {
            $cart_item['data']->set_price( $cart_item['custom_price'] );
        }
    }
}

