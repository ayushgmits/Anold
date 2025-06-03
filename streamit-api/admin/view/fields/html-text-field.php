<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required field values are set
$field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
$field['value'] = isset($field['value']) ? $field['value'] : '';
$field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
$field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : '';
$field['description'] = isset($field['description']) ? $field['description'] : '';
$field['type'] = isset($field['type']) ? $field['type'] : 'text';
$required = (isset($field['required']) && ($field['required'] == true)) ? 'required' : '';
?>
<div id="<?php echo esc_attr($field['key']); ?>_field" class="form-field <?php echo esc_attr($field['key']); ?>_field <?php echo esc_attr($field['wrapper_class']); ?>">
    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo wp_kses_post($field['label']); ?></label>
    <input 
        type="<?php echo esc_attr($field['type']); ?>"
        name="<?php echo esc_attr($field['name']); ?>"
        id="<?php echo esc_attr($field['key']); ?>"
        value="<?php echo esc_attr($field['value']); ?>"
        class="regular-text"
        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
         />
    <?php if (!empty($field['description'])) : ?>
        <p class="description"><?php echo esc_html($field['description']); ?></p>
    <?php endif; ?>
</div>
