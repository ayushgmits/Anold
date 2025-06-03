<?php
if (!defined('ABSPATH')) {
    exit;
}

$field_controller = new streamit_api_field_controller();
?>


<div class="streamit_api_single_slider" rel="<?php echo esc_attr($i); ?>">

    <?php
    $field_controller->get_text_field(
        array(
            'key' => 'slider_title',
            'id' => '',
            'label' => esc_html__('Title', 'streamit-api'),
            'type' => 'text',
            'value' => isset($value['title']) ? $value['title'] : '',
        )
    );

    $field_controller->get_checkbox_field(
        array(
            'key' => 'view_all',
            'id' => '',
            'value' => isset($value['view_all']) ? $value['view_all'] : '',
            'label' => esc_html__('View All', 'streamit'),
            'description' => '',
        )
    );
    ?>
    <div class="streamit-api-slider-radio-section">
        <?php
        $field_controller->get_radio_field(
            array(
                'key' => 'select_type',
                'id' => 'select_type_' . esc_attr($i), // Unique ID for each slider
                'label' => esc_html__('Select Section', 'streamit-api'),
                'name' => 'select_type_' . esc_attr($i), // Unique name for each slider
                'value' => isset($value['select_type']) ? $value['select_type'] : 'selected',
                'options' => [
                    'genre' => esc_html__('Genres', 'streamit-api'),
                    'tag' => esc_html__('Tags', 'streamit-api'),
                    'filter' => esc_html__('Filter', 'streamit-api'),
                    'selected' => esc_html__('Selected', 'streamit-api'),
                ],
                'required' => true
            )
        );
        ?>
    </div>

    <div id="conditional-fields">
        <?php

        $field_controller->get_select_field(array(
            'key'    => 'slider_genres',
            'id'     => '',
            'value'  => isset($value['genres']) ? $value['genres'] : '',
            'label'  => esc_html__('Genres', 'streamit-api'),
            'options' => streamit_get_all_genres($type),
            'multiple' => true
        ));

        $field_controller->get_select_field(array(
            'key'    => 'slider_tags',
            'id'     => '',
            'value'  => isset($value['tags']) ? $value['tags'] : '',
            'label'  => esc_html__('Tags', 'streamit-api'),
            'options' => streamit_get_all_tags($type),
            'multiple' => true
        ));

        $field_controller->get_select_field(array(
            'key'    => 'slider_filter_by',
            'id'     => '',
            'value'  => isset($value['filterBy']) ? $value['filterBy'] : '',
            'label'  => esc_html__('Filter By', 'streamit-api'),
            'options' => streamit_api_get_filter_list()
        ));

        $field_controller->get_select_field(array(
            'key'    => 'slider_datas',
            'id'     => '',
            'value'  => isset($value['datas']) ? $value['datas'] : '',
            'label'  => esc_html__('Movie / TV Show / Video / Live Channels', 'streamit-api'),
            'options' => streamit_api_get_all_data($type),
            'multiple' => true
        ));


        ?>
    </div>
</div>