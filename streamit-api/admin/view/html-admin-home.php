<?php
if (!defined('ABSPATH')) {
    exit;
}
$field_controller = new streamit_api_field_controller();
$home_options = get_option('streamit_api_home');
?>
<div id="admin_home_tab" class="streamit_admin_option_pannel">
    <form name="st-admin-option-dashboard" id="st-admin-option-dashboard" enctype="multipart/form-data">
        <h5>
            <?php echo esc_html__('Home Banner', 'streamit-api') ?>
        </h5>
        <div class="streamit_api_banner_section">
            <div class="select_banner_options">
                <?php
                $field_controller->get_select_field(array(
                    'key' => 'banner_select',
                    'id'  => '',
                    'value' => '',
                    'label' => '',
                    'options' => array(
                        'movie' => esc_html__('Movie', 'streamit-api'),
                        'video' => esc_html__('Video', 'streamit-api'),
                        'tvshow' => esc_html__('TV Show', 'streamit-api'),
                    ),
                ));

                $field_controller->get_button_field(array(
                    'key' => 'streamit_api_banner_select',
                    'label' => esc_html__('Add Banner', 'streamit-api'),
                    'button_text' => esc_html__('Add New', 'streamit-api'),
                    'class' => '',
                    'type' => 'button',
                ));
                ?>
            </div>

            <div class="streamit_api_banner_sections" id="streamit_api_banner_sections">
                <?php
                if (isset($home_options['banners']) && !empty($home_options['banners']) && is_array($home_options['banners'])) {
                    $i = 0;
                    foreach ($home_options['banners'] as $value) {
                        require STREAMIT_API_DIR . 'admin/view/banners/html-admin-banner.php';
                        $i++;
                    }
                }
                ?>
            </div>
        </div>

        <div class="streamit_api_slider_section">
            <div class="" id="streamit_api_slider_list">
                <?php
                $type= '';
                $i = 0;
                if (isset($home_options['sliders']) && !empty($home_options['sliders']) && is_array($home_options['sliders'])) {
                    foreach ($home_options['sliders'] as $value) {
                        require STREAMIT_API_DIR . 'admin/view/sliders/html-admin-slider.php';
                        $i++;
                    }
                } else {
                    require STREAMIT_API_DIR . 'admin/view/sliders/html-admin-slider.php';
                }
                ?>
            </div>

            <?php
            $field_controller->get_button_field(array(
                'key' => 'streamit_api_add_slider',
                'label' => esc_html__('Add Slider', 'streamit-api'),
                'button_text' => esc_html__('Add New', 'streamit-api'),
                'class' => '',
                'type' => 'button',
            ));
            ?>
        </div>

        <?php
        $field_controller->get_button_field(array(
            'key' => 'streamit_api_dashbord_submit',
            'label' => '',
            'button_text' => esc_html__('Submit', 'streamit-api'),
            'class' => '',
            'type' => 'submit',
        ));
        ?>
    </form>
</div>