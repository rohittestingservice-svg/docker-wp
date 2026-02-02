<?php
/**
 * Plugin Name: Custom Composite Product (Dynamic Dropdown)
 * Version: 1.4
 */

if (!defined('ABSPATH')) exit;

add_action('plugins_loaded', function () {

    if (!class_exists('WooCommerce')) return;

    /*----------------------------------
    | Product Class
    ----------------------------------*/
    class WC_Product_Custom_Composite extends WC_Product {
        public function get_type() {
            return 'custom_composite';
        }

        public function is_purchasable() {
            return true;
        }

        public function has_price() {
            return $this->get_price() !== '';
        }

        public function get_price($context = 'view') {
            return parent::get_price($context);
        }
        
        public function supports($feature) {
            $supports = [
                'ajax_add_to_cart',
                'add_to_cart',
                'price',
            ];

            if (in_array($feature, $supports, true)) {
                return true;
            }

            return parent::supports($feature);
        }
    }

    /*----------------------------------
    | Main Plugin Class
    ----------------------------------*/
    class WC_Custom_Composite_Product {

        public function __construct() {

            add_filter('product_type_selector', [$this, 'add_type']);
            add_action('admin_footer', [$this, 'enable_price']);
            add_filter('woocommerce_product_class', [$this, 'map_class'], 10, 2);
            add_filter('woocommerce_product_data_tabs', [$this, 'active_product_date_tab']);

            add_action('woocommerce_product_options_general_product_data', [$this, 'admin_ui']);
            add_action('woocommerce_process_product_meta', [$this, 'save_components']);

            add_action('wp_ajax_get_composite_products', [$this, 'ajax_products']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

            // FRONTEND ITEMS
            add_filter('template_include', [$this, 'get_woocommerce_template'], 99);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        }

        public function add_type($types) {
            $types['custom_composite'] = __('Composite Product', 'wc');
            return $types;
        }

        public function enable_price(){ ?>
            <script>
                jQuery(function ($) {
                    $('.options_group.pricing').addClass('show_if_custom_composite');
                });
            </script>
        <?php
        }

        public function map_class($class, $type) {
            return $type === 'custom_composite'
                ? 'WC_Product_Custom_Composite'
                : $class;
        }

        public function active_product_date_tab($tabs) {
            $tabs['general']['class'][] = 'show_if_custom_composite';
            return $tabs;
        }
        /*----------------------------------
        | Admin UI
        ----------------------------------*/
        public function admin_ui() {
            global $post;

            $saved = (array) get_post_meta($post->ID, '_composite_components', true);
            // print_r($saved);

            echo '<div class="options_group show_if_custom_composite">';
            echo '<p><strong>Composite Products</strong></p>';

            echo '<div id="composite-wrapper">';

            if (!empty($saved)) {
                foreach ($saved as $id) {
                    echo $this->component_row($id);
                }
            } else {
                echo $this->component_row();
            }

            echo '</div>';

            echo '<p><button type="button" class="button" id="add-component">➕ Add Product</button></p>';
            echo '</div>';
        }

        private function component_row($value = '') {
            return '
            <div class="composite-row">
                <select class="composite-select"
                        name="composite_components[]"
                        data-selected="' . esc_attr($value) . '">
                    <option value="">Loading...</option>
                </select>
                <button type="button" class="button remove-component">➖</button>
            </div>';
        }
        
        /*----------------------------------
        | Save
        ----------------------------------*/
        public function save_components($post_id) {
            if (isset($_POST['composite_components'])) {
                update_post_meta(
                    $post_id,
                    '_composite_components',
                    array_values(array_filter(array_map('intval', $_POST['composite_components'])))
                );
            }
        }

        /*----------------------------------
        | AJAX Products
        ----------------------------------*/
        public function ajax_products() {

            $exclude = isset($_POST['exclude']) ? array_map('intval', $_POST['exclude']) : [];

            // 🔥 exclude current (parent) product
            if (!empty($_POST['current_product_id'])) {
                $exclude[] = intval($_POST['current_product_id']);
            }

            $exclude = array_unique(array_filter($exclude));

            $products = wc_get_products([
                'status'  => 'publish',
                'limit'   => -1,
                'exclude' => $exclude,
            ]);

            $options = '<option value="">Select product</option>';

            foreach ($products as $product) {
                $options .= sprintf(
                    '<option value="%d">%s</option>',
                    $product->get_id(),
                    esc_html($product->get_name())
                );
            }

            wp_send_json_success($options);
        }
        
        /*----------------------------------
        | REGISTER ADMIN JS
        ----------------------------------*/
        public function enqueue_admin_assets($hook) {

            // Load only on product edit/add page
            if ($hook !== 'post.php' && $hook !== 'post-new.php') {
                return;
            }

            global $post;
            if (!$post || get_post_type($post) !== 'product') {
                return;
            }

            wp_enqueue_style(
                'wc-composite-admin-css',
                plugin_dir_url(__FILE__) . 'assets/css/composite-admin.css',
                [],
                '1.0'
            );
            
            wp_enqueue_script(
                'wc-composite-admin',
                plugin_dir_url(__FILE__) . 'assets/js/composite-admin.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_localize_script('wc-composite-admin', 'wcComposite', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'postId'  => get_the_ID(),
            ]);
        }

        /*----------------------------------
        | CUSTOM COMPOSITE PRODUCT TYPE TEMPLATE
        ----------------------------------*/
        public function get_woocommerce_template($template) {

            if (!is_singular('product')) {
                return $template;
            }

            $product_id = get_the_ID();
            if (!$product_id) {
                return $template;
            }

            $product = wc_get_product($product_id);

            if (!$product || $product->get_type() !== 'custom_composite') {
                return $template;
            }

            $custom_template = plugin_dir_path(__FILE__) . 'templates/single-product-custom-composite.php';

            if (file_exists($custom_template)) {
                return $custom_template;
            }

            return $template;
        }

        /*----------------------------------
        | CUSTOM COMPOSITE PRODUCT REGISTER CSS AND JS
        ----------------------------------*/
        public function enqueue_frontend_assets() {

            // Load only on single product page
            if (!is_product()) {
                return;
            }

            $product_id = get_the_ID();
            if (!$product_id) {
                return;
            }

            $product = wc_get_product($product_id);

            // Load ONLY for custom composite product
            if (!$product || $product->get_type() !== 'custom_composite') {
                return;
            }

            // ✅ CSS
            wp_enqueue_style(
                'wc-composite-frontend',
                plugin_dir_url(__FILE__) . 'assets/css/composite-product.css',
                [],
                '1.0'
            );

            // ✅ JS
            wp_enqueue_script(
                'wc-composite-frontend',
                plugin_dir_url(__FILE__) . 'assets/js/composite-product.js',
                ['jquery'],
                '1.0',
                true
            );
        }
    }
    
    new WC_Custom_Composite_Product();
});
