;(function($) {
    /**
     * Upload handler helper
     *
     * @param string browse_button ID of the pickfile
     * @param string container ID of the wrapper
     */
    window.PTPImporter_Uploader = function (browse_button, container) {
        this.container = container;
        this.browse_button = browse_button;

        //if no element found on the page, bail out
        if(!$('#'+browse_button).length) {
            return;
        }

        //instantiate the uploader
        this.uploader = new plupload.Uploader({
            runtimes: 'html5,silverlight,flash,html4',
            browse_button: browse_button,
            container: container,
            multipart: true,
            multipart_params: [],
            multiple_queues: false,
            urlstream_upload: true,
            file_data_name: 'ptp_attachment',
            max_file_size: PTPImporter_Vars.plupload.max_file_size,
            url: PTPImporter_Vars.plupload.url,
            flash_swf_url: PTPImporter_Vars.plupload.flash_swf_url,
            silverlight_xap_url: PTPImporter_Vars.plupload.silverlight_xap_url,
            filters: PTPImporter_Vars.plupload.filters,
            resize: PTPImporter_Vars.plupload.resize
        });

        //attach event handlers
        this.uploader.bind('Init', $.proxy(this, 'init'));
        this.uploader.bind('FilesAdded', $.proxy(this, 'added'));
        this.uploader.bind('QueueChanged', $.proxy(this, 'upload'));
        this.uploader.bind('UploadProgress', $.proxy(this, 'progress'));
        this.uploader.bind('Error', $.proxy(this, 'error'));
        this.uploader.bind('FileUploaded', $.proxy(this, 'uploaded'));

        this.uploader.init();
    };

    PTPImporter_Uploader.prototype = {

        init: function (up, params) {
        },

        added: function (up, files) {
            $('.import').remove();

            var $container = $('#' + this.container).find('.upload-filelist');

            $.each(files, function(i, file) {
                $container.append(
                    '<div class="uploaded-item" id="' + file.id + '"><div class="progress"><div class="percent"></div><div class="bar"></div></div></div>');
            });

            up.refresh(); // Reposition Flash/Silverlight
            // up.start();
        },

        upload: function (uploader) {
            this.uploader.start();
        },

        progress: function (up, file) {
            var item = $('#' + file.id);

            $('.bar', item).width( (100 * file.loaded) / file.size );
            $('.percent', item).html( file.percent + '%' );
        },

        error: function (up, error) {
            $('#' + this.container).find('#' + error.file.id).remove();
            alert('Error #' + error.code + ': ' + error.message);
        },

        uploaded: function (up, file, response) {
            var res = $.parseJSON(response.response);
            //$('#' + file.id).remove();

            if(res.success) {
                $('#' + file.id).append(res.content);
                $('#' + file.id + ' .item-link img').load(function(){
                    $(this).parent().toggle();
                    $(this).parent().siblings('.progress').toggle();
                    $(this).parent().siblings('fieldset').find('.item-title').animate({
                        marginLeft: '-=178px',
                        opacity: 1
                    }, 400, function(){
                        $(this).addClass('item-title-active');
                        $(this).parent().siblings('.item-delete').toggle();
                    });
                });
            } else {
                console.log(res.error);
            }
        }
    };

    $(function () {
        new PTPImporter_Uploader('upload-pickfiles', 'upload-container');
    });
})(jQuery);