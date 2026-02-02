<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter('woocommerce_cart_item_name', function ($name, $cart_item, $cart_item_key) {
    $product_id = $cart_item['product_id'];

    // Create edit URL with cart item key
    $edit_url = add_query_arg([
        'edit_cart_item' => $cart_item_key
    ], get_permalink($product_id));

    $edit_link = '&nbsp;<a href="' . esc_url($edit_url) . '" class="button edit-cart-item" style="margin-top:5px;">Edit</a>';

    return $name . $edit_link;
}, 10, 3);


add_action('woocommerce_add_to_cart', function ($cart_item_key, $product_id, $quantity, $variation_id, $cart_item_data) {
    if (!empty($_POST['edit_cart_item_key'])) {
        $old_key = sanitize_text_field($_POST['edit_cart_item_key']);
        WC()->cart->remove_cart_item($old_key);
    }
}, 10, 5);

add_filter('woocommerce_add_to_cart_redirect', function ($url) {
    if (!empty($_POST['edit_cart_item_key'])) {
        return wc_get_cart_url();
    }
    return $url;
});

add_filter( 'woocommerce_get_item_data', function( $item_data, $cart_item ) {
    if ( ! empty( $cart_item['step_selections'] ) ) {
        foreach ( $cart_item['step_selections'] as $sel ) {
            $value  = isset($sel['value']) ? str_replace(['-', '_'] , ' ', $sel['value']) : '';
            if(!empty($sel['value'])){
                // Append the price to the value if it exists
                if(isset($sel['price']) && !empty($sel['price'])){
                    $value .= "&nbsp;[". wc_price($sel['price']) . "]";
                }
                $item_data[] = [
                    'name'  => esc_html( ucfirst($sel['title']) ),
                    'value' => ucfirst($value),
                ];
            }
        }
    }
    return $item_data;
}, 10, 2 );
/**
 * Adjust price dynamically from cart item data
 */
add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Loop through cart items
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

        // If custom price exists, override product price
        if ( isset( $cart_item['step_total_price'] ) ) {
            $cart_item['data']->set_price( (float) $cart_item['step_total_price'] );
        }
    }
});

add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {

    if (!isset($values['step_selections']) || empty($values['step_selections'])) {
        return;
    }

    foreach ($values['step_selections'] as $sel) {

        // Skip empty rows
        if (empty($sel['title']) || empty($sel['value'])) {
            continue;
        }

        $value = str_replace(['-', '_'], ' ', $sel['value']);

        // Add price if present
        if (!empty($sel['price'])) {
            $value .= " [" . wc_price($sel['price']) . "]";
        }

        // Add data to order item
        $item->add_meta_data(
            ucfirst($sel['title']),
            ucfirst($value),
            true
        );
    }
}, 10, 4);


