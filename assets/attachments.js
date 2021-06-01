(function ($) {
    $('#js-attachment-files').on('change', function (e) {
        $('#js-attachment-errors').empty().hide();
    });

    $('#js-attachment-form').on('submit', function (e) {
        var form = $(this);
        var submit = $('#js-attachment-submit');
        var progress = $('#js-attachment-progress');
        var progressBar = progress.find('.progress-bar');
        var fileInput = $('#js-attachment-files');
        var feedback = $('#js-attachment-errors');
        var delay = 250;
        var hasFiles = fileInput[0].files.length > 0;

        e.preventDefault();
        e.stopImmediatePropagation();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            dataType: 'json',
            data: new FormData(this),
            beforeSend: function () {
                hasFiles && form.hide();
                hasFiles && progress.show();
                fileInput.removeClass('is-invalid');
                feedback.empty().hide();
            },
            success: function (response) {
                if (response.errors) {
                    feedback.show().html(response.errors);
                }

                form[0].reset();
                $.pjax.reload('#js-attachment-list', {async: false});
                $.pjax.reload('#js-attachment-type', {async: false});
            },
            complete: function () {
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
                        if (e.lengthComputable) {
                            progressBar.css('width', ((e.loaded / e.total) * 100) + '%');
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
                    $.pjax.reload('#js-attachment-list', {async: false});
                    $.pjax.reload('#js-attachment-type', {async: false});
                }
            });
        }
    });
})(jQuery);
