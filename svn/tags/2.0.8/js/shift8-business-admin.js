jQuery(document).ready(function ($) {
    $('#shift8_business_test_api').on('click', function (e) {
        e.preventDefault();
        var resultBox = $('#shift8_api_test_result');
        resultBox.hide().html('Testing API...').fadeIn();

        $.ajax({
            type: 'POST',
            url: shift8_ajax.ajaxurl,
            data: {
                action: 'shift8_business_test_api',
                nonce: shift8_ajax.nonce, // ✅ FIXED
            },
            success: function (response) {
                if (response.success) {
                    resultBox.css('background', '#d4edda').css('border-color', '#c3e6cb').html(
                        '<strong>✅ Success:</strong> ' + response.data.message +
                        '<pre style="white-space: pre-wrap; background: #f8f9fa; padding: 10px; border: 1px solid #ccc;">' + response.data.data + '</pre>'
                    );
                } else {
                    var errorDetails = response.data.details ? '<pre>' + JSON.stringify(response.data.details, null, 2) + '</pre>' : '';
                    resultBox.css('background', '#f8d7da').css('border-color', '#f5c6cb').html(
                        '<strong>❌ Error:</strong> ' + response.data.message + errorDetails
                    );
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                resultBox.css('background', '#f8d7da').css('border-color', '#f5c6cb').html(
                    '<strong>❌ AJAX Request Failed:</strong><br>' +
                    '<strong>Status:</strong> ' + textStatus + '<br>' +
                    '<strong>Error:</strong> ' + errorThrown + '<br>' +
                    '<pre>' + jqXHR.responseText + '</pre>'
                );
            }
        });
    });
});
