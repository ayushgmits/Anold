<?php
/**
 * Post Types Checkbox Template
 *
 * This template is used to render checkboxes for selecting which post types
 * should have the "Episode Meta Box" displayed.
 *
 * @package WP Episodes Manager
 */

 // Loop through the available post types and render a checkbox for each
foreach ($post_types as $post_type) {
    ?>

    <label>
        <input type="checkbox" name="stream_meta_box_post_types[]" value="<?php echo esc_attr($post_type->name); ?>" <?php echo (in_array($post_type->name, $selected_post_types) ? 'checked' : ''); ?> />
        <?php echo esc_html($post_type->labels->singular_name) . ' (' . esc_html($post_type->name) . ')'; ?>
    </label><br>

    
    <?php
}
?>