<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Initialize the field controller object.
$field_controller = new streamit_api_field_controller();

// Get the banner type through the filter.
$select_type = apply_filters('streamit_api_add_banner', $value['type']);

// Default arguments for fetching items.
$args = array(
    'per_page' => -1,
);

$items = array(); // Initialize items array to prevent undefined variables.

// Determine the type and fetch the corresponding items.
if ('movie' === $value['type']) {
    $movies = function_exists('streamit_get_recommended_movies') ? streamit_get_recommended_movies($args) : esc_html__('Something went wrong.', 'streamit-api');
    $items = !is_wp_error($movies) && !empty($movies) ? $movies : array(esc_html__('No Data Found.', 'streamit-api'));
} elseif ('tvshow' === $value['type']) {
    $tvshows = function_exists('streamit_get_recommended_tvshows') ? streamit_get_recommended_tvshows($args) : esc_html__('Something went wrong.', 'streamit-api');
    $items = !is_wp_error($tvshows) && !empty($tvshows) ? $tvshows : array(esc_html__('No Data Found.', 'streamit-api'));
} elseif ('video' === $value['type']) {
    $videos = function_exists('streamit_get_recommended_videos') ? streamit_get_recommended_videos($args) : esc_html__('Something went wrong.', 'streamit-api');
    $items = !is_wp_error($videos) && !empty($videos) ? $videos : array(esc_html__('No Data Found.', 'streamit-api'));
} else {
    // Apply a custom filter if the type does not match predefined ones.
    $items = apply_filters('streamit_api_banner_item', $value['type'], $args);
}

// Provide a default message if no items were found or returned empty.
if (empty($items)) {
    $items = array(esc_html__('No Data Found.', 'streamit-api'));
}
?>

<div class="banner_display_list" rel="<?php echo esc_attr($i); ?>">
    <?php
    // Hidden field for banner type.
    $field_controller->get_text_field(
        array(
            'key'   => 'banner_type',
            'id'    => '',
            'label' => '',
            'type'  => 'hidden',
            'value' => esc_attr($value['type']), // Escaped output for safety.
        )
    );

    // Select field for selecting banner items.
    $select_key = 'banner_select_item_' . esc_attr($i);
    $field_controller->get_select_field(
        array(
            'key'     => $select_key,
            'id'      => '',
            'value'   => isset($value['selectItem']) ? esc_attr($value['selectItem']) : '',
            'label'   => esc_html__('Select Banner Item', 'streamit-api'),
            'options' => $items,
        )
    );

    // Media upload field for the banner image.
    $field_controller->get_upload_media_field(
        array(
            'key'             => 'banner_image',
            'id'              => '',
            'label'           => esc_html__('Banner Image', 'streamit-api'),
            'value'           => isset($value['image']) ? esc_attr($value['image']) : '',
            'upload_video_id' => 'streamit_api_upload_banner_image',
            'type'            => 'image',
            'remove_video_id' => 'streamit_api_remove_banner_image',
            'wrapper_class'   => 'streamit_api_single_banner_media',
            'description'     => esc_html__('Upload the banner image.', 'streamit-api'),
        )
    );
    ?>
</div>
