<?php
if (!defined('ABSPATH')) {
    exit;
}
$field_controller = new streamit_api_field_controller();
$video_options = get_option('streamit_api_video'); // Changed to video options
?>
<div id="admin_video_tab" class="streamit_admin_option_pannel">
    <form name="st-admin-option-dashboard-video" id="st-admin-option-dashboard-video" enctype="multipart/form-data">
        <h5>
            <?php echo esc_html__('Video Banner', 'streamit-api'); ?>
        </h5>
        <div class="streamit_api_banner_section">
            <div class="select_banner_options">
                <?php
                $field_controller->get_button_field(array(
                    'key' => 'streamit_api_banner_select_video', // Changed key to video
                    'label' => esc_html__('Add Banner', 'streamit-api'),
                    'button_text' => esc_html__('Add New', 'streamit-api'),
                    'class' => '',
                    'type' => 'button',
                ));
                ?>
            </div>

            <div class="streamit_api_banner_sections" id="streamit_api_banner_sections">
                <?php
                if (isset($video_options['banners']) && !empty($video_options['banners']) && is_array($video_options['banners'])) {
                    $i = 0;
                    foreach ($video_options['banners'] as $value) {
                        require STREAMIT_API_DIR . 'admin/view/banners/html-admin-banner.php'; // Keep this as is
                        $i++;
                    }
                }
                ?>
            </div>
        </div>

        <div class="streamit_api_slider_section">
            <div class="" id="streamit_api_slider_list">
                <?php
                $i = 0;
                $type = 'video';
                if (isset($video_options['sliders']) && !empty($video_options['sliders']) && is_array($video_options['sliders'])) {
                    foreach ($video_options['sliders'] as $value) {
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
                'key' => 'streamit_api_add_slider_video',
                'label' => esc_html__('Add Slider', 'streamit-api'),
                'button_text' => esc_html__('Add New', 'streamit-api'),
                'class' => '',
                'type' => 'button',
            ));
            ?>
        </div>

        <?php
        $field_controller->get_button_field(array(
            'key' => 'streamit_api_dashboard_submit',
            'label' => '',
            'button_text' => esc_html__('Submit', 'streamit-api'),
            'class' => '',
            'type' => 'submit',
        ));
        ?>
    </form>
</div>
