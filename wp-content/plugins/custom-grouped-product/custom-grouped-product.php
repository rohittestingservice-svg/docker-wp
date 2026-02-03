<?php
/**
 * Plugin Name: Custom Grouped Product Template
 * Description: Overrides WooCommerce grouped product template without creating new product type
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

class WC_Custom_Grouped_Product_Template {

    public function __construct() {

        // Override grouped product template
        add_filter(
            'woocommerce_locate_template',
            [$this, 'override_grouped_template'],
            10,
            3
        );

        // Enqueue frontend CSS
        add_action(
            'wp_enqueue_scripts',
            [$this, 'enqueue_assets']
        );
    }

    /**
     * Override grouped product add-to-cart template
     */
    public function override_grouped_template($template, $template_name, $template_path) {

        if ($template_name === 'single-product/add-to-cart/grouped.php') {

            $custom = plugin_dir_path(__FILE__) . 'templates/grouped.php';

            if (file_exists($custom)) {
                return $custom;
            }
        }

        return $template;
    }

    /**
     * Enqueue CSS only on grouped product page
     */
    public function enqueue_assets() {

        if (!is_product()) return;

        $product = wc_get_product(get_the_ID());

        if (!$product || $product->get_type() !== 'grouped') return;

        // ✅ CSS
        wp_enqueue_style(
            'custom-grouped-product-css',
            plugin_dir_url(__FILE__) . 'assets/css/grouped-product.css',
            [],
            '1.0'
        );

        // ✅ JS
        wp_enqueue_script(
            'custom-grouped-product-js',
            plugin_dir_url(__FILE__) . 'assets/js/grouped-product.js',
            ['jquery'],
            '1.0',
            true
        );
    }
}

new WC_Custom_Grouped_Product_Template();
