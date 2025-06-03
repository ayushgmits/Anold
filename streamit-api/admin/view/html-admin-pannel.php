<?php 
if (!defined('ABSPATH')) {
    exit;
}
?>

<h5><?php esc_html_e('Streamit Plugin ' , 'streamit-api');  esc_html_e(' v ' . STREAMIT_API_VERSION, 'streamiy-api'); ?></h5>
<div class="panel-wrap" id="streamit_api_admin_options">

    <ul class="streamit-api-tabs">
        <?php foreach ($options_tabs as $key => $tab): ?>
            <li
                class="<?php echo esc_attr($key); ?>_options <?php echo esc_attr($key); ?>_tab <?php echo esc_attr(isset($tab['class']) ? implode(' ', (array) $tab['class']) : ''); ?>">
                <a href="#<?php echo esc_attr($tab['target']); ?>"><span><?php echo esc_html($tab['label']); ?></span></a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php
    do_action('streamit_api_admin_tabs_data');
    do_action('streamit_api_admin_tab_sections');
    ?>
</div>