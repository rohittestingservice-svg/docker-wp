<?php
/**
 * Admin Settings Page - WooCommerce → Glass Product
 */
add_action('admin_menu', 'wc_glass_add_admin_menu');
function wc_glass_add_admin_menu() {
    add_submenu_page(
        'woocommerce',
        __('Glass Product Settings', 'wc-glass-product'),
        __('Glass Product', 'wc-glass-product'),
        'manage_woocommerce',
        'wc-glass-product',
        'wc_glass_settings_page_html'
    );
}

add_action('admin_init', 'wc_glass_register_settings');
function wc_glass_register_settings() {

    add_settings_section(
        'wc_glass_pricing_section',
        __('Glass Product Prices (Rails × Width)', 'wc-glass-product'),
        '__return_false',
        'wc-glass-product'
    );

    // Option fields
    $option_fields = [
        'handgreep'       => 'Handgreep',
        'tochstrips'      => 'Tochtstrips',
        'u_profielen'     => 'U-Profielen',
        'meenemers'       => 'Meenemers',
        'funderingsbalk'  => 'Funderingsbalk',
        'verlengde'       => 'Verlengde',
    ];

    // Rails & Width list
    $rails_list  = [2, 3, 4, 5, 6];
    $width_list  = [820, 900, 980, 1030];

    foreach ($rails_list as $rails) {

        add_settings_field(
            "wc_glass_section_{$rails}",
            "<h2>{$rails} Rails</h2>",
            function() use ($rails, $width_list, $option_fields) {

                echo "<table class='widefat striped' style='margin-bottom:20px;'>";

                // Header row
                echo "<thead><tr>";
                echo "<th>Rails</th><th>Width (mm)</th>";
                foreach ($option_fields as $label) {
                    echo "<th>{$label}</th>";
                }
                echo "</tr></thead><tbody>";

                // Width rows
                foreach ($width_list as $width) {

                    echo "<tr>";
                    echo "<td>{$rails}</td>";
                    echo "<td>{$width}</td>";

                    foreach ($option_fields as $key => $label) {

                        $option_key = "wc_glass_{$rails}_{$width}_{$key}";
                        $value = esc_attr(get_option($option_key, 0));

                        echo "<td><input type='number' 
                                         step='0.01'
                                         style='width:90px'
                                         name='{$option_key}' 
                                         value='{$value}'></td>";
                    }

                    echo "</tr>";
                }

                echo "</tbody></table>";
            },
            'wc-glass-product',
            'wc_glass_pricing_section'
        );

        // Register all options
        foreach ($width_list as $width) {
            foreach ($option_fields as $key => $label) {
                register_setting(
                    'wc_glass_settings_group',
                    "wc_glass_{$rails}_{$width}_{$key}",
                    [
                        'type'              => 'number',
                        'sanitize_callback' => 'floatval',
                        'default'           => 0
                    ]
                );
            }
        }
    }
}

/**
 * Admin Settings Page HTML
 */
function wc_glass_settings_page_html() {
    if (!current_user_can('manage_woocommerce')) return;
    ?>
    <div class="wrap">
        <h1><?php _e('WooCommerce Glass Product Settings', 'wc-glass-product'); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('wc_glass_settings_group');
            do_settings_sections('wc-glass-product');
            submit_button(__('Save Prices', 'wc-glass-product'));
            ?>
        </form>
    </div>
    <?php
}
