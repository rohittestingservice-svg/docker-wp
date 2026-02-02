<?php
/**
 * Plugin Name: Configurator
 * Plugin URI: https://example.com
 * Description: A WooCommerce-dependent configurator plugin.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 * Text Domain: configurator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

define('CONFIGURATOR_URL', plugin_dir_url(__FILE__));

// Ensure WooCommerce is active
function configurator_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            __('This plugin requires WooCommerce to be installed and activated.', 'configurator'),
            'Plugin dependency check',
            ['back_link' => true]
        );
    }
}
register_activation_hook(__FILE__, 'configurator_check_woocommerce');

require_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

function load_plugin_scripts() {
    wp_enqueue_script(
        'configurator-script',
        plugin_dir_url(__FILE__) . 'js/script.js',
        [],
        false,
        true
    );

    // Pass the JSON URL to JS
    wp_localize_script('configurator-script', 'configurator', [
        'baseUrl' => plugin_dir_url(__FILE__),
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}
add_action('wp_enqueue_scripts', 'load_plugin_scripts');


