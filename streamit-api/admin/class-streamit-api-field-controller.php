<?php
class streamit_api_field_controller
{
    public function get_select_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-select-field.php';
    }
	public function get_button_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-button-field.php';
    }
	public function get_upload_media_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-upload-media.php';
    }
	public function get_text_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-text-field.php';
    }
    public function get_checkbox_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-checkbox-field.php';
    }
    public function get_radio_field($field)
    {
        include STREAMIT_API_DIR . 'admin/view/fields/html-radio-field.php';
    }
    public function render_attributes($attributes)
	{
		$attr_string = '';
		foreach ($attributes as $key => $value) {
			if (is_bool($value)) {
				$attr_string .= $value ? sprintf('%s ', esc_attr($key)) : '';
			} else {
				$attr_string .= sprintf('%s="%s" ', esc_attr($key), esc_attr($value));
			}
		}
		return trim($attr_string);
	}
}