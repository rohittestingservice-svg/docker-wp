<?php
/**
 * Plugin Name: WooCommerce Glass Product
 * Description: Adds a custom "Glass Product" type for WooCommerce — includes pricing, inventory, and custom configurator compatibility.
 * Author: Ocean
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wc-glass-product
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define a constant for the main plugin file (for enqueueing scripts/styles)
if ( ! defined( 'WC_GLASS_PRODUCT_FILE' ) ) {
    define( 'WC_GLASS_PRODUCT_FILE', __FILE__ );
}

/**
 * Check if WooCommerce is installed and active.
 * If not, disable this plugin and show an admin notice.
 */
function wc_glass_check_woocommerce_dependency() {
    // Check if WooCommerce is active
    if ( ! class_exists( 'WooCommerce' ) ) {
        // Deactivate this plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
        
        // Display a notice to the admin
        add_action( 'admin_notices', 'wc_glass_woocommerce_dependency_notice' );
    }
}
add_action( 'admin_init', 'wc_glass_check_woocommerce_dependency' );

/**
 * Display the admin notice for the WooCommerce dependency.
 */
function wc_glass_woocommerce_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <?php esc_html_e( 'The "WooCommerce Glass Product" plugin requires WooCommerce to be installed and active.', 'wc-glass-product' ); ?>
        </p>
    </div>
    <?php
}

// ---------------------------------------------
// Global Radio Options
// ---------------------------------------------
define('WC_GLASS_HANDGREEP_OPTIONS', [
    'handgreep' => [
        ['label' => 'Handgreep 1x', 'value' => 'ja 1x'],
        ['label' => 'Handgreep voor alle glasplaten', 'value' => 'ja'],
    ],
]);

// ---------------------------------------------
// Global Checkbox Options
// ---------------------------------------------
define('WC_GLASS_EXTRA_OPTIONS', [
    "tochstrips" => ['label' => 'Tochstrips', 'value' => 'ja'],
    "u_profielen" => ['label' => 'U-Profielen', 'value' => 'ja'],
    "meenemers" => ['label' => 'Meenemers', 'value' => 'ja'],
    "funderingsbalk" => ['label' => 'Funderingsbalk', 'value' => 'ja'],
    "verlengde" => ['label' => 'Verlengde', 'value' => 'ja']
]);


define('WC_DEFAULT_OPTIONS', [
    'pa_soort-glas'     => ['title' => 'Glas type', 'value' => '', 'price' => 0],
    'width'             => ['title' => 'Kaderbreedte', 'value' => '', 'price' => 0],
    'height'            => ['title' => 'Kaderhoogte', 'value' => '', 'price' => 0],
    'pa_kleur'          => ['title' => 'Profielkleur', 'value' => '', 'price' => 0],
    'panel'             => ['title' => 'Panel', 'value' => '', 'price' => 0],
    'overlapping'       => ['title' => 'Overlapping', 'value' => '', 'price' => 0],
    'handgreep'         => ['title' => 'Handgreep', 'value' => '', 'price' => 0],
    'tochstrips'        => ['title' => 'Tochtstrips', 'value' => '', 'price' => 0],
    'u_profielen'       => ['title' => 'U-Profielen', 'value' => '', 'price' => 0],
    'meenemers'         => ['title' => 'Meenemers', 'value' => '', 'price' => 0],
    'funderingsbalk'    => ['title' => 'Funderingsbalk', 'value' => '', 'price' => 0],
    'verlengde'         => ['title' => 'Verlengde', 'value' => '', 'price' => 0],
]);

// Load the plugin after WooCommerce is loaded
add_action( 'plugins_loaded', function() {

    // Only load if WooCommerce is active
    if ( class_exists( 'WooCommerce' ) ) {

        // --- Include the helper file first ---
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'helpers/wc-glass-product-helpers.php';
        
        // Admin core
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'admin/admin-meta-boxes.php';
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'admin/admin-price-settings.php';
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'admin/admin-products.php';

        // Include core plugin classes
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'includes/class-wc-product-glass.php';
        
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'includes/hooks-product-page.php';
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'includes/configurator.php';
        require_once plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . '/includes/cart-hooks.php'; 
    }

});








