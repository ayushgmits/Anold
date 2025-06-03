<?php
if (!defined('ABSPATH')) {
    exit;
}

$field = wp_parse_args($field, array(
    'class' => 'select short',
    'style' => '',
    'wrapper_class' => '',
    'name' => $field['key'],
    'id' => $field['key'],
    'desc_tip' => false,
    'custom_attributes' => array(),
    'multiple' => false // Set to true for multiple selection
));

// Define the wrapper attributes
$wrapper_attributes = array(
    'class' => $field['wrapper_class'] . " form-field {$field['key']}_field",
);

// Define the label attributes
$label_attributes = array(
    'for' => $field['key'],
);

// Initialize field attributes
$field_attributes = (array) $field['custom_attributes'];
$field_attributes['style'] = $field['style'];
$field_attributes['id'] = $field['key'];
$field_attributes['name'] = $field['name']; // Keep the name without `[]`

// Set the multiple attribute correctly
if ($field['multiple'] === true) {
    $field_attributes['multiple'] = 'multiple';
}

// Description handling
$description = !empty($field['description']) && false === $field['desc_tip'] ? $field['description'] : '';
?>

<div <?php echo $this->render_attributes($wrapper_attributes); ?>>
    <label <?php echo $this->render_attributes($label_attributes); ?>>
        <?php echo wp_kses_post($field['label']); ?>
    </label>
    <select class="streamit_api_select2" <?php echo $this->render_attributes($field_attributes); ?>>
        <?php foreach ($field['options'] as $option_key => $option_value) : ?>
            <option value="<?php echo esc_attr($option_key); ?>" <?php echo in_array($option_key, (array) $field['value']) ? 'selected' : ''; ?>>
                <?php echo esc_html($option_value); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($description) : ?>
        <p class="description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
</div>
