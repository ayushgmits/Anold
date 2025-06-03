<div class="row mr-lg-0" id="st-admin-option-accordion">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <span class="h5"><?php echo esc_html__('Streamit Plugin', 'streamit-plugin-lang') ?></span><small
                        class="text-muted ml-2"><?php echo esc_html__('v ' . STREAMIT_API_VERSION, 'streamit-plugin-lang') ?></small>
            </div>
            <div class="card-body p-0 mt-2">
                <ul class="nav nav-pills nav-tabs nav-fill" id="st-accordion-config" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="st-accordion-dashboard" data-toggle="tab" href="#tab-dashboard"
                           role="tab" aria-controls="tab-dashboard" aria-selected="true">
                            <?php echo esc_html__('Home', 'streamit-plugin-lang') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="st-accordion-movies" data-toggle="tab" href="#tab-movies" role="tab"
                           aria-controls="tab-movies" aria-selected="false">
                            <?php echo esc_html__('Movies', 'streamit-plugin-lang') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="st-accordion-tv-shows" data-toggle="tab" href="#tab-tv-shows" role="tab"
                           aria-controls="tab-tv-shows" aria-selected="false">
                            <?php echo esc_html__('TV Shows', 'streamit-plugin-lang') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="st-accordion-video" data-toggle="tab" href="#tab-video" role="tab"
                           aria-controls="tab-video" aria-selected="false">
                            <?php echo esc_html__('Video', 'streamit-plugin-lang') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="st-accordion-live-tv" data-toggle="tab" href="#tab-live-tv" role="tab"
                           aria-controls="tab-live-tv" aria-selected="false">
                            <?php echo esc_html__('Live TV', 'streamit-plugin-lang') ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="tab-content" id="st-accordion-config-panel">
            <div class="tab-pane fade show active" id="tab-dashboard" role="tabpanel"
                 aria-labelledby="st-accordion-dashboard">
                <?php include STREAMIT_API_DIR . 'resources/views/st_admin_dashboard.php'; ?>
            </div>
            <div class="tab-pane fade" id="tab-movies" role="tabpanel" aria-labelledby="st-accordion-movies">
                <?php include STREAMIT_API_DIR . 'resources/views/st_admin_movie.php'; ?>
            </div>
            <div class="tab-pane fade" id="tab-tv-shows" role="tabpanel" aria-labelledby="st-accordion-tv-shows">
                <?php include STREAMIT_API_DIR . 'resources/views/st_admin_tv_show.php'; ?>
            </div>
            <div class="tab-pane fade" id="tab-video" role="tabpanel" aria-labelledby="st-accordion-video">
                <?php include STREAMIT_API_DIR . 'resources/views/st_admin_video.php'; ?>
            </div>
            <div class="tab-pane fade" id="tab-live-tv" role="tabpanel" aria-labelledby="st-accordion-live-tv">
                <?php include STREAMIT_API_DIR . 'resources/views/st_admin_live_tv.php'; ?>
            </div>
        </div>
    </div>
</div>
