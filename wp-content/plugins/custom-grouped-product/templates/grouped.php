<?php
defined('ABSPATH') || exit;

global $product;

$grouped_products = $product->get_children();

if (empty($grouped_products)) {
    return;
}

do_action('woocommerce_before_add_to_cart_form');
?>

<form class="cart grouped_form" method="post" enctype="multipart/form-data">
    <?php
    $count = 1;

    foreach ($grouped_products as $child_id) :

        $child = wc_get_product($child_id);
        if (!$child || !$child->is_purchasable()) {
            continue;
        }

        $image_id  = $child->get_image_id();
        $image_url = $image_id
            ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail')
            : wc_placeholder_img_src();
    ?>
        <div class="product-wrapper">

            <div class="product-top">

                <div class="count">
                    <?php echo esc_html($count++); ?>
                </div>

                <div class="product-img">
                    <img src="<?php echo esc_url($image_url); ?>"
                        alt="<?php echo esc_attr($child->get_name()); ?>">
                </div>

                <div class="product-info">
                    <strong class="product-title">
                        <?php echo esc_html($child->get_name()); ?>
                    </strong>

                    <?php if ($child->get_short_description()) : ?>
                        <div class="desc">
                            <?php echo wp_kses_post($child->get_short_description()); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="toggle">
                    <i class="svg-icon__chevron-down ms-auto"></i>
                </div>
            </div>
            
            <div class="product-options">
                <div class="option-wrapper">
                    <?php
                    // VARIABLE PRODUCT → show attribute dropdowns
                    if ($child->is_type('variable')) {

                        $attributes = $child->get_variation_attributes();

                        foreach ($attributes as $attribute_name => $options) {

                            $taxonomy = str_replace('attribute_', '', $attribute_name);
                            $label    = wc_attribute_label($taxonomy);
                            ?>
                            
                            <div class="variation-row">
                                <label><?php echo esc_html($label); ?></label>

                                <select name="variation[<?php echo esc_attr($child_id); ?>][<?php echo esc_attr($taxonomy); ?>]">
                                    <option value="">
                                        <?php echo esc_html__('Choose ' . $label, 'woocommerce'); ?>
                                    </option>

                                    <?php foreach ($options as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>">
                                            <?php echo esc_html($option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php
                        }

                    } else {
                        // SIMPLE PRODUCT → no options
                        echo '<span class="no-options">' . esc_html__('No options', 'woocommerce') . '</span>';
                    }
                    ?>
                </div>

                <div class="quantity-wrap">
                    <?php
                    woocommerce_quantity_input([
                        'input_name'  => 'quantity[' . $child_id . ']',
                        'min_value'   => 1,
                        'max_value'   => $child->get_max_purchase_quantity(),
                        'input_value' => 1,
                    ], $child);
                    ?>
                </div>

                <div class="price">
                    <?php echo wp_kses_post($child->get_price_html()); ?>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
                    
    <button type="submit"
            class="single_add_to_cart_button button alt">
        <?php esc_html_e('Add Selected Products to Cart', 'woocommerce'); ?>
    </button>

    <input type="hidden"
           name="add-to-cart"
           value="<?php echo esc_attr($product->get_id()); ?>">
</form>

<?php do_action('woocommerce_after_add_to_cart_form'); ?>
