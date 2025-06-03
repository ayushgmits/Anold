<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h2>Membership Levels & Coins</h2>

    <?php if (!empty($membership_levels)) : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Level ID</th>
                    <th>Level Name</th>
                    <th>Coins</th>
                    <th>Bonus Coins (%)</th>
                    <th>Free Coins</th>
                    <th>Total Coins</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($membership_levels as $level) : ?>
                    <tr>
                        <td><?php echo esc_html($level->id); ?></td>
                        <td><?php echo esc_html($level->name); ?></td>
                        <td><?php echo esc_html($level->coins); ?></td>
                        <td><?php echo esc_html($level->bonus_coins); ?></td>
                        <td><?php echo esc_html($level->free_coins); ?></td>
                        <td><?php echo esc_html($level->total_coins); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <div class="notice notice-warning">
            <p>No membership levels with coins enabled found.</p>
        </div>
    <?php endif; ?>
</div>
