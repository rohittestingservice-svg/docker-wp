<?php
add_action('post_submitbox_misc_actions', 'add_sync_button_to_product_page');
function add_sync_button_to_product_page() {
    global $post;

    if ($post->post_type !== 'product') {
        return;
    }

    $sync_url = admin_url('admin-ajax.php?action=sync_product_from_external&product_id=' . $post->ID);
    echo '<div class="misc-pub-section">
        <a href="' . esc_url($sync_url) . '" class="button button-primary" id="sync-product-button" style="margin-top:10px;">Sync Product</a>
    </div>';

    // Optional: Include JS to show loader or success message
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#sync-product-button').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                button.text('Syncing...').prop('disabled', true);
                $.get(button.attr('href'), function(response) {
                    alert(response.data || 'Product synced!');
                    button.text('Sync Product').prop('disabled', false);
                });
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_sync_product_from_external', 'sync_product_from_external_source');
function sync_product_from_external_source() {
    $wp_product_id = absint($_GET['product_id']);
    if (!$wp_product_id || get_post_type($wp_product_id) !== 'product') {
        wp_send_json_error('Invalid product ID.');
    }

    $lspeed_product_id = get_post_meta($wp_product_id, 'lightspeed_product_id', true);
    if(!empty($lspeed_product_id)){
        $response = call_lightspeed_api("/catalog/$lspeed_product_id.json");

        update_products_details($response['product'], $wp_product_id); // Your custom import logic
        wp_send_json_success('Product synced successfully.');
    }
}