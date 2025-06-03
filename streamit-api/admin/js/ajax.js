
function get(route, Dataparams) {
    return jQuery.ajax({
        url: window.ajaxurl,
        type: 'GET',
        data: {
            ...Dataparams,
            action: 'streamit_api_ajax_get',
            route_name: route
        }
    })
}

function post(route, Dataparams) {

    let isFormData = {
        processData: false,
        contentType: false
    };

    // Check if Dataparams is a FormData object
    if (Dataparams instanceof FormData) {
        Dataparams.append('action', 'streamit_api_ajax_post');
        Dataparams.append('route_name', route);
    } else {
        // Regular data object
        Dataparams = {
            ...Dataparams,
            action: 'streamit_api_ajax_post',
            _ajax_nonce: window.streamitapiAjax._ajax_nonce,
            route_name: route
        };
    }
    
    return jQuery.ajax({
        url: window.ajaxurl,
        type: 'POST',
        data: Dataparams,
        ...(Dataparams instanceof FormData) ? isFormData : {}
    });
}

export { get, post } 