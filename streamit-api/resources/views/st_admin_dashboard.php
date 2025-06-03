<?php
// $dashboardData = get_option('streamit_app_home');
// $movieTvShowList = getSTMovieTvShowList()->toArray();
// $getSTGenreList = getSTGenreList()->toArray();
// $getSTTagList = getSTTagList()->toArray();
// $getSTFilterList = getSTFilterList()->toArray();

$dashboardData = get_option('streamit_app_home');
// $movieTvShowList = getSTTVShowList()->toArray();
$dynamicShowType = get_option('default_show_type', ''); // Fetch from options or another source
$movieTvShowList = getSTTVShowList(false, $dynamicShowType)->toArray();
$getSTGenreList = getSTTVShowGenreList()->toArray();
$getSTTagList = getSTTVShowTagList()->toArray();
$getSTFilterList = getSTFilterList()->toArray();
?>
<div class="card p-0">
    <div class="card-body">
        <form name="st-admin-option-dashboard" id="st-admin-option-dashboard" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-12">
                    <h5>
                        <?php echo esc_html__('Home Banner', 'streamit-plugin-lang') ?>
                    </h5>
                    <hr>
                </div>
                <div class="col-lg-2">
                    <?php echo esc_html__('Slides', 'streamit-plugin-lang') ?>
                </div>
                <div class="col-lg-10">
                    <div id="dashboard-banner-slide" class="st-clone-master" data-accordion="true">
                        <?php
                        if (isset($dashboardData['banner']) && count($dashboardData['banner']) > 0) {
                            foreach ($dashboardData['banner'] as $i1 => $d1) {
                        ?>
                                <div class="card st-clone-item">
                                    <div class="card-header st-accordion-header collapsed" id="dashboard-banner-slide-<?php echo ($i1 + 1); ?>" data-toggle="collapse" data-target="#dashboard-banner-slide-body-<?php echo ($i1 + 1); ?>" aria-expanded="false" aria-controls="dashboard-banner-slide-body-<?php echo ($i1 + 1); ?>">
                                        <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Banner">
                                            <?php echo esc_html__('Banner', 'streamit-plugin-lang') . ' ' . ($i1 + 1); ?>
                                        </span>
                                        <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="dashboard-banner-slide-body-<?php echo ($i1 + 1); ?>" class="collapse st-accordion-body" aria-labelledby="dashboard-banner-slide-<?php echo ($i1 + 1); ?>" data-parent="#dashboard-banner-slide">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label><?php _e("Movies / TV Shows / Videos / Live Channels", "streamit-plugin-lang") ?></label>
                                                    <select class="form-control st-multiple-checkboxes" name="banner_movie_show[]" data-live-search="true" data-size="10" x-placement="Select Movies / TV Shows / Videos / Live Channels">
                                                        <?php
                                                        foreach ($movieTvShowList as $movieTvShow) {
                                                            echo '<option value="' . $movieTvShow['value'] . '" ' . ((int)$movieTvShow['value'] === (int)$d1['show'] ? 'selected' : '') . '>' . $movieTvShow['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mt-3 d-grid">
                                                    <label><?php _e("Image", "streamit-plugin-lang") ?></label>
                                                    <?php
                                                    if (isset($d1['attachment']) && $attach = wp_get_attachment_image_src($d1['attachment'])) {
                                                        echo '<div class="st-upload-img cursor-pointer"><img class="form-control img slide-image-preview" src="' . $attach[0] . '" /></div>
                                            <input type="hidden" name="banner_image[]" value="' . $d1['attachment'] . '">
                                            <div class="st-upload-img-rmv btn btn-outline-danger cursor-pointer mt-2"><i class="fa fa-times"></i></div>';
                                                    } else {

                                                        echo '<div class="st-upload-img btn btn-outline-secondary cursor-pointer">' . __("Upload Image", "streamit-plugin-lang") . '</div>
                                            <input type="hidden" name="banner_image[]">
                                            <div class="st-upload-img-rmv btn btn-outline-danger cursor-pointer mt-2" style="display:none"><i class="fa fa-times"></i></div>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php
                            }
                        } else {
                            ?>
                            <div class="card st-clone-item">
                                <div class="card-header st-accordion-header collapsed" id="dashboard-banner-slide-1" data-toggle="collapse" data-target="#dashboard-banner-slide-body-1" aria-expanded="false" aria-controls="dashboard-banner-slide-body-1">
                                    <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Banner">
                                        <?php echo esc_html__('Banner 1', 'streamit-plugin-lang'); ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div id="dashboard-banner-slide-body-1" class="collapse st-accordion-body" aria-labelledby="dashboard-banner-slide-1" data-parent="#dashboard-banner-slide">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label><?php _e("Movies / TV Shows / Videos / Live Channels", "streamit-plugin-lang") ?></label>
                                                <select class="form-control st-multiple-checkboxes" name="banner_movie_show[]" data-live-search="true" data-size="10" x-placement="Select Movie / TV Show / Video">
                                                    <?php
                                                    foreach ($movieTvShowList as $movieTvShow) {
                                                        echo '<option value="' . $movieTvShow['value'] . '" >' . $movieTvShow['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mt-3 d-grid">
                                                <label><?php _e("Image", "streamit-plugin-lang") ?></label>
                                                <div class="st-upload-img btn btn-outline-secondary cursor-pointer">
                                                    <?php _e("Upload Image", "streamit-plugin-lang") ?>
                                                </div>
                                                <input type="hidden" name="banner_image[]">
                                                <div class="st-upload-img-rmv btn btn-outline-danger cursor-pointer mt-2" style="display:none"><i class="fa fa-times"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        <?php
                        } ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary float-right st-clone-add-more mt-2">
                        <?php echo esc_html__('Add New', 'streamit-plugin-lang') ?>
                    </button>
                </div>
                <div class="col-md-12">
                    <h5>
                        <?php echo esc_html__('Slider', 'streamit-plugin-lang') ?>
                    </h5>
                    <hr>
                </div>
                <div class="col-lg-2">
                    <?php echo esc_html__('Slider', 'streamit-plugin-lang') ?>
                </div>
                <div class="col-lg-10">
                    <div id="dashboard-sub-slider" class="st-clone-master" data-accordion="true">
                        <?php
                        if (isset($dashboardData['sliders']) && count($dashboardData['sliders']) > 0) {
                            foreach ($dashboardData['sliders'] as $i2 => $d2) { ?>
                                <div class="card st-clone-item">
                                    <div class="card-header st-accordion-header collapsed" id="dashboard-sub-slider-<?php echo ($i2 + 1); ?>" data-toggle="collapse" data-target="#dashboard-sub-slider-body-<?php echo ($i2 + 1); ?>" aria-expanded="false" aria-controls="dashboard-sub-slider-body-<?php echo ($i2 + 1); ?>">
                                        <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Slider">
                                            <?php esc_html_e(!empty($d2['title']) ? $d2['title'] : 'Slider ' . ($i2 + 1)); ?>
                                        </span>
                                        <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>

                                    <div id="dashboard-sub-slider-body-<?php echo ($i2 + 1); ?>" class="collapse st-accordion-body" aria-labelledby="dashboard-sub-slider-<?php echo ($i2 + 1); ?>" data-parent="#dashboard-sub-slider">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <label><?php _e("Title", "streamit-plugin-lang")  ?></label>
                                                    <input class="form-control" type="text" placeholder="Title" value="<?php esc_html_e($d2['title']) ?>" name="sub_title[]">
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php _e("Genres", "streamit-plugin-lang")  ?></label>
                                                    <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="sub_genre[]" multiple data-actions-box="true" x-placement="Select Genre">
                                                        <?php
                                                        foreach ($getSTGenreList as $genre) {
                                                            echo '<option value="' . $genre['value'] . '" ' . (in_array($genre['value'], $d2['genre']) ? 'selected' : '') . '>' . $genre['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php _e("Tags", "streamit-plugin-lang")  ?></label>
                                                    <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="sub_tag[]" multiple data-actions-box="true" x-placement="Select Tags">
                                                        <?php
                                                        foreach ($getSTTagList as $tag) {
                                                            echo '<option value="' . $tag['value'] . '" ' . (in_array($tag['value'], $d2['tag']) ? 'selected' : '') . '>' . $tag['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php _e("Filter By", "streamit-plugin-lang")  ?></label>
                                                    <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="filter[]" x-placement="Select Filter">
                                                        <?php
                                                        foreach ($getSTFilterList as $filter) {
                                                            echo '<option value="' . $filter['value'] . '" ' . ($filter['value'] === $d2['filter'] ? 'selected' : '') . '>' . $filter['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php _e("Movie / TV Show / Video / Live Channels", "streamit-plugin-lang")  ?></label>
                                                    <select class="form-control st-multiple-checkboxes" multiple name="select_movie_show[]" data-live-search="true" data-size="10" data-actions-box="true" x-placement="Select Movie / TV show / Video">
                                                        <?php
                                                        foreach ($movieTvShowList as $movieTvShow) {
                                                            echo '<option value="' . $movieTvShow['value'] . '" ' . (in_array($movieTvShow['value'], $d2['select_movie_show']) ? 'selected' : '') . ' >' . $movieTvShow['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 d-grid mt-3">
                                                    <label><?php _e("View All", "streamit-plugin-lang")  ?></label>
                                                    <label class="switch mt-2">
                                                        <input name="view_all[]" type="checkbox" value="true" <?php echo $d2['view_all'] === 'true' ? 'checked' : ''; ?>>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            <?php
                            }
                        } else {
                            ?>
                            <div class="card st-clone-item">
                                <div class="card-header st-accordion-header collapsed" id="dashboard-sub-slider-1" data-toggle="collapse" data-target="#dashboard-sub-slider-body-1" aria-expanded="false" aria-controls="dashboard-sub-slider-body-1">
                                    <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Slider">
                                        <?php esc_html_e('Slider 1'); ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>

                                <div id="dashboard-sub-slider-body-1" class="collapse st-accordion-body" aria-labelledby="dashboard-sub-slider-1" data-parent="#dashboard-sub-slider">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <label><?php _e("Title", "streamit-plugin-lang")  ?></label>
                                                <input class="form-control" type="text" placeholder="Title" value="" name="sub_title[]">
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php _e("Genres", "streamit-plugin-lang")  ?></label>
                                                <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="sub_genre[]" multiple data-actions-box="true" x-placement="Select Genre">
                                                    <?php
                                                    foreach ($getSTGenreList as $genre) {
                                                        echo '<option value="' . $genre['value'] . '" >' . $genre['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php _e("Tags", "streamit-plugin-lang")  ?></label>
                                                <select class="form-control st-multiple-checkboxes" data-actions-box="true" data-live-search="true" data-size="10" name="sub_tag[]" multiple x-placement="Select Tags">
                                                    <?php
                                                    foreach ($getSTTagList as $tag) {
                                                        echo '<option value="' . $tag['value'] . '" >' . $tag['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php _e("Filter By", "streamit-plugin-lang")  ?></label>
                                                <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="filter[]" x-placement="Select Filter">
                                                    <?php
                                                    foreach ($getSTFilterList as $filter) {
                                                        echo '<option value="' . $filter['value'] . '" >' . $filter['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php _e("Movie / TV Show / Video / Live Channels", "streamit-plugin-lang")  ?></label>
                                                <select class="form-control st-multiple-checkboxes" multiple name="select_movie_show[]" data-live-search="true" data-size="10" data-actions-box="true" x-placement="Select Movie / TV show / Video">
                                                    <?php
                                                    foreach ($movieTvShowList as $movieTvShow) {
                                                        echo '<option value="' . $movieTvShow['value'] . '" >' . $movieTvShow['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 d-grid mt-3">
                                                <label><?php _e("View All", "streamit-plugin-lang")  ?></label>
                                                <label class="switch mt-2">
                                                    <input name="view_all[]" type="checkbox" value="true" checked>
                                                    <span class="slider round"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        <?php
                        } ?>
                    </div>
                    <button type="button" class="btn btn-outline-primary float-right st-clone-add-more mt-2">
                        <?php echo esc_html__('Add New', 'streamit-plugin-lang') ?>
                    </button>
                </div>
                <div class="col-md-12">
                    <hr class="mb-3">
                    <button type="button" class="btn btn-info mt-2" id="st-dashboard-admin-setting">
                        <?php echo esc_html__('Submit', 'streamit-plugin-lang') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>