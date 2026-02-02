<?php
defined('ABSPATH') || exit;

get_header('shop');

$product_id = get_the_ID();
$product    = wc_get_product($product_id);

if (!$product || $product->get_type() !== 'custom_composite') {
    wc_get_template_part('content', 'single-product');
    get_footer('shop');
    return;
}
?>

<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

    <?php do_action('woocommerce_before_single_product'); ?>

    <div class="product">

        <!-- LEFT: PRODUCT IMAGES -->
        <div class="woocommerce-product-gallery">
            <?php
            /**
             * Hook: woocommerce_before_single_product_summary
             *
             * @hooked woocommerce_show_product_images - 20
             */
            do_action('woocommerce_before_single_product_summary');
            ?>
        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="summary entry-summary">

            <?php
            /**
             * Hook: woocommerce_single_product_summary
             *
             * @hooked woocommerce_template_single_title - 5
             * @hooked woocommerce_template_single_rating - 10
             * @hooked woocommerce_template_single_price - 10
             * @hooked woocommerce_template_single_excerpt - 20
             * @hooked woocommerce_template_single_add_to_cart - 30
             * @hooked woocommerce_template_single_meta - 40
             */
            do_action('woocommerce_single_product_summary');
            ?>

            <!-- 🔥 COMPOSITE PRODUCTS UI -->
            <?php
            $components = (array) get_post_meta($product_id, '_composite_components', true);
            if (!empty($components)) :
            ?>
                <form class="cart composite-cart" method="post">
                <?php 
                    $count = 1;

                    foreach ($components as $component_id) :

                        $child = wc_get_product($component_id);
                        if (!$child) continue;

                        $image_id  = $child->get_image_id();
                        $image_url = $image_id
                            ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail')
                            : wc_placeholder_img_src();

                        $short_desc = $child->get_short_description();?>
                        <div class="wrapper composite-item" data-product-id="<?php echo esc_attr($component_id); ?>">

                            <div class="main">
                                <div class="count">
                                    <?php echo esc_html($count++); ?>
                                </div>

                                <div class="product_img">
                                    <img src="<?php echo esc_url($image_url); ?>"
                                        alt="<?php echo esc_attr($child->get_name()); ?>">
                                </div>

                                <div class="product-details">
                                    <h4 class="product-title">
                                        <?php echo esc_html($child->get_name()); ?>
                                    </h4>

                                    <?php if ($short_desc) : ?>
                                        <div class="product-desc">
                                            <?php echo wp_kses_post(wpautop($short_desc)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="toggle">
                                    <!-- Placeholder for options toggle -->
                                    <button type="button" class="toggle-options">
                                        <?php esc_html_e('Options', 'wc'); ?>
                                    </button>
                                </div>

                            </div><!-- /.main -->
                            <div class="option-wrapper">
                                <div class="options">
                                    <!-- Dynamic options / variations / extras can go here -->
                                </div>

                                <div class="price">
                                    <?php echo wp_kses_post($child->get_price_html()); ?>
                                </div>

                                <!-- Hidden inputs for cart -->
                                <input type="hidden" name="composite_children[]"
                                    value="<?php echo esc_attr($component_id); ?>">

                                <input type="number"
                                    name="composite_qty[<?php echo esc_attr($component_id); ?>]"
                                    value="1"
                                    min="1"
                                    class="composite-qty">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <?php
    /**
     * Hook: woocommerce_after_single_product_summary
     *
     * @hooked woocommerce_output_product_data_tabs - 10
     * @hooked woocommerce_upsell_display - 15
     * @hooked woocommerce_output_related_products - 20
     */
    do_action('woocommerce_after_single_product_summary');
    ?>

</div>

<?php
do_action('woocommerce_after_single_product');
get_footer('shop');
