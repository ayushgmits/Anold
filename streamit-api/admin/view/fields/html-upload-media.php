<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required field values are set
$field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
$field['value'] = isset($field['value']) ? $field['value'] : '';
$field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
$field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : false;

?>
<div id="<?php echo esc_attr($field['key']); ?>_field" class="form-field media-attachment-video media-option <?php echo esc_attr($field['key']); ?>_field <?php echo esc_attr($field['wrapper_class']); ?>">
    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo wp_kses_post($field['label']); ?></label>
    <div id="<?php echo esc_attr($field['wrapper_class']); ?>" class="streamit_media_preview">
        <?php
        // Check if the field value is not empty
        if (!empty($field['value'])) {
            if (isset($field['type'])) {
                if ($field['type'] == 'image') {
                    $image_src = wp_get_attachment_url($field['value']);
                    if ($image_src) {
                        echo '<img style="max-width: 150px;" src="' . esc_url($image_src) . '" alt="Image" />';
                    }
                }
                elseif ($field['type'] == 'video') {
                    $video_src = wp_get_attachment_url($field['value']);
                    if ($video_src) {
                        echo '<video width="320" height="240" controls>';
                        echo '<source src="' . esc_url($video_src) . '" type="video/mp4">';
                        echo 'Your browser does not support the video tag.';
                        echo '</video>';
                    }
                }
            }
        }
        ?>

    </div>
    <input type="hidden" id="<?php echo esc_attr($field['key']); ?>" name="<?php echo esc_attr($field['name']); ?>" class="upload_video_id" value="<?php echo esc_attr($field['value']); ?>" />
    <a href="#" id="<?php echo esc_attr($field['upload_video_id']); ?>" class="button streamit_api_upload_video_button tips"><?php echo esc_html__('Upload/Add video', 'streamit-api'); ?></a>
    <a href="#" id="<?php echo esc_attr($field['remove_video_id']); ?>" class="button streamit_api_remove_video_button tips"><?php echo esc_html__('Remove this video', 'streamit-api'); ?></a>
</div>