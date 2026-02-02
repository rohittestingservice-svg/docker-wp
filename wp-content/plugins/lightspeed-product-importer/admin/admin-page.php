<?php
// 1. Add submenu under WooCommerce
add_action('admin_menu', function () {
    add_submenu_page(
        'woocommerce',            // Parent slug
        'Product_syuc Import',    // Page title
        'Product_syuc',           // Menu title
        'manage_woocommerce',     // Capability
        'product_syuc_import',    // Menu slug
        'product_syuc_import_page'// Callback
    );
});

// 2. Render the page with a button
function product_syuc_import_page() {
    ?>
    <div class="wrap">
        <h1>Product_syuc Import</h1>
        <form method="post">
            <?php wp_nonce_field('product_syuc_import_action', 'product_syuc_import_nonce'); ?>
            <div class="input-block">
                <input type="checkbox" name="import_all" id="import_all" value='1'>
                <label for="import_all">Import All details</label>
            </div>
            <input type="submit" name="product_syuc_import" class="button button-primary" value="Run Import">
        </form>
    </div>
    <?php
}

// 3. Handle the import on form submission
add_action('admin_init', function () {
    if (isset($_POST['product_syuc_import']) && check_admin_referer('product_syuc_import_action', 'product_syuc_import_nonce')) {
        product_syuc_import_function();
    }
});

// 4. Define the import function
function product_syuc_import_function() {
    $import_all = isset($_POST['import_all']) ? true : false;
    
    // $product_data = call_lightspeed_api('/catalog.json',['limit'=>500, 'page'=>2]); //get all products
    
    //read the product json
    $jsonFilePath = LIGHTSPEED_IMPORTER_PLUGIN_PATH.'assets/products.json';
    $jsonString = file_get_contents($jsonFilePath);
    $product_data = json_decode($jsonString, true);
    if(!empty($product_data['products'])){
        foreach ($product_data['products'] as $key => $product) {
            import_product($product, $import_all);
        }
    }

    add_action('admin_notices', function () {
        echo '<div class="notice notice-success is-dismissible"><p>Product_syuc import completed successfully!</p></div>';
    });
}