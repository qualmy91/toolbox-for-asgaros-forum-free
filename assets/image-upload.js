function tfaf_file_upload(field_id) {

    const uploadForm = '<form id="tfaf_file_form" action="?tfaf_do_upload=1" method="post" target="tfaf_file_frame"' +
        'enctype="multipart/form-data" onsubmit="tfaf_file_upload_submit();" data-field-id="' + field_id + '">' +
        '<input type="file" name="tfaf_file" id="tfaf_file" tabindex="-1" onchange="jQuery(this).parent().submit();" />' +
        '<iframe id="tfaf_response_frame" name="tfaf_file_frame" />';

    jQuery('#tfaf_file_form, #tfaf_response_frame').remove();
    jQuery('#' + field_id).parent().css('overflow', 'hidden')
        .append(uploadForm);

    jQuery('#tfaf_file').trigger('click');

}

function tfaf_file_upload_submit() {
    const responseFrame = jQuery('#tfaf_response_frame');

    responseFrame.load(function () {
        let response = responseFrame.contents().find('body').text();
        if (!(response.startsWith('https://') || response.startsWith('http://'))) {
            if (response.startsWith('size-error')){
                // Get max upload size
                let max_size = response.split(']');
                max_size = max_size[0].split('[')[1];
                alert('Error: The image must be smaller then ' + max_size + ' MB.');
            } else if (response.startsWith('[[tfaf_debugging_mode]]')) {
                alert(response);
            } else {
                alert('Error: The image upload was not successful. Please check your image and try it again. Only PNG, GIF or JPEG files are allowed.');
            }
            response = '';
        }
        jQuery('#' + jQuery('#tfaf_file_form').data('field-id')).val(response).prop('disabled', false);
        responseFrame.off('load');
    });
    jQuery('#' + jQuery('#tfaf_file_form').data('field-id')).prop('disabled', true).val('Please wait...');

    return true;
}