(function (window, $) {
    var fileInput = $('#js-attachment-files');
    var feedback = $('#js-attachment-errors');
    var redirectUrl = $('#js-attachment-redirect-url').val();

    fileInput.on('change', function () {
        feedback.empty().hide();
    });

    $('#js-attachment-form').on('submit', function (e) {
        var form = $(this);
        var submit = $('#js-attachment-submit');
        var progress = $('#js-attachment-progress');
        var progressBar = progress.find('.progress-bar');
        var delay = 250;

        e.preventDefault();
        e.stopImmediatePropagation();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: new FormData(this),
            beforeSend: function () {
                var hasFiles = fileInput[0].files.length > 0;

                hasFiles && form.hide();
                hasFiles && progress.show();

                fileInput.removeClass('is-invalid');
                feedback.empty().hide();
            },
            success: function (response) {
                if (response.errors.length) {
                    feedback.show().html(response.errors);
                } else if (redirectUrl) {
                    window.location.href = redirectUrl;
                }

                form.get(0).reset();

                $.pjax.reload('#js-attachment-list', {async: false});
                $.pjax.reload('#js-attachment-type', {async: false});
            },
            complete: function () {
                form.get(0).reset();
                form.delay(delay).show(0);

                submit.prop('disabled', false);
                progress.delay(delay).hide(0);

                setTimeout(function () {
                    progressBar.css('width', 0);
                }, delay);
            },
            cache: false,
            contentType: false,
            processData: false,
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();

                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (e) {
                        var width = ((e.loaded / e.total) * 100) + '%';

                        if (e.lengthComputable) {
                            progressBar.text(width);
                            progressBar.css('width', width);
                        }
                    }, false);
                }

                return xhr;
            }
        });
    });

    $(document).on('click', '.js-attachment-delete', function (e) {
        e.preventDefault();

        if (confirm(Attachments.confirmMessage)) {
            $.post(e.target.href, function (response) {
                if (response.success) {
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        $.pjax.reload('#js-attachment-list', {async: false});
                        $.pjax.reload('#js-attachment-type', {async: false});
                    }
                } else {
                    alert(response.message);
                }
            });
        }
    });
})(window, window.jQuery);
