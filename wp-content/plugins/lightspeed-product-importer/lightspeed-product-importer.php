<?php
/**
 * Plugin Name: Lightspeed Product Importer
 * Description: Adds a new custom product type in WooCommerce.
 * Version: 1.0
 * Author: Rohit
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define constants for plugin directory
define('LIGHTSPEED_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LIGHTSPEED_IMPORTER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('Lightspeed Product Importer requires WooCommerce to be installed and active.', 'lightspeed-product-importer'); ?></p>
        </div>
        <?php
    });
    return;
}

require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/helpers.php';
require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/lightspeed-api.php';
require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/image-handler.php';
require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/product-options.php';
require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'includes/lightspeed-sync.php';

//admin page
require_once LIGHTSPEED_IMPORTER_PLUGIN_PATH . 'admin/admin-page.php';

// add admin js
add_action('admin_enqueue_scripts', 'enqueue_custom_variant_admin_scripts');
function enqueue_custom_variant_admin_scripts($hook) {
    // Only load on the WooCommerce product edit page
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

    wp_enqueue_style( 'custom-variant-style', LIGHTSPEED_IMPORTER_PLUGIN_URL . 'assets/css/custom-variant.css', [], '1.2', 'all'
    );
}

//register and pass the php data into the javascript
add_action('admin_enqueue_scripts', 'enqueue_custom_admin_scripts');
function enqueue_custom_admin_scripts($hook) {
    // Only load on the WooCommerce product edit page
    if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

    global $post;
    if ('product' !== get_post_type($post)) return;

    wp_enqueue_script(
        'custom-variant-js', 
        LIGHTSPEED_IMPORTER_PLUGIN_URL . 'assets/js/custom-variant.js',
        ['jquery'], 
        '1.2', 
        true
    );

    // Localize the PHP data into JS
    $custom_variants = get_post_meta($post->ID, 'custom_variants_data', true);
    $custom_variants = !empty($custom_variants) ? json_decode($custom_variants) : [];
    wp_localize_script('custom-variant-js', 'customVariantData', [
        'variants' => $custom_variants
    ]);

   //Localize the PHP data into JS
    $custom_fields = get_post_meta($post->ID, 'custom_fields_data', true);
    $custom_fields = !empty($custom_fields) ? json_decode($custom_fields) : [];
    wp_localize_script('custom-variant-js', 'customFieldsData', [
        'fields' => $custom_fields
    ]);
}

// 1. Add New Product Type to Dropdown
add_filter( 'product_type_selector', 'add_outdoor_product_type_to_dropdown' );
function add_outdoor_product_type_to_dropdown( $types ){
    $types[ 'outdoor' ] = 'Outdoor Product';
    return $types;
}

// 2. Define the Product Class
add_action( 'init', 'create_outdoor_product_type_class' );
function create_outdoor_product_type_class(){
    if ( ! class_exists( 'WC_Product_Outdoor' ) ) {
        class WC_Product_Outdoor extends WC_Product {
            public function get_type() {
                return 'outdoor';
            }
        }
    }
}

// 3. Load the Custom Class
add_filter( 'woocommerce_product_class', 'load_outdoor_product_class', 10, 2 );
function load_outdoor_product_class( $classname, $product_type ) {
    if ( $product_type === 'outdoor' ) {
        $classname = 'WC_Product_Outdoor';
    }
    return $classname;
}

// 4. Show General Pricing Tab for Outdoor Products
add_action( 'woocommerce_product_data_panels', 'outdoor_product_type_show_price_tab' );
function outdoor_product_type_show_price_tab() {
    wc_enqueue_js( "     
        $(document.body).on('woocommerce-product-type-change',function(event,type){
            if (type=='outdoor') {
                $('.general_tab').show();
                $('.pricing').show();         
            }
        });      
    " );
    global $product_object;
    if ( $product_object && 'outdoor' === $product_object->get_type() ) {
        wc_enqueue_js( "
            $('.general_tab').show();
            $('.pricing').show();         
        " );
    }
}

// 5. Hide Irrelevant Tabs for Outdoor
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    $tabs['inventory']['class'][] = 'hide_if_outdoor';
    $tabs['shipping']['class'][] = 'hide_if_outdoor';
    $tabs['linked_product']['class'][] = 'hide_if_outdoor';
    $tabs['attribute']['class'][] = 'hide_if_outdoor';
    $tabs['advanced']['class'][] = 'hide_if_outdoor';
    return $tabs;
}, 9999 );

// 6. Show Add to Cart for Outdoor Type
add_action( "woocommerce_outdoor_add_to_cart", function() {
    do_action( 'woocommerce_simple_add_to_cart' );
});

/*-------------------------------------------create the option for data-------------------------------------------*/

// Add product data tabs for outdoor
add_filter('woocommerce_product_data_tabs', 'add_outdoor_product_tabs');
function add_outdoor_product_tabs($tabs) {
    $tabs['general']['class'][] = 'show_if_outdoor';

    $tabs['custom_variants_data'] = array(
        'label'    => __('Variants', 'woocommerce'),
        'target'   => 'custom_product_variants',
        'class'    => array('show_if_outdoor'),
    );

    $tabs['custom_fields_data'] = array(
        'label'    => __('Custom Fields', 'woocommerce'),
        'target'   => 'custom_product_fields',
        'class'    => array('show_if_outdoor'),
    );

    $tabs['custom_template_data'] = array(
        'label'    => __('Extra Template Data', 'woocommerce'),
        'target'   => 'custom_product_data_tab_content',
        'class'    => array('show_if_outdoor'),
    );

    return $tabs;
}

add_action('woocommerce_product_data_panels', 'custom_product_data_tab_content');
function custom_product_data_tab_content() {
    global $post;

    ?>
    <div id="custom_product_data_tab_content" class="panel woocommerce_options_panel">
        <div class="options_group">
            <?php
            woocommerce_wp_textarea_input([
                'id'          => '_custom_data1',
                'label'       => __('Data 1', 'woocommerce'),
                'class'       => 'widefat',
                'value'       => get_post_meta($post->ID, '_custom_data1', true),
            ]);
            
            woocommerce_wp_textarea_input([
                'id'          => '_custom_data2',
                'label'       => __('Data 2', 'woocommerce'),
                'class'       => 'widefat',
                'value'       => get_post_meta($post->ID, '_custom_data2', true),
            ]);
            
            woocommerce_wp_textarea_input([
                'id'          => '_custom_data3',
                'label'       => __('Data 3', 'woocommerce'),
                'class'       => 'widefat',
                'value'       => get_post_meta($post->ID, '_custom_data3', true),
            ]);
            ?>
        </div>
    </div>
    <?php
}

add_action('woocommerce_process_product_meta', 'save_custom_product_tab_fields');
function save_custom_product_tab_fields($post_id) {
    if (isset($_POST['_custom_data1'])) {
        update_post_meta($post_id, '_custom_data1', sanitize_text_field($_POST['_custom_data1']));
    }

    if (isset($_POST['_custom_data2'])) {
        update_post_meta($post_id, '_custom_data2', sanitize_text_field($_POST['_custom_data2']));
    }

    if (isset($_POST['_custom_data3'])) {
        update_post_meta($post_id, '_custom_data3', sanitize_text_field($_POST['_custom_data3']));
    }
}

/*variants*/
add_action('woocommerce_product_data_panels', 'custom_variants_product_tab_content');
function custom_variants_product_tab_content() {
    global $post;
    $custom_variants_data = get_post_meta($post->ID, 'custom_variants_data', true);
    // print_r($custom_variants_data);die;
    
?>
    <div id='custom_product_variants' class='panel woocommerce_options_panel'>
        <?php if(empty($custom_variants_data)): ?>
            <button type="button" class="button add_btn"><?php esc_html_e('Add Variant', 'woocommerce'); ?></button>
        <?php else: ?>
            <button type="button" class="button edit_btn"><?php esc_html_e('Edit Variant', 'woocommerce'); ?></button>
        <?php endif; ?>
        <div id="variant_table" style="margin-top: 20px;"></div>
        <!-- Popup Modal -->
        <div class="main-model" data-table-ref="variant_table">
            <button type="button" class="close_Btn" style="position: fixed;right: 0;top: 0;cursor: pointer;background-color: #2271b1;color:#fff;">✕</button>
            <h3>Variant Fields</h3>
            <div class="var-wrapper">
                <div style="display:flex;">
                    <select class="input_type" style="width: 60%;">
                        <option value="">Make choice...</option>
                        <option value="text">Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="radio">Radio</option>
                        <option value="select">Select</option>
                    </select>
                    <button type="button" class="custom-btn add_input_btn">Add</button>
                    <button type="button" class="custom-btn save_input_btn">Save</button>
                </div>
                <!-- input wrapper-->
                <div class="input_wrapper" style="margin-top: 15px;"></div> 
                <input type="hidden" class="data_input" name="custom_variants_data" id="custom_variants_data" value="<?php echo esc_attr($custom_variants_data); ?>">
            </div>
        </div>
    </div>
    <?php
}

/*custom fields*/
add_action('woocommerce_product_data_panels', 'custom_product_tab_content');
function custom_product_tab_content() {
    global $post;
    $custom_fields_data = get_post_meta($post->ID, 'custom_fields_data', true);
    // echo "<pre>";print_r($custom_fields_data);die;
?>
    <div id='custom_product_fields' class='panel woocommerce_options_panel'>
        <?php if(empty($custom_fields_data)): ?>
            <button type="button" class="button add_btn"><?php esc_html_e('Add Fields', 'woocommerce'); ?></button>
        <?php else: ?>
            <button type="button" class="button edit_field_btn"><?php esc_html_e('Edit Fields', 'woocommerce'); ?></button>
        <?php endif; ?>
        <div id="fields_table" style="margin-top: 20px;"></div>
        <!-- Popup Modal -->
        <div class="main-model" data-table-ref="fields_table">
            <button type="button" class="close_Btn" style="position: fixed;right: 0;top: 0;cursor: pointer;background-color: #2271b1;color:#fff;">✕</button>
            <h3>Custom Fields</h3>
            <div class="var-wrapper">
                <div style="display:flex;">
                    <select class="input_type" style="width: 60%;">
                        <option value="">Make choice...</option>
                        <option value="text">Input</option>
                        <option value="textarea">Textarea</option>
                        <option value="radio">Radio</option>
                        <option value="select">Select</option>
                    </select>
                    <button type="button" class="custom-btn add_input_btn">Add</button>
                    <button type="button" class="custom-btn save_field_btn">Save</button>
                </div>
                <!-- input wrapper-->
                <div class="input_wrapper" style="margin-top: 15px;"></div> 
                <input type="hidden" class="data_input" name="custom_fields_data" id="custom_fields_data" value="<?php echo esc_attr($custom_fields_data); ?>">
            </div>
        </div>
    </div>
    <?php
}

add_action('woocommerce_process_product_meta', 'save_custom_hidden_field');
function save_custom_hidden_field($post_id) {
    
    if (!empty($_POST['custom_variants_data'])) {
        update_post_meta($post_id, 'custom_variants_data', wp_unslash($_POST['custom_variants_data']));
    }

    if (!empty($_POST['custom_fields_data'])) {
        update_post_meta($post_id, 'custom_fields_data', wp_unslash($_POST['custom_fields_data']));
    }

}


