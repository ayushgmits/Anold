<?php
/**
 * Episode Meta Box Template
 *
 * This template is used to render custom fields for the 'episode' post type.
 *
 * @package WP Episodes Manager
 */
?>

<div class="ish-meta-fields">
    <?php echo  $is_unlocked; ?>
    <p>
        <label for="ish_is_free"><?php _e('Is Free?', STREAM_HANDLER); ?></label>
        <input type="checkbox" id="ish_is_free" name="ish_is_free" value="1" <?php checked($is_free, 1); ?> />
        <br><small><?php _e('This setting determines whether it is available for free. If enabled, users can watch it without spending any coins.', STREAM_HANDLER); ?></small>
    </p>

    <p>
        <label for="ish_ads_count"><?php _e('Ads Count', STREAM_HANDLER); ?></label>
        <input type="number" id="ish_ads_count" name="ish_ads_count" value="<?php echo esc_attr($ads_count); ?>" />
        <br><small><?php _e('Defines the number of ads that will be displayed during playback. Set to 0 for an ad-free experience.', STREAM_HANDLER); ?></small>
    </p>

    <p>
            <label for="ish_coins"><?php _e('Coins', STREAM_HANDLER); ?></label>
            <input type="number" id="ish_coins" name="ish_coins" value="<?php echo esc_attr($coins); ?>" />
        <br><small><?php _e('Specifies the number of coins a user must spend to unlock it. If set to 0, it will be available for free.', STREAM_HANDLER); ?></small>
    </p>

    <label for="unlock_time"><?php _e('Select Unlock Time:', 'wppoi'); ?></label>
<input type="datetime-local" name="unlock_time" id="unlock_time" 
    value="<?php echo (!empty($unlock_time)) ? esc_attr(date('Y-m-d\TH:i', intval($unlock_time))) : ''; ?>" 
    class="wppoi-datetimepicker" />

    
    <p id="unlock_timer" class="description"><?php //echo esc_html($formatted_time); ?></p>
    <p id="unlock_timerss"><?php echo esc_html($formatted_unlock_time); ?></p>

   <?php
    // Generate message dynamically
        if ($remaining_minutes > 0) {
            $hours = floor($remaining_minutes / 60);
            $minutes = $remaining_minutes % 60;
            $formatted_time = "Will unlock intime ";
    
            if ($hours > 0) {
                $formatted_time .= $hours . " hour" . ($hours > 1 ? "s" : "") . " ";
            }
            if ($minutes > 0) {
                $formatted_time .= $minutes . " minute" . ($minutes > 1 ? "s" : "");
            }
        } else {
            $formatted_time = "Unlocked!";
        } 
        ?>
    <div id="unlock_timer"><?php echo $formatted_time; ?></div>
    
    <?php wp_nonce_field('ish_save_meta', 'ish_nonce'); ?>
</div>
