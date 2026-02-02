<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Define a global variable to track if the shortcode has been used
global $has_glass_configurator_shortcode;
$has_glass_configurator_shortcode = false;

add_shortcode('glass_configurator', function($atts = [], $content = null) {
    global $has_glass_configurator_shortcode;
    
    // Set the flag to true because the shortcode is present on this page
    $has_glass_configurator_shortcode = true;

    $uid = uniqid('gc_');
    ob_start();
    $index = 1;
    $blockId = $uid .'_'. $index;
    
    $glass_type = 'pa_soort-glas';
    $product_glass_types = wc_glass_get_attribute_terms($glass_type);
    $glass_terms = wc_glass_get_attribute_terms($glass_type);

    $color = 'pa_kleur';
    $color_terms = wc_glass_get_attribute_terms($color);

    // Get the global product options
    $radio_options = WC_GLASS_HANDGREEP_OPTIONS;
    $checkbox_options = WC_GLASS_EXTRA_OPTIONS;
    
    ?>
    <div id="gc-container">
        <h3><?php echo esc_html__('Glass Frame Configurator', 'your-text-domain'); ?></h3>
        <div class="gc-wrapper">
            <div class="gc-step-row">
                <div class="gc-left">
                    <!-- Form Template -->
                    <div class="form-wrapper">
                        <form class="gc-step-form" onsubmit="return false;" data-step-index="0">
                            <h5>Schuifwand <?= $index; ?>:</h5>
                            <div class="conf_input-wrapper">
                                <!-- Glas type -->
                                <div class="input-box">
                                    <label>Glas type</label>
                                    <select name="<?= $glass_type; ?>" data-title="Soort glas" class="glass_type">
                                        <option value="">Glass type</option>
                                        <?php 
                                            if(!empty($glass_terms)):
                                                foreach ($glass_terms as $key => $terms):
                                                    echo '<option value="'.$terms->slug.'" >'. $terms->name .'</option>';
                                                endforeach;
                                            endif;
                                        ?>
                                    </select>
                                </div>
                                <!-- Width -->
                                <div class="input-box">
                                    <label>Kaderbreedte</label>
                                    <input type="number" name="width" data-title="Width" step="1" min="1500" max="7000" class="width" placeholder="Width (mm)">
                                    <div class="width-error error-message"></div>
                                </div>
                                <!-- Height -->
                                <div class="input-box">
                                    <label>Kaderhoogte</label>
                                    <input type="number" name="height" data-title="Height" step="1" min="1700" max="3000" class="height" placeholder="Height (mm)">
                                    <div class="height-error error-message"></div>
                                </div>
                                <!-- Color -->
                                <div class="input-box">
                                    <label>Profielkleur</label>
                                    <select name="<?= $color; ?>" data-title="Kleur" class="glass_color">
                                        <option value="">Color Rails</option>
                                        <?php 
                                        if(!empty($color_terms)):
                                            foreach ($color_terms as $key => $terms): 
                                                echo '<option value="'.$terms->slug.'" >'. $terms->name .'</option>';
                                            endforeach;
                                        endif;
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="extra-options">
                                <div class="gc-radio-container">
                                    <?php 
                                    foreach ($radio_options as $groupName => $options):
                                        // ✅ Create a common label for the group
                                        $groupLabel = ucfirst(str_replace('_', ' ', $groupName));
                                        echo '<div class="group-label" name="' . $groupName . '" data-label="'.esc_html($groupLabel).'">';
                                        foreach ($options as $index => $opt) {
                                            $label = $opt['label'];
                                            // ✅ Create slug for ID (unique per option)
                                            $id = strtolower(preg_replace('/[^A-Za-z0-9_]+/', '_', $groupName . '_' . $index));
                                            // ✅ Group name (same for all radios in this group)
                                            $value = $opt['value'];
                                            echo '<div class="input-box">';
                                            echo '<input type="radio" class="option-radio" name="' . $groupName . '" id="' . $id . '" value="' . $value . '">';
                                            echo '<label for="' . $id . '">' . $label . '</label>';
                                            echo '</div>';
                                        }
                                        echo "</div>";
                                    endforeach;
                                    ?>
                                </div>
                                <div class="gc-checkbox-container">
                                    <?php 
                                    // print_r($checkbox_options);
                                    $i= 0;
                                    foreach ($checkbox_options as $key => $opt):
                                        $id = $key."_".$index."_".$i;
                                        echo '<div class="input-box">';
                                        echo '<input type="checkbox" class="option-checkbox" name="'. $key .'" id="'. $id .'">';
                                        echo '<label for="'. $id .'">' . $opt['label'] . '</label>';
                                        echo '</div>';
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    <button type="button" class="button gc-add-more" style="margin-top:15px;">+ Meer toevoegen</button>
                </div>
                <div class="gc-right">
                    <div class="preview-item-wrapper" style="display:block;">
                        <div class="dummy-img-content">
                            <img class="position-dummy-img" src="https://glazenschuifwandoverkapping.nl/wp-content/uploads/2025/11/dummy-img.jpg" alt="">
                            <div class="position-content">

                            <h4>Glagen schuifwanden de actile loopt tot en met <span class="big-text">31</span>Dec 2025</h4>
                            <p class="discount"><span>11% Korting</span></p>
                            <div class="step-container">
                              <div class="step-box" id="step-1">
                                <div class="step-number">1</div>
                                <div class="step-text">SELECTEER GLASTYPE</div>
                              </div>

                              <div class="step-box" id="step-2">
                                <div class="step-number">2</div>
                                <div class="step-text">AFMETINGEN INVOEREN</div>
                              </div>

                              <div class="step-box" id="step-3">
                                <div class="step-number">3</div>
                                <div class="step-text">SELECTEER KLEUR RAILS</div>
                              </div>
                            </div>
                        </div>
                            <div class="cs-image-bottom-title">
                              <div class="title-text" id="animated-bottom-title">
                                BEREKEN UW PRIJS IN 3 STAPPEN
                              </div>
                            </div>
                        </div>
                    </div>
                    <div class="gc-overview-outer">
                        <div class="gc-overview">
                        <button class="button" id="gc-add-to-cart" style="display:none;">Add to cart</button>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    return ob_get_clean();
});

// 3. Enqueue the script conditionally
add_action('wp_enqueue_scripts', 'enqueue_configurator_scripts');
function enqueue_configurator_scripts() {
    // Register the JS file
    wp_register_script(
        'configurator', 
        plugins_url('assets/js/configurator.js', WC_GLASS_PRODUCT_FILE),
        [], 
        '1.0.0', 
        true
    );
    wp_localize_script('configurator', 'gc_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'currency_symbol' => get_woocommerce_currency_symbol(),
        'zero_price'      =>  strip_tags(wc_price(0)),
    ]);

    // Register the CSS file
    wp_register_style(
        'configurator-style',
        plugins_url('assets/css/configurator.css', WC_GLASS_PRODUCT_FILE),
        [],
        '1.0.0'
    );
}

// 4. Conditionally *enqueue* (print to the page) the assets in the footer
add_action('wp_footer', 'conditionally_enqueue_configurator_assets');
function conditionally_enqueue_configurator_assets() {
    global $has_glass_configurator_shortcode;

    // Check the global flag
    if ($has_glass_configurator_shortcode) {
        // Enqueue the registered scripts/styles if the flag is true
        wp_enqueue_script('configurator');
        wp_enqueue_style('configurator-style');
    }
}

add_action('wp_ajax_gc_get_product_data', 'gc_get_product_data');
add_action('wp_ajax_nopriv_gc_get_product_data', 'gc_get_product_data');
function gc_get_product_data() {

    $response = [];
    $selections = WC_DEFAULT_OPTIONS;

    if (empty($_POST['form_data'])) {
        wp_send_json_error(['message' => 'Missing form data']);
    }

    $form_data = json_decode(stripslashes($_POST['form_data']), true);

    if (!is_array($form_data)) {
        wp_send_json_error(['message' => 'Invalid form data']);
    }

    // Width required
    if (!isset($form_data['width']['value'])) {
        wp_send_json_error(['message' => 'Width not provided.']);
    }

    $panel_width = floatval($form_data['width']['value']);

    // Get product based on width
    $product_data = get_glass_products($panel_width);

    if (!is_array($product_data) || empty($product_data['product_id'])) {
        wp_send_json_error(['message' => 'No matching product found.']);
    }

    $product_id  = $product_data['product_id'];
    $overlap_val = $product_data['overlapping'];

    $product = wc_get_product($product_id);
    if (!$product) wp_send_json_error(['message' => 'Invalid product.']);

    $response['product_id'] = $product_id;

    $product_price = floatval($product->get_price());

    // Meta data
    $glass_width     = floatval($product->get_meta('_glass_width'));
    $glass_quantity  = intval($product->get_meta('_glass_quantity'));

    // Build auto selections (panel, overlapping)
    $panel_text = $glass_quantity . ' x ' . $glass_width . 'mm';

    $panel = [
        'title' => 'Panel',
        'value' => $panel_text,
        'price' => 0
    ];

    $overlapping_value = $overlap_val > 0 ? round($overlap_val, 1).'mm' : '';

    $overlapping = [
        'title' => 'Overlapping',
        'value' => $overlapping_value,
        'price' => $product_price
    ];

    $total_amount = $product_price; // start with product base price

    // -----------------------
    // PROCESS USER SELECTIONS
    // -----------------------
    foreach ($form_data as $key => $item) {

        // Skip empty values
        if (!isset($item['value']) || $item['value'] === '') continue;

        $value = $item['value'];
        $title = $item['title'] ?? ucfirst(str_replace('_',' ', $key));

        // Add mm for width/height
        if ($key === 'width' || $key === 'height') {
            $value .= 'mm';
        }

        // Calculate price
        $price = wc_glass_get_price($glass_quantity, $glass_width, $key);

        // Handle handgreep
        if ($key === 'handgreep' && stripos($value, '1x') === false) {
            $price = $price * max(($glass_quantity - 1), 1);
        }

        if (!empty($price) && is_numeric($price)) {
            $total_amount += floatval($price);
        }

        $selections[$key] = [
            'title' => $title,
            'value' => $value,
            'price' => wc_price($price),
        ];

        // Inject panel + overlapping only when kleur is selected
        if ($key === 'pa_kleur') {
            $selections['panel'] = $panel;
            $selections['overlapping'] = $overlapping;
        }
    }


    // Final response
    $response['selections']   = $selections;
    $response['total_amount'] = $total_amount;

    wp_send_json_success($response);
}


add_action('wp_ajax_gc_add_to_cart', 'gc_add_to_cart');
add_action('wp_ajax_nopriv_gc_add_to_cart', 'gc_add_to_cart');
function gc_add_to_cart() {
    // Sanitize and decode selections
    $selectionsData = isset($_POST['selections']) ? wp_unslash($_POST['selections']) : '';
    $selectionsArray = json_decode($selectionsData, true);
    
    if (empty($selectionsArray) || !is_array($selectionsArray)) {
        wp_send_json_error(['message' => 'Invalid or missing data.']);
    }
    // print_r($selectionsArray);die;    
    $added_items = [];
    foreach ($selectionsArray as $selectedData) {
        // print_r($selectedData);die("ocean");
        if (empty($selectedData) || !is_array($selectedData)) {
            continue;
        }
        
        // ✅ Get product id and remove from main selection
        $product_id = intval($selectedData['product_id']);
        unset($selectedData['product_id']);
        
        // ✅ Calculate custom total price from form selections
        $product_price = $selectedData['total_amount'];
        // print_r($product_price);die;
        // foreach ($selectedData as $key => $data) {
        //     if (isset($data['price']) && is_numeric($data['price'])) {
        //         $product_price += floatval($data['price']);
        //     }
        // }
        // ✅ Prepare custom cart item data
        $cart_item_data = [
            'step_selections' => $selectedData['selections'],
            'custom_price'    => $product_price,
        ];

        // ✅ Add product to cart
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if ($cart_item_key) {
            $added_items[] = [
                'cart_item_key' => $cart_item_key,
                'product_id'    => $product_id,
                'price'         => wc_price($product_price),
            ];
        }
    }

    if (!empty($added_items)) {
        wp_send_json_success(['items' => $added_items]);
    } else {
        wp_send_json_error(['message' => 'Could not add any items to cart.']);
    }
}

function decode_wc_price($price_html) {
    // Remove HTML tags
    $clean = wp_strip_all_tags($price_html);

    // Remove currency symbols and non-numeric characters
    $clean = preg_replace('/[^0-9.,-]/', '', $clean);

    // Convert comma decimals to dot if needed
    if (strpos($clean, ',') !== false && strpos($clean, '.') === false) {
        $clean = str_replace(',', '.', $clean);
    }

    return floatval($clean);
}


/**
 * ✅ Set custom product price in WooCommerce cart
 * This ensures the dynamically calculated price replaces the default product price
 */
add_action('woocommerce_before_calculate_totals', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['custom_price']) && is_numeric($cart_item['custom_price'])) {
            $cart_item['data']->set_price(floatval($cart_item['custom_price']));
        }
    }
});


add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
    // print_r($cart_item['step_data']);die;
    if ( isset( $cart_item['step_data'] ) && is_array( $cart_item['step_data'] ) ) {
        foreach ( $cart_item['step_data'] as $key => $value ) {
            $item_data[] = [
                'name'  => ucwords(str_replace('_', ' ', $key)),
                'value' => $value,
            ];
        }
    }
    return $item_data;
}, 10, 2);

