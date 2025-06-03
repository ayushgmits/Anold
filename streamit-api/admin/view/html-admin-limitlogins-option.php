<?php
if (!defined('ABSPATH')) {
    exit;
}
$field_controller = new streamit_api_field_controller();
?>

<div id="admin_options_limitslogin" class="streamit_admin_setting_pannel">
<h2><?php esc_html_e('Device Limits' , 'streamit-api'); ?></h2>
<h4><?php esc_html_e('Set login limits per account' , 'streamit-api'); ?></h4>

<?php 
$pmp_levels = function_exists('streamit_get_pricing_levels') ? streamit_get_pricing_levels() : '';
if(!empty($pmp_levels)){
foreach($pmp_levels as $level_id => $level_name){
    $field_controller->get_text_field(
        array(
            'key'   => $level_id,
            'id'    => '',
            'label' => esc_html($level_name),
            'type'  => 'number',
            'value' => isset($value[$level_id]) ? $value[$level_id] : '',
        )
    );
}
}
?>
</div>