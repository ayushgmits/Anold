(function ($) {
    'use strict';
    $(document).ready(() => {
        /****************
         * Add Clone
         */
        $(document).on('click', '.st-clone-add-more', function () {
            $(document).find('.st-multiple-checkboxes').selectpicker('destroy')
            let cloneMaster = $(this).parent().find('.st-clone-master');
            if (cloneMaster.length > 0) {
                let cloneItems = cloneMaster.find('.st-clone-item:first');
                if (cloneItems.length > 0) {
                    let newClone = cloneItems.clone();
                    let clone = resetFields(newClone);
                    if ("true" === cloneMaster.attr('data-accordion')) {
                        clone = resetAccordionValue(cloneMaster, clone);
                    }
                    cloneMaster.append(clone);
                    cloneMaster.find('.st-clone-item:last .st-accordion-header').trigger('click');
                    $(document).find('.st-multiple-checkboxes').selectpicker();
                } else {
                    swal("No Item Found", "Refresh your page and try again.", "error");
                }
            }
        });

        /******************
         * Remove Clone
         */
        $(document).on('click', '.st-clone-remove', function () {
            $(this).closest('.st-clone-item').remove();
        })

        /*********************
         * Home Setting Save
         */
        $(document).on('click', '#st-dashboard-admin-setting', function () {

            let _this = $(this);
            let $inputs = $('form#st-admin-option-dashboard :input');
            let dashboardPostData = getFormData($inputs);

            postAjax(_this, 'post_st_admin_data', 'dashboard_setting', dashboardPostData, (success, response) => {
                if (response.status && response.status === true) {
                    swal("Home Panel Data Saved", " ", "success", {
                        buttons: false,
                        timer: 2000,
                    });
                } else {
                    swal("Fail To Save", "Refresh your page and try again", "error", {
                        buttons: false,
                        timer: 2000,
                    });
                }
            });
        });

        /*********************
         * Movie Setting Save
         */
        $(document).on('click', '#st-movie-admin-setting', function () {

            let _this = $(this);
            let $inputs = $('form#st-admin-option-movie :input');
            let moviePostData = getFormData($inputs);

            postAjax(_this, 'post_st_admin_data', 'movie_setting', moviePostData, (success, response) => {
                if (response.status && response.status === true) {
                    swal("Movie Panel Data Saved", " ", "success", {
                        buttons: false,
                        timer: 2000,
                    });
                } else {
                    swal("Fail To Save", "Refresh your page and try again", "error", {
                        buttons: false,
                        timer: 2000,
                    });
                }
            });
        });

        /*********************
         * TV Show Setting Save
         */
        $(document).on('click', '#st-tv-show-admin-setting', function () {

            let _this = $(this);
            let $inputs = $('form#st-admin-option-tv-show :input');
            let tvShowPostData = getFormData($inputs);

            postAjax(_this, 'post_st_admin_data', 'tv_show_setting', tvShowPostData, (success, response) => {
                if (response.status && response.status === true) {
                    swal("TV Show Panel Data Saved", " ", "success", {
                        buttons: false,
                        timer: 2000,
                    });
                } else {
                    swal("Fail To Save", "Refresh your page and try again", "error", {
                        buttons: false,
                        timer: 2000,
                    });
                }
            });
        });

        $(document).on('click', '#st-video-admin-setting', function () {

            let _this = $(this);
            let $inputs = $('form#st-admin-option-video :input');
            let moviePostData = getFormData($inputs);

            postAjax(_this, 'post_st_admin_data', 'video_setting', moviePostData, (success, response) => {
                if (response.status && response.status === true) {
                    swal("Video Panel Data Saved", " ", "success", {
                        buttons: false,
                        timer: 2000,
                    });
                } else {
                    swal("Fail To Save", "Refresh your page and try again", "error", {
                        buttons: false,
                        timer: 2000,
                    });
                }
            });
        });

        $(document).on('click', '#st-live-tv-admin-setting', function () {

            let _this = $(this);
            let $inputs = $('form#st-admin-option-live-tv :input');
            let liveTvPostData = getFormData($inputs);

            postAjax(_this, 'post_st_admin_data', 'live_tv_setting', liveTvPostData, (success, response) => {
                if (response.status && response.status === true) {
                    swal("Live TV Panel Data Saved", " ", "success", {
                        buttons: false,
                        timer: 2000,
                    });
                } else {
                    swal("Fail To Save", "Refresh your page and try again", "error", {
                        buttons: false,
                        timer: 2000,
                    });
                }
            });
        });

        // on upload button click
        $(document).on('click', '.st-upload-img', function (e) {

            e.preventDefault();

            let button = $(this),
                custom_uploader = wp.media({
                    title: 'Insert image',
                    library: {
                        type: 'image'
                    },
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                }).on('select', function () {
                    let attachment = custom_uploader.state().get('selection').first().toJSON();
                    button.html('<img class="form-control img slide-image-preview" src="' + attachment.url + '">').removeClass('btn btn-outline-secondary').next().val(attachment.id).next().show();
                }).open();

        });

        // on remove button click
        $(document).on('click', '.st-upload-img-rmv', function (e) {
            e.preventDefault();
            let button = $(this);
            button.hide().prev().val('').prev().html('Upload image').addClass('btn btn-outline-secondary');
        });

        $(document).find('.st-multiple-checkboxes').selectpicker();
    });

    function getFormData($inputs) {
        let values = {};
        $inputs.each(function () {
            if (this.name.includes('[]')) {
                let key = String(this.name).replace('[]', '');
                if (this.type === "checkbox") {
                    // console.log(this,$(this).is(':checked'));
                    if (!(key in values)) {
                        values[key] = [];
                    }
                    if ($(this).is(':checked')) {
                        values[key].push($(this).val());
                    } else {
                        values[key].push(null);
                    }
                } else {
                    if (!(key in values)) {
                        values[key] = [];
                    }
                    values[key].push($(this).val());
                }
            } else {
                values[this.name] = $(this).val();
            }
        });
        return values;
    }

    /***************************
     * Post Ajax With Callback
     * @param _this
     * @param action
     * @param type
     * @param postData
     * @param callback
     */
    function postAjax(_this, action, type, postData, callback) {
        $.ajax({
            url: st_localize.ajaxurl,
            type: "post",
            data: {
                action: action,
                _ajax_nonce: st_localize.nonce,
                fields: postData,
                type: type
            },
            beforeSend: function () {
                _this.html(
                    '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 48 48"><circle cx="24" cy="4" r="4" fill="#fff"/><circle cx="12.19" cy="7.86" r="3.7" fill="#fffbf2"/><circle cx="5.02" cy="17.68" r="3.4" fill="#fef7e4"/><circle cx="5.02" cy="30.32" r="3.1" fill="#fef3d7"/><circle cx="12.19" cy="40.14" r="2.8" fill="#feefc9"/><circle cx="24" cy="44" r="2.5" fill="#feebbc"/><circle cx="35.81" cy="40.14" r="2.2" fill="#fde7af"/><circle cx="42.98" cy="30.32" r="1.9" fill="#fde3a1"/><circle cx="42.98" cy="17.68" r="1.6" fill="#fddf94"/><circle cx="35.81" cy="7.86" r="1.3" fill="#fcdb86"/></svg><span>Saving Data..</span>'
                );
            },
            success: function (response) {
                _this.html("Submit");
                if (typeof callback == "function") {
                    callback(true, response);
                }
            },
            error: function () {
                _this.html("Submit");
                if (typeof callback == "function") {
                    callback(false, null);
                }
            }
        });
    }

    /******************************
     *Reset Clone Fields
     * @param cloneHtml
     * @returns {*} html
     */
    function resetFields(cloneHtml) {
        cloneHtml.find("input").val("");
        cloneHtml.find('input[type="checkbox"]').val(true);
        cloneHtml.find(':selected').removeAttr('selected');
        cloneHtml.find(':checked').removeAttr('checked');
        cloneHtml.find('img').attr('src', '');
        cloneHtml.find(".st-upload-img-rmv").hide().prev().val('').prev().html('Upload image').addClass('btn btn-outline-secondary');
        return cloneHtml;
    }

    /******************************
     * Set Accordion On Clone
     * @param cloneMaster
     * @param cloneHtml
     * @returns {*} html
     */
    function resetAccordionValue(cloneMaster, cloneHtml) {
        let id = cloneMaster.attr('id');
        let lastCount = parseInt(cloneMaster.find('.st-clone-item:last .st-accordion-header').attr('id').replace(id + '-', ''));
        let currentCloneCount = lastCount + 1;
        cloneHtml.find('.st-accordion-header').attr('id', id + '-' + currentCloneCount);
        cloneHtml.find('.st-accordion-header').attr('data-target', '#' + id + '-body-' + currentCloneCount);
        cloneHtml.find('.st-accordion-header').attr('aria-controls', id + '-body-' + currentCloneCount);
        cloneHtml.find('.st-accordion-header').attr('aria-expanded', false);
        cloneHtml.find('.st-accordion-header').addClass('collapsed');
        cloneHtml.find('.st-clone-header').text(cloneHtml.find('.st-clone-header').attr('data-title') + ' ' + currentCloneCount)

        cloneHtml.find('.st-accordion-body').attr('id', id + '-body-' + currentCloneCount);
        cloneHtml.find('.st-accordion-body').attr('aria-labelledby', id + '-' + currentCloneCount);
        cloneHtml.find('.st-accordion-body').removeClass('show');
        return cloneHtml;
    }

    var switchStatus = $('#stripe-switch:visible, #razorpay-switch:visible, #device-limit-switch:visible');
    var gatewayMode = $(".st-pmp-payment-options input[type=radio]:checked");
    let instructionsTr = $(".in-app-payment.instructions, .default-payment.instructions").closest("tr");
    
    instructionsTr.find("td").remove();
    instructionsTr.find("th").attr("colspan", 2);
    hideshow(switchStatus);
    hideShowGatewayMode(gatewayMode.val() ?? 0);

    $(document).on('change', '#stripe-switch, #razorpay-switch, #device-limit-switch', function (e) {
        var switchStatus = $(this);
        hideshow(switchStatus)
    });

    $(document).on('click', '.st-pmp-payment-options input[type=radio]', function (e) {
        var switchStatus = $(this);
        hideShowGatewayMode(switchStatus.val());
    });

    function hideShowGatewayMode(val) {
        if (val == 0) {
            $(".in-app-payment").closest("tr").hide();
            $(".default-payment").closest("tr").hide();
        } else if (val == 1) {
            $(".in-app-payment").closest("tr").hide();
            $(".default-payment").closest("tr").show();
        } else if (val == 2) {
            $(".in-app-payment").closest("tr").show();
            $(".default-payment").closest("tr").hide();
        }
    }

    function hideshow(val) {
        if (val.is(':checked') === false) {
            val.closest('table').find("tr").not("tr:first").hide();
        } else {
            val.closest('table').find("tr").show();
            hideShowGatewayMode(gatewayMode.val());
        }
    }

})(jQuery);