<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('wp_enqueue_scripts', function() {
    // Register the JS script (no jQuery dependency now)
    wp_register_script(
        'wc-step-product-js',
        plugins_url('assets/js/step-product.js', WC_GLASS_PRODUCT_FILE),
        [], // no dependencies
        '1.0',
        true // load in footer
    );

    // Localize AJAX params for vanilla JS
    wp_localize_script('wc-step-product-js', 'wc_add_to_cart_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'cart_url' => wc_get_cart_url(),
    ]);

    // Register the CSS file
    wp_register_style(
        'wc-step-product-css',
        plugins_url('assets/css/step-product.css', WC_GLASS_PRODUCT_FILE),
        [],
        '1.0'
    );

    // Enqueue the scripts and styles
    wp_enqueue_script('wc-step-product-js');
    wp_enqueue_style('wc-step-product-css');
});

function get_value_by_title($array, $key) {
    foreach ($array as $item) {
        if (isset($item['title']) && $item['title'] === $key) {
            return $item['value'];
        }
    }
    return null; 
}

add_action('woocommerce_before_add_to_cart_quantity', function() {
    global $product;
    
    if (!$product || $product->get_type() !== 'glass_product') return;

    // Product price
    $product_price = (float) $product->get_price();

    // Get the option price
    $glass_quantity = $product->get_meta('_glass_quantity', true);
    $glass_width = $product->get_meta('_glass_width', true);
    
    // Cart items data
    $cart_item_key = isset($_GET['edit_cart_item']) ? sanitize_text_field($_GET['edit_cart_item']) : '';
    $cart = WC()->cart->get_cart();
    $selectionData = $cart[$cart_item_key]['step_selections'] ?? []; // steps selected data

    // Glass
    $glass_type =  $selectionData['pa_soort-glas'] ?? '';
    $selected_type_value = $glass_type['value'] ?? 'getint-glas';

    // Color
    $color = $selectionData['pa_kleur'] ?? '';
    $selected_color_value = $color['value'] ?? 'antraciet';

    /*----------------------------------start add hidden fields--------------------------------------------*/
    echo '<input type="hidden" name="product_price" value="'.$product_price.'">'; 
    echo '<div class="cart-items-data">';

    // Add hidden field for default order
    $default_structure = WC_DEFAULT_OPTIONS;
    if (isset($_GET['edit_cart_item']) && isset($cart[$cart_item_key])) {

        $cart_item_key = sanitize_text_field($_GET['edit_cart_item']);
        $item = $cart[$cart_item_key];
        $selectedData = $item['step_selections'] ?? [];

        // Merge defaults + selected data
        foreach ($default_structure as $key => $default) {
            $title = $default['title'];
            $value = $default['value'];
            $price = $default['price'];

            if (isset($selectedData[$key])) {
                $value = $selectedData[$key]['value'];
                $price = $selectedData[$key]['price'];
            }

            echo '<input type="hidden" name="'.$key.'" value="'.esc_attr($value).'" data-title="'.$title.'" data-price="'.$price.'">';
        }

    } else {

        // For new product (not editing)
        // Fill only kleur & soort with selected values
        $default_structure['pa_soort-glas']['value'] = $selected_type_value ?? '';
        $default_structure['pa_kleur']['value'] = $selected_color_value ?? '';

        // Output in correct order
        foreach ($default_structure as $key => $opt) {
            echo '<input type="hidden" name="'.$key.'" value="'.esc_attr($opt['value']).'" data-title="'.$opt['title'].'" data-price="'.$opt['price'].'">';
        }
    }
    /*----------------------------------end add hidden fields--------------------------------------------*/
    // Display options for 'Soort Glas' (type of glass) attribute
    $taxonomy_title_type = "Soort glas";
    $taxonomy_slug_type = 'pa_soort-glas';
    $product_glass_types = get_product_attribute_terms_by_id($product->get_id(), $taxonomy_slug_type);
    echo '<div id="step-builder">';
    echo '<div class="step">';
    echo "<h3>{$taxonomy_title_type}:<span></span></h3>";
    echo '<div class="radio-wrapper">';
    if (!empty($product_glass_types)) {
        foreach ($product_glass_types as $term) {
            $type_name = esc_html($term->name);
            $type_slug = esc_attr($term->slug);
            $unique_id = $taxonomy_slug_type . '_' . $type_slug; 
            $checked = ($type_slug == $selected_type_value) ? 'checked' : '';
            $image_url = !empty(get_term_image_url($term->term_id)) ? get_term_image_url($term->term_id) : wc_placeholder_img_src() ;
            ?>
            <div class="color-type-option radio_img-option">
                <input 
                    type="radio" 
                    <?= $checked; ?> 
                    data-title="<?= $taxonomy_title_type; ?>" 
                    id="<?php echo $unique_id; ?>" 
                    name="<?php echo $taxonomy_slug_type; ?>" 
                    value="<?php echo $type_slug; ?>"
                >
                <label for="<?php echo $unique_id; ?>">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo $type_name; ?>">
                </label>
            </div>
            <?php
        }
    }
    echo '</div>';
    echo '</div>';
    
    // Display options for 'Kleur' (color) attribute
    $taxonomy_title_color = "Kleur";
    $taxonomy_slug_color = 'pa_kleur';
    $product_colors = get_product_attribute_terms_by_id($product->get_id(), $taxonomy_slug_color);
    echo '<div class="step">';
    echo "<h3>{$taxonomy_title_color}:<span></span></h3>";
    echo '<div class="radio-wrapper">';
    if (!empty($product_colors)) {
        foreach ($product_colors as $term) {
            $color_name = esc_html($term->name);
            $color_slug = esc_attr($term->slug);
            $unique_id = $taxonomy_slug_color . '_' . $color_slug; 
            $checked = ($color_slug == $selected_color_value) ? 'checked' : '';
            $image_url = !empty(get_term_image_url($term->term_id)) ? get_term_image_url($term->term_id) : wc_placeholder_img_src() ;
            ?>
            <div class="color-option radio_img-option">
                <input 
                    type="radio" 
                    <?= $checked; ?> 
                    data-title="<?= $taxonomy_title_color; ?>" 
                    id="<?php echo $unique_id; ?>" 
                    name="<?php echo $taxonomy_slug_color; ?>" 
                    value="<?php echo $color_slug; ?>"
                >
                <label for="<?php echo $unique_id; ?>">
                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo $color_name; ?>">
                </label>
            </div>
            <?php
        }
    }
    echo '</div>';
    echo '</div>';
    
    // Handgreep options
    $handgreep_options = WC_GLASS_HANDGREEP_OPTIONS;
    if (!empty($handgreep_options)):
        foreach ($handgreep_options as $name => $options_list): 
            $title = ucwords(str_replace('_', ' ', $name));
        ?>
            <div class="step">
                <h3><?= esc_html(ucfirst($name)); ?></h3>
                <div class="option-group">
                    <?php foreach ($options_list as $options): 
                        $option_title = ucwords(str_replace('_', ' ', $options['label']));
                        $value = $options['value'];
                        $price = wc_glass_get_price($glass_quantity, $glass_width, $name);
                        if (strpos($value, 'ja 1x') === false) {
                            $price = $price * $glass_quantity;
                        }
                        // Checked
                        $checked = isset($selectedData) && (array_key_exists($name, $selectedData) && $selectedData[$name]['value'] === $value)
                        ? 'checked' : '';
                    ?>
                    <div class="option-item <?= $checked === 'checked' ? 'active' : ''; ?>">
                        <input 
                            type="radio" 
                            data-title="<?= $title; ?>" 
                            data-price="<?= $price; ?>" 
                            name="<?= $name; ?>" 
                            value="<?= $value; ?>"
                            <?= $checked; ?>
                            style="display:none;"
                        >
                        <h3><?= esc_html($option_title); ?></h3>
                        <span><?= wc_price($price); ?></span>
                    </div>
                    <?php endforeach; ?>  
                </div>
            </div>
        <?php endforeach;
    endif;

    // Extra options
    $extra_options = WC_GLASS_EXTRA_OPTIONS;
    foreach ($extra_options as $name => $options) {
        // Convert section title to readable format (first letter capital)
        $section_title = ucwords(str_replace('_', ' ', $options['label']));
        $value = $options['value'];
        $price = wc_glass_get_price($glass_quantity, $glass_width, $name);
        // Checked
        $checked = isset($selectedData) && (array_key_exists($name, $selectedData) && $selectedData[$name]['value'] === $value)
                        ? 'checked' : '';
    ?>
        <div class="step">
            <div class="option-item <?= $checked === 'checked' ? 'active' : ''; ?>">
                <input 
                    type="checkbox" 
                    data-title="<?= $section_title; ?>" 
                    data-price="<?= $price; ?>" 
                    name="<?= $name; ?>" 
                    value="<?= $value; ?>"
                    <?= $checked; ?>
                    style="display:none;"
                >
                <h3><?= esc_html($section_title); ?></h3>
                <span><?= wc_price($price); ?></span>
            </div>
        </div>
    <?php
    }
    
    // Steps of all glass options
    $steps = get_post_meta($product->get_id(), 'glass_option_data', true);
    if(!empty($steps)){
        foreach ($steps as $i => $step) {
            $title = isset($step['title']) ? $step['title'] : '';
            $options = isset($step['options']) ? $step['options'] : [];
            $selected = get_value_by_title($selectedData, $title);
            $name = strtolower(str_replace([' ', '_'], '-', $title));

            // Flag to track if any valid option title is found
            $has_valid_options_with_title = false;
            foreach ($options as $opt) {
                if (isset($opt['title']) && !empty($opt['title'])) {
                    $has_valid_options_with_title = true;
                    break; // Stop checking after finding the first valid title
                }
            }

            // Only print the entire structure (including the h3) if we found at least one valid option title
            if (!empty($title) && $has_valid_options_with_title) {
                echo '<div class="step" data-step="'. esc_attr($i) .'">';
                echo '<h3>'. esc_html($title) .'</h3>';

                if (!empty($options)) {
                    echo '<select name="'.$name.'" data-title="'.$title.'" data-price="0" id="step_'.esc_attr($i).'">';
                    echo count($options) > 1 ? '<option value="">Select an option</option>' : '';
                    foreach ($options as $opt_index => $opt) {
                        $opt_title = isset($opt['title']) ? $opt['title'] : '';
                        $selectedOption = $selected == $opt_title ? 'selected' : '';
                        $opt_price = isset($opt['price']) ? floatval($opt['price']) : 0;
                        
                        // select text
                        $opt_price_html = wc_price( floatval( $opt['price'] ) );
                        $opt_val = $opt['title'] . ' ' . $opt_price_html;

                        // Ensure the option itself is valid before printing the <option> tag
                        if (!empty($opt_title)) {
                            echo '<option '.$selectedOption.' value="' . esc_attr( $opt_title ) . '" data-price="' . esc_attr( $opt_price ) . '">' . esc_html( $opt_title ) . ' (' . $opt_price_html . ')</option>';
                        }
                    }
                    echo '</select>';
                }
                echo '</div>';
            }
        }
    }
    echo '</div>';

});

add_action('woocommerce_before_add_to_cart_button', function () {
    if (empty($_GET['edit_cart_item'])) return;

    $cart_item_key = sanitize_text_field($_GET['edit_cart_item']);
    $cart = WC()->cart->get_cart();

    if (!isset($cart[$cart_item_key])) return;
    echo isset($cart_item_key) ? '<input type="hidden" name="edit_key" value="'.$cart_item_key.'">' : '';

    $item = $cart[$cart_item_key];
    $selectedData = $item['step_selections'] ?? [];
    
    
});

// add product into the cart
add_action( 'wp_ajax_wc_glass_product_add_to_cart', 'wc_glass_product_add_to_cart' );
add_action( 'wp_ajax_nopriv_wc_glass_product_add_to_cart', 'wc_glass_product_add_to_cart' );
function wc_glass_product_add_to_cart() {
    $product_id  = intval($_POST['product_id'] ?? 0);
    $total_price = floatval($_POST['total_price'] ?? 0);
    $quantity    = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
    $edit_key    = sanitize_text_field($_POST['edit_key'] ?? '');

    if (!$product_id) wp_send_json_error(['message'=>'Invalid product ID']);

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable()) wp_send_json_error(['message'=>'Product not purchasable']);

    $cart = WC()->cart; 
    
    //Get the selections data
    if (isset($_POST['selections'])) {
        if (is_array($_POST['selections'])) {
            $selections = $_POST['selections'];
        } else {
            $selections = json_decode(stripslashes($_POST['selections']), true);
        }
    } else {
        $selections = [];
    }

    // Update existing cart item
    if ($edit_key && isset($cart->cart_contents[$edit_key])) {
        $cart->cart_contents[$edit_key]['step_total_price'] = $total_price;
        $cart->cart_contents[$edit_key]['step_selections']  = $selections;
        $cart->cart_contents[$edit_key]['quantity']         = $quantity;

        $cart->calculate_totals();
        WC()->session->set('cart', $cart->cart_contents);

        wp_send_json_success([
            'message' => 'Cart item updated',
            'cart_key' => $edit_key
        ]);
    }

    // Add new item if no edit key
    $cart_item_data = [
        'step_total_price' => $total_price,
        'step_selections'  => $selections,
    ];
    $cart_item_key = $cart->add_to_cart($product_id, $quantity, 0, [], $cart_item_data);

    if (!$cart_item_key) wp_send_json_error(['message'=>'Could not add to cart']);

    wp_send_json_success([
        'message' => 'Product added to cart',
        'cart_key' => $cart_item_key
    ]);
}

// Add selected options data to cart item meta and adjust price
add_filter('woocommerce_add_cart_item_data', function($cart_item_data, $product_id, $variation_id) {
    if (isset($_POST['step_selected']) && is_array($_POST['step_selected'])) {
        $cart_item_data['step_selected'] = array_values($_POST['step_selected']);
        // store unique key to avoid merging
        $cart_item_data['unique_key'] = md5( microtime().rand() );
    }

    if (isset($cart_item['step_total_price'])) {
        $cart_item_data['data']->set_price((float) $cart_item_data['step_total_price']);
    }

    return $cart_item_data;
}, 10, 3);

// Adjust price on cart based on selected options
add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (!empty($cart_item['step_selected']) && isset($cart_item['data']) ) {
            $base_price = floatval($cart_item['data']->get_price());
            $extra = 0;
            foreach ($cart_item['step_selected'] as $p) {
                $extra += floatval($p);
            }
            $new_price = $base_price + $extra;
            $cart_item['data']->set_price($new_price);
        }
    }
});



