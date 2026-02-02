<?php
    if ( ! defined( 'ABSPATH' ) ) exit;

    add_action( 'wp_enqueue_scripts', 'step_builder_enqueue_scripts' ); 
    add_action( 'admin_enqueue_scripts', 'step_builder_enqueue_scripts' );
    function step_builder_enqueue_scripts( $hook ) {
        wp_enqueue_script(
            'step-builder',
            plugins_url('assets/js/step-builder.js', WC_GLASS_PRODUCT_FILE),
            [],
            '1.0',
            true
        );

        wp_localize_script( 'step-builder', 'wc_step_vars', [
            'placeholder' => wc_placeholder_img_src(),
        ]);
    }

    /**
     * Add custom fields for Glass Product type in admin
     */
    add_action('woocommerce_product_options_general_product_data', function () {
        global $post;

        // Only show for glass_product type
        $product = wc_get_product($post->ID);
        if ($product && $product->get_type() !== 'glass_product') return;

        echo '<div class="options_group show_if_glass_product">';

        // Glass Quantity Field
        woocommerce_wp_text_input([
            'id' => '_glass_quantity',
            'label' => __('Glass Quantity', 'wc-glass-product'),
            'placeholder' => 'e.g. 3',
            'desc_tip' => true,
            'description' => __('Enter the number of glass panels included in this product.', 'wc-glass-product'),
            'type' => 'number',
            'custom_attributes' => [
                'min' => '1',
                'step' => '1'
            ]
        ]);

        // Width Field
        woocommerce_wp_text_input([
            'id' => '_glass_width',
            'label' => __('Glass Width (mm)', 'wc-glass-product'),
            'placeholder' => 'e.g. 3000',
            'desc_tip' => true,
            'description' => __('Enter the standard width of this glass product in millimeters.', 'wc-glass-product'),
            'type' => 'number',
            'custom_attributes' => [
                'min' => '1',
                'step' => '1'
            ]
        ]);
        
        echo '</div>';
    });

    /**
     * Save Glass products custom fields
     */
    add_action('woocommerce_admin_process_product_object', function ($product) {
        if ($product->get_type() !== 'glass_product') return;

        if (isset($_POST['_glass_quantity'])) {
            $product->update_meta_data('_glass_quantity', sanitize_text_field($_POST['_glass_quantity']));
        }

        if (isset($_POST['_glass_width'])) {
            $product->update_meta_data('_glass_width', sanitize_text_field($_POST['_glass_width']));
        }

        if (isset($_POST['steps'])) {
            $steps = wc_clean(wp_unslash($_POST['steps']));
            $product->update_meta_data('glass_option_data', $steps);
        }
    });

    /**
     * Add the tab for step product
     */
    add_filter('woocommerce_product_data_tabs', function($tabs) {
        $tabs['glass_product_tab'] = array(
            'label'    => __('Glass Options', 'wc-step-product'),
            'target'   => 'glass_product_data', // this should match the div ID below
            'class'    => array('show_if_glass_product'),
            'priority' => 80,
        );
        return $tabs;
    });

    add_action('woocommerce_product_data_panels', function() {
        global $post;

        $steps = get_post_meta($post->ID, 'glass_option_data', true);
        if (empty($steps)) $steps = [];
        ?>
        <div id="glass_product_data" class="panel woocommerce_options_panel hidden">
            <div id="step-product-builder">
                <div class="steps-top">
                    <button type="button" class="button add-step">+ Add Steps</button>
                </div>

                <div class="steps-container">
                    <?php foreach ($steps as $sIndex => $step):
                        if (!isset($sIndex)) $sIndex = 0;
                        if (!isset($step)) $step = [];
                        $title = $step['title'] ?? '';
                        $options = $step['options'] ?? [];
                        ?>

                        <div class="step-block" data-step-index="<?= esc_attr($sIndex); ?>">
                            <div class="st-form-row">
                                <div class="st-input-group">
                                    <input class="input" type="text" 
                                            name="steps[<?= $sIndex; ?>][title]" 
                                            placeholder="Enter step title" 
                                            value="<?= esc_attr($title); ?>">
                                </div>
                                <div class="st-actions">
                                    <a href="javascript:void(0)" class="remove-step">Remove</a>
                                    <a href="javascript:void(0)" class="edit-step">Edit</a>
                                </div>
                            </div>

                            <div class="options-container">
                                <?php if (!empty($options)): ?>
                                    <?php foreach ($options as $oIndex => $opt):
                                    ?>
                                        <div class="option-block">
                                            <input type="text" name="steps[<?= $sIndex; ?>][options][<?= $oIndex; ?>][title]" 
                                                placeholder="Option title" 
                                                value="<?= esc_attr($opt['title']); ?>" />
                                            <input type="number" step="0.01" min="1" 
                                                name="steps[<?= $sIndex; ?>][options][<?= $oIndex; ?>][price]" 
                                                placeholder="Price" 
                                                value="<?= esc_attr($opt['price']); ?>" />
                                            <?= ($oIndex == 0)
                                                ? '<button type="button" class="button add-option">+</button>'
                                                : '<button type="button" class="button remove-option">-</button>'; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="option-block">
                                        <input type="text" name="steps[<?= $sIndex; ?>][options][0][title]" placeholder="Option title" />
                                        <input type="number" step="0.01" min="1" name="steps[<?= $sIndex; ?>][options][0][price]" placeholder="Price" />
                                        <button type="button" class="button add-option">+</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <hr>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <style>
            #step-product-builder { padding: 10px;}
            #step-product-builder input[type=text] {width: 40%;}
            .steps-top { padding-bottom: 10px;}
            .st-form-row { display: flex; align-items: center; margin-bottom: 10px; }
            .st-input-group { flex:1; }
            .option-block { display:flex; gap:10px; margin-bottom:5px; flex-wrap:wrap; align-items:center; }
            .option-block input, .option-block select { flex-shrink:0; }
        </style>
        <?php
    });
    
    add_action('save_post_product', function($post_id) {
        // print_r($post_id);die;
        // if (!isset($_POST['glass_product_meta_nonce']) || !wp_verify_nonce($_POST['glass_product_meta_nonce'], 'save_glass_product_meta')) {
        //     return;
        // }
  
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        if (isset($_POST['steps'])) {
            // print_r($_POST['steps']);die;
            update_post_meta($post_id, 'glass_option_data', $_POST['steps']);
        } else {
            delete_post_meta($post_id, 'glass_option_data');
        }
    });


    // Add the 'show_if_glass_product' class to the general tab.
    add_filter('woocommerce_product_data_tabs', function ($tabs) {
        $tabs['general']['class'][] = 'show_if_glass_product';
        $tabs['attribute']['class'][] = 'show_if_glass_product';
        return $tabs;
    });

    add_action('admin_footer', function () {
        global $post, $typenow;

        if ($typenow !== 'product') return;

        wc_enqueue_js(
            "
            jQuery(document).ready(function($) {
                // Add the class to enable visibility for our custom product type
                //$('.options_group.pricing');
                //$('.product_data_tabs .general_tab, .product_data_tabs .attribute_tab, .product_data_tabs .variations_tab').addClass('show_if_glass_product');
                
                // Handle switching between product types
                $('select#product-type').on('change', function () {
                    var product_type = $(this).val();
                    if (product_type === 'glass_product') {
                        // Show all relevant standard options for our custom type
                        $('.options_group.pricing').show();
                    }
                }).trigger('change');
            });
            "
        );
    });



    


