<?php
$liveTvData = get_option('streamit_app_live_tv');
$live_tv_list = getSTLiveTVList()->toArray();
$getSTLiveTvCategoryList = getSTLiveTVCategoryList()->toArray();
$getSTFilterList = getSTFilterList()->toArray();
?>
<div class="card p-0">
    <div class="card-body">
        <form name="st-admin-option-live-tv" id="st-admin-option-live-tv" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-12">
                    <h5>
                        <?php echo esc_html__('Live TV Banner', 'streamit-plugin-lang') ?>
                    </h5>
                    <hr>
                </div>
                <div class="col-lg-2">
                    <?php echo esc_html__('Slides', 'streamit-plugin-lang') ?>
                </div>
                <div class="col-lg-10">
                    <div id="live-tv-banner-slide" class="st-clone-master" data-accordion="true">
                        <?php
                        if (isset($liveTvData['banner']) && count($liveTvData['banner']) > 0) {
                            foreach ($liveTvData['banner'] as $i1 => $d1) {
                        ?>
                                <div class="card st-clone-item">
                                    <div class="card-header st-accordion-header collapsed" id="live-tv-banner-slide-<?php echo ($i1 + 1); ?>" data-toggle="collapse" data-target="#live-tv-banner-slide-body-<?php echo ($i1 + 1); ?>" aria-expanded="false" aria-controls="live-tv-banner-slide-body-<?php echo ($i1 + 1); ?>">
                                        <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Banner">
                                            <?php echo esc_html__('Banner', 'streamit-plugin-lang') . ' ' . ($i1 + 1); ?>
                                        </span>
                                        <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="live-tv-banner-slide-body-<?php echo ($i1 + 1); ?>" class="collapse st-accordion-body" aria-labelledby="live-tv-banner-slide-<?php echo ($i1 + 1); ?>" data-parent="#live-tv-banner-slide">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label><?php echo esc_html__('Channels', 'streamit-plugin-lang'); ?></label>
                                                    <select class="form-control st-multiple-checkboxes" name="banner_live_tv_cahnnels[]" data-live-search="true" data-size="10" x-placement="Select Channel">
                                                        <?php
                                                        foreach ($live_tv_list as $channel) {
                                                            echo '<option value="' . $channel['value'] . '" ' . ((int)$channel['value'] === (int)$d1['show'] ? 'selected' : '') . '>' . $channel['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3 mt-3 d-grid">
                                                    <label><?php echo esc_html__('Image', 'streamit-plugin-lang'); ?></label>
                                                    <?php
                                                    if (isset($d1['attachment']) && $attach = wp_get_attachment_image_src($d1['attachment'])) {
                                                        echo '<div class="st-upload-img cursor-pointer"><img class="form-control img slide-image-preview" src="' . $attach[0] . '" /></div>
                                            <input type="hidden" name="banner_image[]" value="' . $d1['attachment'] . '">
                                            <div class="st-upload-img-rmv btn btn-outline-danger cursor-pointer mt-2" ><i class="fa fa-times"></i></div>';
                                                    } else {

                                                        echo '<div class="st-upload-img btn btn-outline-secondary cursor-pointer">' . __("Upload image", "streamit-plugin-lang") . '</div>
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
                                <div class="card-header st-accordion-header collapsed" id="live-tv-banner-slide-1" data-toggle="collapse" data-target="#live-tv-banner-slide-body-1" aria-expanded="false" aria-controls="live-tv-banner-slide-body-1">
                                    <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Banner">
                                        <?php echo esc_html__('Banner 1', 'streamit-plugin-lang'); ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div id="live-tv-banner-slide-body-1" class="collapse st-accordion-body" aria-labelledby="live-tv-banner-slide-1" data-parent="#live-tv-banner-slide">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label><?php echo esc_html__('Channels', 'streamit-plugin-lang'); ?></label>
                                                <select class="form-control st-multiple-checkboxes" name="banner_live_tv_cahnnels[]" data-live-search="true" data-size="10" x-placement="Select Channel">
                                                    <?php
                                                    foreach ($live_tv_list as $channel) {
                                                        echo '<option value="' . $channel['value'] . '" >' . $channel['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mt-3 d-grid">
                                                <label><?php echo esc_html__('Image', 'streamit-plugin-lang'); ?></label>
                                                <div class="st-upload-img btn btn-outline-secondary cursor-pointer">
                                                    <?php echo esc_html__('Upload Image', 'streamit-plugin-lang'); ?>
                                                </div>
                                                <input type="hidden" name="banner_image[]">
                                                <div class="st-upload-img-rmv btn btn-outline-danger cursor-pointer mt-2" style="display:none"><i class="fa fa-times"></i></div>
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
                    <div id="live-tv-sub-slider" class="st-clone-master" data-accordion="true">
                        <?php
                        if (isset($liveTvData['sliders']) && count($liveTvData['sliders']) > 0) {
                            foreach ($liveTvData['sliders'] as $i2 => $d2) { ?>
                                <div class="card st-clone-item">
                                    <div class="card-header st-accordion-header collapsed" id="live-tv-sub-slider-<?php echo ($i2 + 1); ?>" data-toggle="collapse" data-target="#live-tv-sub-slider-body-<?php echo ($i2 + 1); ?>" aria-expanded="false" aria-controls="live-tv-sub-slider-body-<?php echo ($i2 + 1); ?>">
                                        <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Slider">
                                            <?php esc_html_e(!empty($d2['title']) ? $d2['title'] : 'Slider ' . ($i2 + 1)); ?>
                                        </span>
                                        <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </div>

                                    <div id="live-tv-sub-slider-body-<?php echo ($i2 + 1); ?>" class="collapse st-accordion-body" aria-labelledby="live-tv-sub-slider-<?php echo ($i2 + 1); ?>" data-parent="#live-tv-sub-slider">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-lg-12">
                                                    <label><?php _e("Title", "streamit-plugin-lang")  ?></label>
                                                    <input class="form-control" type="text" placeholder="Title" value="<?php esc_html_e($d2['title']) ?>" name="sub_title[]">
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php echo esc_html__('Catgories', 'streamit-plugin-lang'); ?></label>
                                                    <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="sub_cat[]" multiple data-actions-box="true" x-placement="Select Genre">
                                                        <?php
                                                        foreach ($getSTLiveTvCategoryList as $genre) {
                                                            echo '<option value="' . $genre['value'] . '" ' . (in_array($genre['value'], $d2['cat']) ? 'selected' : '') . '>' . $genre['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="col-lg-6 mt-3">
                                                    <label><?php echo esc_html__('Filter By', 'streamit-plugin-lang'); ?></label>
                                                    <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="filter[]" x-placement="Select Filter">
                                                        <?php
                                                        foreach ($getSTFilterList as $filter) {
                                                            echo '<option value="' . $filter['value'] . '" ' . ($filter['value'] === $d2['filter'] ? 'selected' : '') . '>' . $filter['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mt-3">
                                                    <label><?php echo esc_html__('Channels', 'streamit-plugin-lang'); ?></label>
                                                    <select class="form-control st-multiple-checkboxes" multiple name="select_live_tv_channels[]" data-live-search="true" data-size="10" data-actions-box="true" x-placement="Select Channel">
                                                        <?php
                                                        foreach ($live_tv_list as $channel) {
                                                            echo '<option value="' . $channel['value'] . '" ' . (in_array($channel['value'], $d2['select_live_tv_channels']) ? 'selected' : '') . ' >' . $channel['text'] . '</option>';
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 d-grid mt-3">
                                                    <label><?php echo esc_html__('View All', 'streamit-plugin-lang'); ?></label>
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
                                <div class="card-header st-accordion-header collapsed" id="live-tv-sub-slider-1" data-toggle="collapse" data-target="#live-tv-sub-slider-body-1" aria-expanded="false" aria-controls="live-tv-sub-slider-body-1">
                                    <span class="m-0 h6 text-center cursor-pointer st-clone-header" data-title="Slider">
                                        <?php esc_html_e('Slider 1'); ?>
                                    </span>
                                    <button type="button" class="btn btn-outline-danger st-clone-remove float-right mt-0">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>

                                <div id="live-tv-sub-slider-body-1" class="collapse st-accordion-body" aria-labelledby="live-tv-sub-slider-1" data-parent="#live-tv-sub-slider">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label><?php _e("Title", "streamit-plugin-lang")  ?></label>
                                                <input class="form-control" type="text" placeholder="Title" value="" name="sub_title[]">
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php echo esc_html__('Categories', 'streamit-plugin-lang'); ?></label>
                                                <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="sub_cat[]" multiple data-actions-box="true" x-placement="Select Genre">
                                                    <?php
                                                    foreach ($getSTLiveTvCategoryList as $genre) {
                                                        echo '<option value="' . $genre['value'] . '" >' . $genre['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="col-lg-6 mt-3">
                                                <label><?php echo esc_html__('Filter By', 'streamit-plugin-lang'); ?></label>
                                                <select class="form-control st-multiple-checkboxes" data-live-search="true" data-size="10" name="filter[]" x-placement="Select Filter">
                                                    <?php
                                                    foreach ($getSTFilterList as $filter) {
                                                        echo '<option value="' . $filter['value'] . '" >' . $filter['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 mt-3">
                                                <label><?php echo esc_html__('Channels', 'streamit-plugin-lang'); ?></label>
                                                <select class="form-control st-multiple-checkboxes" multiple name="select_live_tv_channels[]" data-live-search="true" data-size="10" data-actions-box="true" x-placement="Select Channel">
                                                    <?php
                                                    foreach ($live_tv_list as $channel) {
                                                        echo '<option value="' . $channel['value'] . '" >' . $channel['text'] . '</option>';
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-6 d-grid mt-3">
                                                <label><?php echo esc_html__('View All', 'streamit-plugin-lang'); ?></label>
                                                <label class="switch mt-2">
                                                    <input name="view_all[]" type="checkbox" value="true">
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
                    <button type="button" class="btn btn-info mt-2" id="st-live-tv-admin-setting">
                        <?php echo esc_html__('Submit', 'streamit-plugin-lang') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>