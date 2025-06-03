<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required field values are set
$field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
$field['value'] = isset($field['value']) ? $field['value'] : '';
$field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
$field['checked'] = !empty($field['value']) && ($field['value'] == 'true') ? 'checked' : '';
$field['label'] = isset($field['label']) ? $field['label'] : '';
?>
<div id="<?php echo esc_attr($field['key']); ?>_field" class="form-field <?php echo esc_attr($field['key']); ?>_field <?php echo esc_attr($field['wrapper_class']); ?>">
    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo wp_kses_post($field['label']); ?></label>
        <input 
            type="checkbox"
            name="<?php echo esc_attr($field['name']); ?>"
            id="<?php echo esc_attr($field['key']); ?>"
            <?php echo esc_attr($field['checked']); ?> />
</div>
