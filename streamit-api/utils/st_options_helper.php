<?php
function st_get_comment_settings()
{
    global $st_app_options, $streamit_options;

    if (is_streamit_theme_active()) {
        $settings = [
            "movie_comments"    => (int) (isset($streamit_options["streamit_movie_display_rating"]) && $streamit_options["streamit_movie_display_rating"] == "yes"),
            "tv_show_comments"  => (int) (isset($streamit_options["streamit_tvshow_display_rating"]) && $streamit_options["streamit_tvshow_display_rating"] == "yes"),
            "episode_comments"  => (int) (isset($streamit_options["streamit_episode_display_rating"]) && $streamit_options["streamit_episode_display_rating"] == "yes"),
            "video_comments"    => (int) (isset($streamit_options["streamit_video_display_rating"]) && $streamit_options["streamit_video_display_rating"] == "yes")
        ];

        return $settings;
    }

    $settings = [
        "movie_comments"    => (int) (isset($st_app_options["st_comments"]["movie_comments"])),
        "tv_show_comments"  => (int) (isset($st_app_options["st_comments"]["tv_show_comments"])),
        "episode_comments"  => (int) (isset($st_app_options["st_comments"]["episode_comments"])),
        "video_comments"    => (int) (isset($st_app_options["st_comments"]["video_comments"]))
    ];

    return $settings;
}
function st_get_limit_login_settings()
{
    global $st_app_options;

    return $st_app_options["st_device_lmit"] ?? false;
}
function st_options_text($args)
{
    $args = wp_parse_args(
        $args,
        [
            "attr-class"    => "",
            "attr-id"       => "",
        ]
    );

    $options = $args['data'];
    $path = str_replace("[", "['", $args['key']);
    $path = str_replace("]", "']", $path);

    try {
        eval("\$option_value = \$options$path;");
    } catch (Throwable $e) {
        $option_value = "";
    }
    echo '<input type="text" class="form-control ' . $args["attr-class"] . '" name="st_app_options' . $args['key'] . '" value="' . esc_attr($option_value) . '" />';
}

function st_options_number($args)
{
    $args = wp_parse_args(
        $args,
        [
            "attr-class"    => "",
            "attr-id"       => "",
            "default"       => 0
        ]
    );

    $options = $args['data'];
    $path = str_replace("[", "['", $args['key']);
    $path = str_replace("]", "']", $path);
    try {
        eval("\$option_value = \$options$path;");
    } catch (Throwable $e) {
        $option_value = "";
    }
    if (!$option_value || empty($option_value))
        $option_value = $args["default"];
    echo '<input type="number"  style="width:80px;" class="form-control ' . $args["attr-class"] . '" name="st_app_options' . $args['key'] . '" value="' . esc_attr($option_value) . '" />';
}

function st_options_checkbox($args)
{
    $args = wp_parse_args(
        $args,
        [
            "attr-class"    => "",
            "attr-id"       => "",
        ]
    );

    $options = $args['data'];
    $path = str_replace("[", "['", $args['key']);
    $path = str_replace("]", "']", $path);

    try {
        eval("\$option_value = \$options$path;");
    } catch (Throwable $e) {
        $option_value = "";
    }
    echo '<input type="checkbox" class="form-control ' . $args["attr-class"] . '" name="st_app_options' . $args['key'] . '" value="yes" ' . checked("yes", $option_value, false) . ' />';
}


function st_options_radio($args)
{
    $args = wp_parse_args(
        $args,
        [
            "attr-class"    => "",
            "attr-id"       => "",
        ]
    );

    $options = $args['data'];
    $path = str_replace("[", "['", $args['key']);
    $path = str_replace("]", "']", $path);

    try {
        eval("\$option_value = \$options$path;");
    } catch (Throwable $e) {
        $option_value = "";
    }

    if (isset($args["options"])) {
        foreach ($args["options"] as $value => $label) {
            echo '<div class="form-check form-check-inline ' . $args["attr-class"] . '">';
            echo "<input type='radio' class='form-check-input' name='st_app_options" . $args['key'] . "' value='" . esc_attr($value) . "' " . checked($option_value, $value, false) . " /> ";
            echo '<label class="form-check-label" for="">';
            echo $label;
            echo '</label>';
            echo "</div>";
        }
        echo '<p style="font-weight:normal;white-space:nowrap">' . $args['desc'] ?? '' . '</>';
    }
}

function st_options_switch($args)
{
    $args = wp_parse_args(
        $args,
        [
            "attr-class"    => "",
            "attr-id"       => "",
        ]
    );

    $options = $args['data'];
    $path = str_replace("[", "['", $args['key']);
    $path = str_replace("]", "']", $path);
    $option_value = [];
    try {
        eval("\$option_value = \$options$path;");
    } catch (Throwable $e) {
        $option_value = "";
    }

    echo '<div class="custom-control custom-switch">';
    echo '<input type="checkbox" class="custom-control-input switch ' . $args["attr-class"] . '" id="' . $args["attr-id"] . '" name="st_app_options' . $args['key'] . '" value="yes" ' . checked("yes", $option_value, false) . ' />';
    echo '<label for="' . $args["attr-id"] . '" class="custom-control-label"></label>';
    echo "</div>";
}

function st_option_notice($args)
{
    echo "<div class=" . $args["class"] . ">" . $args["content"]  . "</div>";
}

function get_general_settings($st_app_options, $class)
{

    add_settings_section(
        'general_options_section',
        'General',
        '',
        'app_setting_options_page',
        [
            "before_section"    => "<div class='$class'>",
            "after_section"     => "</div>"
        ]
    );

    add_settings_field(
        'show_titles',
        'Display titles',
        'st_options_switch',
        'app_setting_options_page',
        'general_options_section',
        array(
            'key'        => '[st_general][show_titles]',
            'data'       => $st_app_options,
            'attr-id'    => 'general-show-title-switch'
        )
    );

    add_settings_field(
        'onesignal_devider',
        '',
        'st_option_notice',
        'app_setting_options_page',
        'general_options_section',
        [
            "content" => "<hr>"
        ]
    );
    add_settings_field(
        'onesignal_heading',
        "<h1>Firebase</h1> <p style='font-weight:normal;white-space:nowrap'>Manage settings related firebase Push Notifications</p>",
        'st_option_notice',
        'app_setting_options_page',
        'general_options_section',
    );
    add_settings_field(
        'firebase_client_email',
        "Client Email <p style='font-weight:normal;white-space:nowrap'>Add your FireBase Client email</p>",
        'st_options_text',
        'app_setting_options_page',
        'general_options_section',
        array(
            'key'        => '[firebase][client_email]',
            'data'       => $st_app_options
        )
    );
    add_settings_field(
        'firebase_private_key',
        "Private Key <p style='font-weight:normal;white-space:nowrap'>Add your FireBase Private key</p>",
        'st_options_text',
        'app_setting_options_page',
        'general_options_section',
        array(
            'key'        => '[firebase][private_key]',
            'data'       => $st_app_options
        )
    );
    add_settings_field(
        'firebase_project_id',
        "Project Id <p style='font-weight:normal;white-space:nowrap'>Add your FireBase Project id</p>",
        'st_options_text',
        'app_setting_options_page',
        'general_options_section',
        array(
            'key'        => '[firebase][project_id]',
            'data'       => $st_app_options
        )
    );
    add_settings_field(
        'firebase_app_name',
        "Application Name <p style='font-weight:normal;white-space:nowrap'>Add your Application Name</p>",
        'st_options_text',
        'app_setting_options_page',
        'general_options_section',
        array(
            'key'        => '[firebase][app_name]',
            'data'       => $st_app_options
        )
    );
}

function get_comment_settings($st_app_options, $class)
{
    if (is_streamit_theme_active() && $class != "d-none") {

        add_settings_section(
            'comment_options_notice_section',
            'Comment',
            'st_option_notice',
            'app_setting_options_page',
            [
                "content"   => "<p class='pt-3 pb-5'><big>You have dependent theme active. Mange this settings form <a target='_blank' href='http://localhost/wordpress/products/streamit/wp-admin/admin.php?page=_streamit_options&tab=21'>Theme option</a>.</big></p>",
            ],
        );

        $class = "d-none";
    }
    add_settings_section(
        'comment_options_section',
        'Comment',
        '',
        'app_setting_options_page',
        [
            "before_section"    => "<div class='$class'>",
            "after_section"     => "</div>"
        ]
    );

    add_settings_field(
        'movie_comments_enable',
        'Movies',
        'st_options_switch',
        'app_setting_options_page',
        'comment_options_section',
        array(
            'key'        => '[st_comments][movie_comments]',
            'data'       => $st_app_options,
            'attr-id'    => 'movie-comments-switch'
        )
    );

    add_settings_field(
        'tv_show_comments_enable',
        'Tv-Shows',
        'st_options_switch',
        'app_setting_options_page',
        'comment_options_section',
        array(
            'key'        => '[st_comments][tv_show_comments]',
            'data'       => $st_app_options,
            'attr-id'    => 'tv-show-comments-switch'
        )
    );

    add_settings_field(
        'episode_comments_enable',
        'Episodes',
        'st_options_switch',
        'app_setting_options_page',
        'comment_options_section',
        array(
            'key'        => '[st_comments][episode_comments]',
            'data'       => $st_app_options,
            'attr-id'    => 'episode-comments-switch'
        )
    );

    add_settings_field(
        'video_comments_enable',
        'Videos',
        'st_options_switch',
        'app_setting_options_page',
        'comment_options_section',
        array(
            'key'        => '[st_comments][video_comments]',
            'data'       => $st_app_options,
            'attr-id'    => 'video-comments-switch'
        )
    );
}

function get_device_limit_settings($st_app_options, $class)
{
    add_settings_section(
        'device_limit_options_section',
        'Device Limits<p style="font-weight:normal;white-space:nowrap">Set login limits per account</p>',
        '',
        'app_setting_options_page',
        [
            "before_section"    => "<div class='$class'>",
            "after_section"     => "</div>"
        ]
    );

    add_settings_field(
        'device_limit_enable',
        'Enable',
        'st_options_switch',
        'app_setting_options_page',
        'device_limit_options_section',
        array(
            'key'        => '[st_device_lmit][is_enable]',
            'data'       => $st_app_options,
            'attr-id'    => 'device-limit-switch'
        )
    );


    add_settings_field(
        'default_limit',
        'Default Limit',
        'st_options_number',
        'app_setting_options_page',
        'device_limit_options_section',
        array(
            'key'        => '[st_device_lmit][default_limit]',
            'data'       => $st_app_options,
            'default'    => 4
        )
    );

    $levels = st_pmpro_getAllLevels();

    if ($levels) {
        add_settings_field(
            'limit_plan_devider',
            '',
            'st_option_notice',
            'app_setting_options_page',
            'device_limit_options_section',
            [
                "content" => "<hr>"
            ]
        );
        add_settings_field(
            'limit_plan_heading',
            "Membership Plans <p style='font-weight:normal;white-space:nowrap'>Add device limits by membership plans</p>",
            'st_option_notice',
            'app_setting_options_page',
            'device_limit_options_section',
        );

        foreach ($levels as $level_id => $level) {
            add_settings_field(
                'limit_plan_' . $level_id,
                $level->name,
                'st_options_number',
                'app_setting_options_page',
                'device_limit_options_section',
                array(
                    'key'        => "[st_device_lmit][$level_id]",
                    'data'       => $st_app_options,
                )
            );
        }
    }
}

function get_st_pmp_settings($st_app_options, $class)
{
    add_settings_section(
        'st_pmp_options_section',
        'Membership<p style="font-weight:normal;white-space:nowrap">Set membership settings.</p>',
        '',
        'app_setting_options_page',
        [
            "before_section"    => "<div class='$class'>",
            "after_section"     => "</div>"
        ]
    );

    add_settings_field(
        'pmp_payment_type',
        'Payment types',
        'st_options_radio',
        'app_setting_options_page',
        'st_pmp_options_section',
        array(
            'key'       => '[st_pmp_options][payment_type]',
            'data'      => $st_app_options,
            'options'   => [
                '0' => 'Disable',
                '1' => 'Default',
                '2' => 'In-App Payment'
            ],
            'desc'      => 'Select payment type or disable it for membership.',
            'attr-class'   => 'st-pmp-payment-options'
        )
    );

    add_settings_field(
        'default_payment_instructions',
        '<div class="default-payment instructions" style="padding-left:10px;border:1px solid #3c434a;border-left:5px solid #3c434a;line-height:30px;">You can manage this settings from Memberships > Settings > <a href="' . admin_url('?page=pmpro-paymentsettings') . '" target="_blank">Payment Gateway & SSL</a></div>',
        'st_option_notice',
        'app_setting_options_page',
        'st_pmp_options_section'
    );

    $instructions = "<div class='in-app-payment instructions'>";
    $instructions .= "<div style='font-size:16px;'><h4>" . esc_html__('Instructions !', 'streamit-api') . "</h4></div>";
    $instructions .= "<p style='color:red;'>";
    $instructions .= esc_html__('In order for the in-app purchase feature to work,', 'streamit-api') . "<br>";
    $instructions .= esc_html__('- You must enter the `Entitlement ID` and one of the API keys according to your app.', 'streamit-api') . "<br>";
    $instructions .= esc_html__('- Enter both API keys if your app supports iOS and Android.', 'streamit-api') . "</p>";

    $instructions .= "<p style='padding-left:10px;border:1px solid #3c434a;border-left:5px solid #3c434a;line-height:30px;'>";
    $instructions .= "<a style='color:green;' href='https://www.revenuecat.com/docs/getting-started/entitlements#creating-an-entitlement'>" . esc_html("Click Here") . "</a>" . esc_html(" To know how to get `Entitlement ID`?") . "<br>";
    $instructions .= "<a style='color:green;' href='https://www.revenuecat.com/docs/welcome/authentication#obtaining-api-keys'>" . esc_html("Click Here") . "</a>" . esc_html(" To know how to get `Android & Apple` API keys?");
    $instructions .= "</p></div>";
    add_settings_field(
        'in_app_payment_instructions',
        $instructions,
        'st_option_notice',
        'app_setting_options_page',
        'st_pmp_options_section'
    );
    add_settings_field(
        'in_app_entitlement_id',
        'Entitlement ID',
        'st_options_text',
        'app_setting_options_page',
        'st_pmp_options_section',
        array(
            'key'        => '[st_pmp_options][in_app][entitlement_id]',
            'data'       => $st_app_options,
            'attr-class' => 'in-app-payment'
        )
    );
    add_settings_field(
        'in_app_google_api_key',
        'Google API key',
        'st_options_text',
        'app_setting_options_page',
        'st_pmp_options_section',
        array(
            'key'        => '[st_pmp_options][in_app][google_api_key]',
            'data'       => $st_app_options,
            'attr-class' => 'in-app-payment'
        )
    );
    add_settings_field(
        'in_app_apple_api_key',
        'Apple API key',
        'st_options_text',
        'app_setting_options_page',
        'st_pmp_options_section',
        array(
            'key'        => '[st_pmp_options][in_app][apple_api_key]',
            'data'       => $st_app_options,
            'attr-class' => 'in-app-payment'
        )
    );
}
