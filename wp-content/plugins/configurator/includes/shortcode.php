<?php
// Enqueue Styles & Scripts
function configurator_enqueue_scripts() {
    wp_enqueue_style('configurator-style', plugins_url('../css/style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'configurator_enqueue_scripts');

// Shortcode for 5-Step Form
function configurator_shortcode() {
    ob_start();
?>
    <div class="configurator">
        <div class="conf-left-section">
            <div class="img-wrapper">
                <img id="selected_img" src="<?php echo CONFIGURATOR_URL."/images/featured-img.png";?>">
            </div>
        </div>
        <div class="conf-right-section">
            <div id="form-wrapper"></div>
            <div class="nav-buttons">
                <button id="prevBtn">Previous</button>
                <button id="nextBtn">Next</button>
            </div>
        </div>
        
</div>
    <?php
    return ob_get_clean();
}
add_shortcode('configurator', 'configurator_shortcode');

// Handle form submission
add_action('wp_ajax_configurator_form_submission', 'configurator_form_submission');
add_action('wp_ajax_nopriv_configurator_form_submission', 'configurator_form_submission');

function configurator_form_submission() {
    $submitted_data = [];
    $message = "Nieuwe aanvraag via de configurator:\n\n";
    // Loop through POST and exclude 'action'
    foreach ($_POST as $key => $value) {
        if ($key === 'action') continue;

        $label = ucwords(str_replace('_', ' ', sanitize_text_field($key)));
        $message .= "{$label}: " . sanitize_text_field($value) . "\n";
    }

    // Send email to admin
    $admin_email = get_option('admin_email');
    $subject = 'Nieuwe configurator aanvraag';
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    $sent = wp_mail($admin_email, $subject, $message, $headers);

    if ($sent) {
        wp_send_json_success(['message' => 'Form submitted and email sent to admin.']);
    } else {
        wp_send_json_error(['message' => 'Form submitted but email failed to send.']);
    }
}
