<?php
if (!defined('ABSPATH')) {
    exit;
}

// Ensure required field values are set
$field['name'] = isset($field['name']) ? $field['name'] : $field['id'];
$field['value'] = isset($field['value']) ? $field['value'] : '';
$field['wrapper_class'] = isset($field['wrapper_class']) ? $field['wrapper_class'] : '';
$field['placeholder'] = isset($field['placeholder']) ? $field['placeholder'] : ''; // Not needed for radio buttons
$field['description'] = isset($field['description']) ? $field['description'] : '';
$field['type'] = isset($field['type']) ? $field['type'] : 'radio'; // Default to 'radio'
$field['options'] = isset($field['options']) ? $field['options'] : []; // Radio button options
$required = (isset($field['required']) && ($field['required'] == true)) ? 'required' : '';
?>
<div id="<?php echo esc_attr($field['key']); ?>_field" class="form-field <?php echo esc_attr($field['key']); ?>_field <?php echo esc_attr($field['wrapper_class']); ?>">
    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo wp_kses_post($field['label']); ?></label>
    <div class="radio-group">
        <?php foreach ($field['options'] as $option_key => $option_value) : ?>
            <label>
                <input 
                    type="radio"
                    name="<?php echo esc_attr($field['name']); ?>"
                    id="<?php echo esc_attr($field['key'] . '_' . $option_key); ?>"
                    value="<?php echo esc_attr($option_key); ?>"
                    <?php checked($field['value'], $option_key); ?> 
                />
                <?php echo esc_html($option_value); ?>
            </label><br/>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($field['description'])) : ?>
        <p class="description"><?php echo esc_html($field['description']); ?></p>
    <?php endif; ?>
</div>
