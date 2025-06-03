<?php

namespace Includes\CPT;

class iqonicCPTLiveTV
{
    function __construct()
    {
        add_action("init", [$this, "iqonic_register_cpt_tv_live"], 4);
        add_action('add_meta_boxes', [$this, 'iqonic_add_channels_meta_box']);
        add_action('save_post_live_tv', [$this, 'iqonic_save_channels_custom_meta_fields'], 10, 3);
    }
    public function iqonic_register_cpt_tv_live()
    {
        $labels = array(
            'name'                  => _x('Live TV', 'Post type general name', 'streamit-api'),
            'singular_name'         => _x('Live TV', 'Post type singular name', 'streamit-api'),
            'menu_name'             => _x('Live TV', 'Admin Menu text', 'streamit-api'),
            'name_admin_bar'        => _x('Live TV', 'Add New on Toolbar', 'streamit-api'),
            'add_new'               => __('Add New', 'streamit-api'),
            'add_new_item'          => __('Add New Channel', 'streamit-api'),
            'new_item'              => __('New Channel', 'streamit-api'),
            'edit_item'             => __('Edit Channel', 'streamit-api'),
            'view_item'             => __('View Channels', 'streamit-api'),
            'all_items'             => __('All Channels', 'streamit-api'),
            'search_items'          => __('Search Channels', 'streamit-api'),
            'parent_item_colon'     => __('Parent channel:', 'streamit-api'),
            'not_found'             => __('No channels found.', 'streamit-api'),
            'not_found_in_trash'    => __('No channels found in Trash.', 'streamit-api'),
            'featured_image'        => _x('Channel Thumbnail', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'streamit-api'),
            'set_featured_image'    => _x('Set Thumbnail', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'streamit-api'),
            'remove_featured_image' => _x('Remove Thumbnail', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'streamit-api'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'streamit-api'),
            'archives'              => _x('Channel archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'streamit-api'),
            'insert_into_item'      => _x('Insert into channel', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'streamit-api'),
            'uploaded_to_this_item' => _x('Uploaded to this channel', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'streamit-api'),
            'filter_items_list'     => _x('Filter channels list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'streamit-api'),
            'items_list_navigation' => _x('Channels list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'streamit-api'),
            'items_list'            => _x('Channels list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'streamit-api'),
        );

        $args = array(
            'labels'             => $labels,
            // 'public'             => true,
            // 'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            // 'menu_position'      => 2,
            'query_var'          => true,
            // 'rewrite'            => array('slug' => 'live-tv/channels'),
            'capability_type'    => 'post',
            // 'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-video-alt',
            // 'show_in_rest'       => true,
            // 'rest_base'          => "iqonic/live-tv/channels",
            'supports'           => array('title', 'author', 'thumbnail', 'editor', 'excerpt'),
        );

        $cat_labels = array(
            'name'                       => _x('Categories', 'taxonomy general name', 'streamit-api'),
            'singular_name'              => _x('Category', 'taxonomy singular name', 'streamit-api'),
            'search_items'               => __('Search Categories', 'streamit-api'),
            'popular_items'              => __('Popular Categories', 'streamit-api'),
            'all_items'                  => __('All Categories', 'streamit-api'),
            'parent_item'                => __('Parent Category', 'streamit-api'),
            'parent_item_colon'          => __('Parent Category:', 'streamit-api'),
            'edit_item'                  => __('Edit Category', 'streamit-api'),
            'update_item'                => __('Update Category', 'streamit-api'),
            'add_new_item'               => __('Add New Category', 'streamit-api'),
            'new_item_name'              => __('New Category Name', 'streamit-api'),
            'separate_items_with_commas' => __('Separate Categories with commas', 'streamit-api'),
            'add_or_remove_items'        => __('Add or remove Categories', 'streamit-api'),
            'choose_from_most_used'      => __('Choose from the most used Categories', 'streamit-api'),
            'not_found'                  => __('No Categories found.', 'streamit-api'),
            'menu_name'                  => __('Categories', 'streamit-api'),
        );

        $cat_args = array(
            'hierarchical'          => true,
            'labels'                => $cat_labels,
            'show_ui'               => true,
            'show_admin_column'     => true,
            'update_count_callback' => '_update_post_term_count',
            // 'query_var'             => true,
            // 'rewrite'               => array( 'slug' => 'Category' ),
        );

        register_post_type('live_tv', $args);

        $cat_slug = 'live_tv_cat';
        register_taxonomy($cat_slug, 'live_tv', $cat_args);
        add_action("{$cat_slug}_add_form_fields", [$this, 'add_live_tv_cat_meta_fields_markup']);
        add_action("{$cat_slug}_edit_form_fields", [$this, 'edit_live_tv_cat_meta_fields_markup']);
        add_action("created_{$cat_slug}", [$this, 'save_live_tv_cat_meta_fields']);
        add_action("edited_{$cat_slug}", [$this, 'save_live_tv_cat_meta_fields']);
        add_filter("manage_edit-{$cat_slug}_columns", [$this, 'add_live_tv_cat_custom_columns']);
        add_filter("manage_{$cat_slug}_custom_column", [$this, 'display_live_tv_cat_custom_columns'], 10, 3);
    }

    // Add custom fields for channel URL, and URL type
    function iqonic_add_channels_meta_box()
    {
        add_meta_box(
            "iqonic-live-tv-channel-meta-fields",
            "Channel Data",
            [$this, "meta_fields_markup"],
            "live_tv",
            "normal",
            "high",
            null
        );
    }
    // New / Edit channels custom fields markup(HTML) at admin side
    function meta_fields_markup($channel)
    {

        $url_type   = get_post_meta($channel->ID, 'iqonic_live_tv_channel_url_type', true);
        $url        = get_post_meta($channel->ID, 'iqonic_live_tv_channel_url', true);
?>
        <div>
            <table width="100%">
                <?php add_action("iqonic_after_channels_meta_fields_markup_table_starts", $channel) ?>
                <tr>
                    <th align="left" width="15%"><?php _e('Channel URL type', 'iqonic') ?></th>
                    <td>
                        <select name="iqonic_channel_url_type" style="width:70%;">
                            <option value="0" <?php selected($url_type, '0'); ?>><?php _e("-- Select type --", "streamit-api"); ?></option>
                            <option value="url" <?php selected($url_type, 'URL'); ?>><?php _e("URL", "streamit-api"); ?></option>
                            <option value="embed" <?php selected($url_type, 'embed'); ?>><?php _e("Embed", "streamit-api"); ?></option>
                            <option value="youtube" <?php selected($url_type, 'youtube'); ?>><?php _e("YouTube", "streamit-api"); ?></option>
                            <option value="hls" <?php selected($url_type, 'hls'); ?>><?php _e("HLS", "streamit-api"); ?></option>
                            <option value="vimeo" <?php selected($url_type, 'vimeo'); ?>><?php _e("Vimeo", "streamit-api"); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th align="left" width="15%"><?php _e('Channel URL', 'iqonic') ?></th>
                    <td><input name="iqonic_channel_url" type="text" style="width:70%;" value="<?php echo esc_url($url); ?>" /></td>
                </tr>
                <?php add_action("iqonic_before_channels_meta_fields_markup_table_ends", $channel) ?>
            </table>
            <input name="iqonic_channel_custom_field_nonce" type="hidden" value="<?php echo wp_create_nonce('iqonic-channel-field-nonce') ?>" />
        </div>
    <?php    }

    // Save custom fields value into database
    function iqonic_save_channels_custom_meta_fields($channelID, $post, $update)
    {
        if (
            !isset($_POST["iqonic_channel_custom_field_nonce"])
            || !wp_verify_nonce($_POST["iqonic_channel_custom_field_nonce"], 'iqonic-channel-field-nonce')
        ) {
            return $channelID;
        }

        if (!current_user_can("edit_post", $channelID)) {
            return $channelID;
        }

        if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
            return $channelID;
        }

        $url_type = $channel_url = '';
        if (isset($_POST['iqonic_channel_url_type'])) {
            $url_type = sanitize_text_field($_POST['iqonic_channel_url_type']);
        }
        if (isset($_POST['iqonic_channel_url'])) {
            $channel_url = sanitize_text_field($_POST['iqonic_channel_url']);
        }

        update_post_meta($channelID, 'iqonic_live_tv_channel_url_type', $url_type);
        update_post_meta($channelID, 'iqonic_live_tv_channel_url', $channel_url);
    }

    // category custom thumbnail metabox
    function add_live_tv_cat_meta_fields_markup()
    {
    ?>
        <div class="form-field term-group add-new">
            <input type="hidden" id="iq-taxonomy-image-id" name="iq-taxonomy-image-id" value="">
            <div id="taxonomy-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary taxonomy-media-button" id="taxonomy-media-button" name="taxonomy-media-button" value="<?php _e('Add Image', 'streamit-api'); ?>" />
                <input type="button" class="button button-secondary taxonomy-media-remove" id="taxonomy-media-remove" name="taxonomy-media-remove" value="<?php _e('Remove Image', 'streamit-api'); ?>" />
            </p>
            <p>Upload category image</p>
        </div>
    <?php
        $this->add_live_tv_cat_tumbnail_script();
    }
    function edit_live_tv_cat_meta_fields_markup($term)
    {
        $image_id = get_term_meta($term->term_id, 'thumbnail_id', true);
    ?>
        <tr class="form-field term-group-wrap">
            <td>
                <input type="hidden" id="iq-taxonomy-image-id" name="iq-taxonomy-image-id" value="<?php echo esc_attr($image_id); ?>">
                <div id="taxonomy-image-wrapper">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary taxonomy-media-button" id="taxonomy-media-button" name="taxonomy-media-button" value="<?php _e('Add Image', 'streamit-api'); ?>" />
                    <input type="button" class="button button-secondary taxonomy-media-remove" id="taxonomy-media-remove" name="taxonomy-media-remove" value="<?php _e('Remove Image', 'streamit-api'); ?>" />
                </p>
            </td>
        </tr>
    <?php
        $this->add_live_tv_cat_tumbnail_script();
    }

    function save_live_tv_cat_meta_fields($term_id)
    {

        if (isset($_POST['iq-taxonomy-image-id']) && '' !== $_POST['iq-taxonomy-image-id']) {
            $image = sanitize_text_field($_POST['iq-taxonomy-image-id']);
            update_term_meta($term_id, 'thumbnail_id', $image);
        } else {
            update_term_meta($term_id, 'thumbnail_id', '');
        }
    }
    function add_live_tv_cat_custom_columns($columns)
    {
        $new_columns = array();

        foreach ($columns as $key => $title) {
            if ($key == 'cb') {
                $new_columns['cb'] = $title;
                $new_columns['image'] = __('Image', 'streamit-api');
            } else {
                $new_columns[$key] = $title;
            }
        }

        return $new_columns;
    }
    function display_live_tv_cat_custom_columns($content, $column_name, $term_id)
    {
        if ($column_name === 'image') {
            $image_id = get_term_meta($term_id, 'thumbnail_id', true);
            if ($image_id) {
                $content = wp_get_attachment_image($image_id, [70, 70]);
            } else {
                $content = __('No Image', 'streamit-api');
            }
        }

        return $content;
    }
    function add_live_tv_cat_tumbnail_script()
    {
    ?>
        <script>
            jQuery(document).ready(function($) {
                var frame;
                $('#taxonomy-media-button').on('click', function(e) {
                    e.preventDefault();
                    if (frame) {
                        frame.open();
                        return;
                    }
                    frame = wp.media({
                        title: '<?php _e('Select or Upload Media', 'streamit-api'); ?>',
                        button: {
                            text: '<?php _e('Use this media', 'streamit-api'); ?>'
                        },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#iq-taxonomy-image-id').val(attachment.id);
                        $('#taxonomy-image-wrapper').html('<img src="' + attachment.sizes.thumbnail.url + '" />');
                    });
                    frame.open();
                });

                $('#taxonomy-media-remove').on('click', function() {
                    $('#iq-taxonomy-image-id').val('');
                    $('#taxonomy-image-wrapper').html('');
                });

                $(document).ajaxSuccess(function(event, xhr, settings) {
                    if (settings.data && settings.data.indexOf('action=add-tag') !== -1 && !xhr.responseText.includes("wp_error")) {
                        $('#iq-taxonomy-image-id').val('');
                        $('#taxonomy-image-wrapper').text('');
                    }
                });
            });
        </script>
<?php
    }
}
