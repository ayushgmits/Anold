<?php
if (!defined('ABSPATH')) {
    exit;
}
$field_controller = new streamit_api_field_controller();
?>

<div id="admin_options_general" class="streamit_admin_setting_pannel">
<?php
    $field_controller->get_checkbox_field(
        array(
            'key' => 'show_titles',
            'id' => '',
            'value' => isset($value['show_titles']) ? $value['show_titles'] : '',
            'label' => esc_html__('Display titles', 'streamit-api'),
            'description' => '',
        )
    );

    $field_controller->get_text_field(
        array(
            'key'   => 'client_email',
            'id'    => '',
            'label' => esc_html__('Client Email', 'streamit-api'),
            'type'  => 'text',
            'value' => isset($value['client_email']) ? $value['client_email'] : '',
            'description' => esc_html__('Add your FireBase Client email' , 'streamit-api')
        )
    );

    $field_controller->get_text_field(
        array(
            'key'   => 'private_key',
            'id'    => '',
            'label' => esc_html__('Client Email', 'streamit-api'),
            'type'  => 'text',
            'value' => isset($value['private_key']) ? $value['private_key'] : '',
            'description' => esc_html__('Add your FireBase Private key' , 'streamit-api')
        )
    );

    $field_controller->get_text_field(
        array(
            'key'   => 'project_id',
            'id'    => '',
            'label' => esc_html__('Client Email', 'streamit-api'),
            'type'  => 'text',
            'value' => isset($value['project_id']) ? $value['project_id'] : '',
            'description' => esc_html__('Add your FireBase Project id' , 'streamit-api')
        )
    );

    $field_controller->get_text_field(
        array(
            'key'   => 'app_name',
            'id'    => '',
            'label' => esc_html__('Application Name', 'streamit-api'),
            'type'  => 'text',
            'value' => isset($value['app_name']) ? $value['app_name'] : '',
            'description' => esc_html__('Add your Application Name' , 'streamit-api')
        )
    );
    ?>
</div>