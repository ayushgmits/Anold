<div class="wrap">
    <h1><?php _e("App Options", STA_TEXT_DOMAIN) ?></h1>
    <hr>
    <ul class="nav nav-pills">
    <li class="nav-item">
            <a class="nav-link" href="admin.php?page=app-options&settings=general"><?php _e("General", STA_TEXT_DOMAIN) ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin.php?page=app-options&settings=comment"><?php _e("Comment", STA_TEXT_DOMAIN) ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin.php?page=app-options&settings=device-limit"><?php _e("Limit Logins", STA_TEXT_DOMAIN) ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="admin.php?page=app-options&settings=st-pmp"><?php _e("Membership", STA_TEXT_DOMAIN) ?></a>
        </li>
    </ul>
    <hr>
    <form method="post" action="options.php" class="p-3">
        <div class="form-row">
            <div class="form-group col-md-6">
                <?php
                settings_fields('app_options_group');
                do_settings_sections('app_setting_options_page');
                submit_button();
                ?>
            </div>
        </div>
    </form>
</div>