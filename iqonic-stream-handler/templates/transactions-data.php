
<?php
    // Fetch countries
    $countriesclass = new Membership_Transactions();
    $countries = $countriesclass->get_countries_list();
    $selected_country = $_GET['country'] ?? '';
    $selected_state = isset($_GET['state']) ? $_GET['state'] : ''; // Retrieve selected state
    $states = !empty($selected_country) ? get_states_list($selected_country) : [];
?>

<div class="wrap">
    <h1><?php _e('Membership Transactions', 'my-membership-plugin'); ?></h1>

    <form method="get" id="transaction-form">
        <input type="hidden" name="page" value="mt-transactions" />
        <table class="form-table">
            <tr>
                <th><label for="start_date"><?php _e('Start Date', 'my-membership-plugin'); ?></label></th>
                <td><input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($_GET['start_date'] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="end_date"><?php _e('End Date', 'my-membership-plugin'); ?></label></th>
                <td><input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($_GET['end_date'] ?? ''); ?>" /></td>
            </tr>
            <tr>
                <th><label for="user_id"><?php _e('User ', 'my-membership-plugin'); ?></label></th>
                <td>
                    <select id="user_id" name="user_id">
                        <option value="0"><?php _e('All Users', 'my-membership-plugin'); ?></option>
                        <?php
                        $users = get_users(array('fields' => array('ID', 'display_name')));
                        foreach ($users as $user) {
                            printf(
                                '<option value="%d" %s>%s</option>',
                                $user->ID,
                                selected($_GET['user_id'] ?? 0, $user->ID, false),
                                esc_html($user->display_name)
                            );
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="transaction_type"><?php _e('Transaction Type', 'my-membership-plugin'); ?></label></th>
                <td>
                    <select id="transaction_type" name="transaction_type">
                        <option value=""><?php _e('All Transactions', 'my-membership-plugin'); ?></option>
                        <option value="coins" <?php selected($_GET['transaction_type'] ?? '', 'coins'); ?>><?php _e('Coins', 'my-membership-plugin'); ?></option>
                        <option value="subscription" <?php selected($_GET['transaction_type'] ?? '', 'subscription'); ?>><?php _e('Subscription', 'my-membership-plugin'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="country"><?php _e('Country', 'my-membership-plugin'); ?></label></th>
                <td>
                <select class="country-dropdown" id="country" name="countrys">
                    <option value=""><?php _e('Select Country', 'my-membership-plugin'); ?></option>
                    <?php 
                    $selected_country = isset($_GET['countrys']) ? sanitize_text_field($_GET['countrys']) : ''; // Get selected country from the URL
                    foreach ($countries as $name): ?>
                        <option value="<?php echo esc_attr($name); ?>" <?php selected($selected_country, $name); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                </td>
            </tr>
            <tr>
                <th><label for="state"><?php _e('State/Province', 'my-membership-plugin'); ?></label></th>
                <td>
                <select id="state" name="state" data-selected-state="<?php echo esc_attr($selected_state); ?>">
                    <option value=""><?php _e('Select State', 'my-membership-plugin'); ?></option>
                    <?php
                    if ($selected_country) {
                        $states = $this->get_states_list($selected_country);
                        foreach ($states as $state): ?>
                            <option value="<?php echo esc_attr($state); ?>" <?php selected($selected_state, $state); ?>>
                                <?php echo esc_html($state); ?>
                            </option>
                        <?php endforeach;
                    }
                    ?>
                </select>
                </td>
            </tr>
        </table>
        <?php submit_button(__('Show Transactions', 'my-membership-plugin')); ?>
    </form>
</div>







