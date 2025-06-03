<?php 
if (!defined('ABSPATH')) {
    exit;
}
?>

<h1><?php esc_html_e('App Options' , 'streamit-api');?></h1>
<div class="panel-wrap" id="streamit_api_admin_settings">

    <ul class="streamit-api-options-tabs">
        <?php foreach ($tabs as $key => $tab): ?>
            <li
                class="<?php echo esc_attr($key); ?>_options <?php echo esc_attr($key); ?>_tab <?php echo esc_attr(isset($tab['class']) ? implode(' ', (array) $tab['class']) : ''); ?>">
                <a href="#<?php echo esc_attr($tab['target']); ?>"><span><?php echo esc_html($tab['label']); ?></span></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php
    do_action('streamit_api_admin_options_data');
    do_action('streamit_api_admin_options_sections');
    ?>
</div>