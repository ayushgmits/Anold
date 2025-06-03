<?php
if (!defined('ABSPATH')) {
    exit;
}

$field = wp_parse_args($field, array(
    'class' => 'button',
    'style' => '',
    'wrapper_class' => '',
    'name' => $field['key'],
    'id' => $field['key'],
    'desc_tip' => false,
    'custom_attributes' => array(),
    'type' => 'button',
    'button_text' => esc_html__('Submit' , 'streamit-api'),
));

// Wrapper attributes
$wrapper_attributes = array(
    'class' => $field['wrapper_class'] . " form-field {$field['key']}_field",
);

// Label attributes
$label_attributes = array(
    'for' => $field['key'],
);

// Field attributes
$field_attributes = (array) $field['custom_attributes'];
$field_attributes['style'] = $field['style'];
$field_attributes['id'] = $field['key'];
$field_attributes['name'] = $field['name'];
$field_attributes['type'] = $field['type']; // e.g., button, submit, reset
$field_attributes['class'] = $field['class']; // Additional classes

// Description handling
$description = !empty($field['description']) && false === $field['desc_tip'] ? $field['description'] : '';
?>

<div <?php echo $this->render_attributes($wrapper_attributes); ?>>
    <label <?php echo $this->render_attributes($label_attributes); ?>>
        <?php echo wp_kses_post($field['label']); ?>
    </label>
    <button <?php echo $this->render_attributes($field_attributes); ?>>
        <?php echo esc_html($field['button_text']); ?>
    </button>
    <?php if ($description) : ?>
        <p class="description"><?php echo esc_html($description); ?></p>
    <?php endif; ?>
</div>
