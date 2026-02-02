<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Custom product type: Glass Product
 */
class WC_Product_Glass extends WC_Product_Simple {

    public function __construct( $product ) {
        $this->product_type = 'glass_product';
        parent::__construct( $product );
    }

    public function get_type() {
        return 'glass_product';
    }

    public function is_purchasable() {
        return true;
    }

    public function is_visible() {
        return true;
    }

    public function is_in_stock() {
        return true;
    }

    public function get_price( $context = 'view' ) {
        $price = parent::get_price( $context );
        return $price ? $price : 0;
    }

    public function add_to_cart_text() {
        return __( 'Add to cart', 'wc-glass-product' );
    }

    public function add_to_cart_url() {
        return esc_url( add_query_arg( 'add-to-cart', $this->get_id() ) );
    }
}

/**
 * Register product type in WooCommerce dropdown
 */
add_filter( 'product_type_selector', function( $types ) {
    $types['glass_product'] = __( 'Glass Product', 'wc-glass-product' );
    return $types;
});

/**
 * Map product type to class
 */
add_filter( 'woocommerce_product_class', function( $classname, $product_type ) {
    if ( $product_type === 'glass_product' ) {
        $classname = 'WC_Product_Glass';
    }
    return $classname;
}, 10, 2);

/**
 * Display Add to Cart form for Glass Product
 */
add_action( 'woocommerce_single_product_summary', 'wc_glass_product_add_to_cart_area', 30 );
function wc_glass_product_add_to_cart_area() {
    global $product;

    if ( $product->get_type() === 'glass_product' ) {
        wc_get_template( 'single-product/add-to-cart/glass-product.php', array( 'product' => $product ) );
    }
}

/**
 * Enable support for key WooCommerce features
 */
add_filter( 'woocommerce_product_type_supports', function( $supports, $type ) {
    if ( $type === 'glass_product' ) {
        $supports = array_merge( $supports, [
            'ajax_add_to_cart',
            'editor',
            'thumbnail',
            'custom-fields',
            'comments',
            'taxes',
            'price',
            'sku',
            'stock',
            'shipping',
        ]);
    }
    return $supports;
}, 10, 2);

/**
 * Locate templates for Glass Product
 */
add_filter( 'woocommerce_locate_template', function( $template, $template_name, $template_path ) {
    $plugin_path = plugin_dir_path( WC_GLASS_PRODUCT_FILE ) . 'templates/';

    if ( file_exists( $plugin_path . $template_name ) ) {
        $template = $plugin_path . $template_name;
    }

    return $template;
}, 10, 3 );
