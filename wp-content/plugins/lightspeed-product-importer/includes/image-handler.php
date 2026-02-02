<?php
function generate_custom_filename($url) {
    $path = parse_url($url, PHP_URL_PATH); 
    $parts = explode('/', trim($path, '/'));

    $file_id = $parts[count($parts) - 2];
    $file_name = $parts[count($parts) - 1];

    return $file_id . '-' . $file_name;
}

function download_image_to_media_library($image_src, $product_id = 0) {
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    // Download image temporarily
    $tmp = download_url($image_src);
    if (is_wp_error($tmp)) {
        return false;
    }

    // Get image content hash (for de-duplication)
    $image_hash = sha1_file($tmp);
    if (!$image_hash) {
        @unlink($tmp);
        return false;
    }

    // Check if image with same hash already exists
    $existing_id = find_existing_attachment_by_hash($image_hash);
    if ($existing_id) {
        @unlink($tmp);
        return $existing_id;
    }

    // Get actual extension from file content
    $image_info = getimagesize($tmp);
    $extension = image_type_to_extension($image_info[2], false);
    
    // Create custom filename
    $base_file_name = generate_custom_filename($image_src);
    $file_name = pathinfo($base_file_name, PATHINFO_FILENAME) . '.' . $extension;

    // Prepare file array
    $file_array = [
        'name'     => $file_name,
        'tmp_name' => $tmp
    ];

    // Upload
    $attachment_id = media_handle_sideload($file_array, $product_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return false;
    }

    // Save hash to attachment meta
    update_post_meta($attachment_id, '_image_content_hash', $image_hash);

    // Set metadata
    $image_title = preg_replace('/\.[^.]+$/', '', $file_name);
    wp_update_post([
        'ID' => $attachment_id,
        'post_title' => $image_title,
        'post_excerpt' => $image_title,
        'post_content' => '',
    ]);
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $image_title);

    return $attachment_id;
}

function find_existing_attachment_by_hash($hash) {
    global $wpdb;

    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_image_content_hash' AND meta_value = %s LIMIT 1",
        $hash
    ));

    return $attachment_id ? intval($attachment_id) : false;
}