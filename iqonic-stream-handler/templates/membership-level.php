        <h3><?php _e( 'Level Type', 'textdomain' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="custom_subscription_level"><?php _e( 'Subscription Level', 'textdomain' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="custom_subscription_level" id="custom_subscription_level" value="1" <?php checked( get_post_meta( $level->id, '_custom_subscription_level', true ), 1 ); ?> />
                        <span class="description"><?php _e( 'Check this box to enable subscription for this membership level.', 'textdomain' ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="custom_coins_checkbox"><?php _e( 'Coins Level', 'textdomain' ); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" name="custom_coins_level" id="custom_coins_level" value="1" <?php checked( get_post_meta( $level->id, '_custom_coins_level', true ), 1 ); ?> />
                        <span class="description"><?php _e( 'Check this box to enable coins for this membership level.', 'textdomain' ); ?></span>
                    </td>
                </tr>
            </table>

            <h3><?php _e('Coins Settings', 'your-text-domain'); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="enable_coins"><?php _e('Enable Coins for this Membership Level', 'your-text-domain'); ?></label></th>
                    <td>
                        <input type="checkbox" id="enable_coins" name="enable_coins" value="1" <?php checked($enable_coins, 1); ?> />
                        <p class="description"><?php _e('Check this box to enable coins for this membership level.', 'your-text-domain'); ?></p>
                    </td>
                </tr>
                <tr class="coins-settings" style="<?php echo ($enable_coins ? '' : 'display:none;'); ?>">
                    <th><label for="coins"><?php _e('Coins', 'your-text-domain'); ?></label></th>
                    <td>
                        <input type="number" id="coins" name="coins" value="<?php echo esc_attr($coins); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr class="coins-settings" style="<?php echo ($enable_coins ? '' : 'display:none;'); ?>">
                    <th><label for="bonus_coins"><?php _e('Bonus Coins %', 'your-text-domain'); ?></label></th>
                    <td>
                        <input type="number" id="bonus_coins" name="bonus_coins" value="<?php echo esc_attr($bonus_coins); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr class="coins-settings" style="<?php echo ($enable_coins ? '' : 'display:none;'); ?>">
                    <th><label for="free_coins"><?php _e('Free Coins', 'your-text-domain'); ?></label></th>
                    <td>
                        <input type="number" id="free_coins" name="free_coins" value="<?php echo esc_attr($free_coins); ?>" class="regular-text" readonly/>
                    </td>
                </tr>
                <tr class="coins-settings" style="<?php echo ($enable_coins ? '' : 'display:none;'); ?>">
                    <th><label for="total_coins"><?php _e('Total Coins', 'your-text-domain'); ?></label></th>
                    <td>
                        <input type="number" id="total_coins" name="total_coins" value="<?php echo esc_attr($total_coins); ?>" class="regular-text" readonly />
                        <p class="description"><?php _e('Total Coins are calculated as: Coins + Bonus Coins + Free Coins.', 'your-text-domain'); ?></p>
                    </td>
                </tr>
            </table>