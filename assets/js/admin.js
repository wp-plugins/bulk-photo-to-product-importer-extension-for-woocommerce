;(function ($) {

    var PTPImporter = {

        init: function () {
            $('.term-id').chosen().find('option[value="-1"]').val('');
            $('.variation-group').prop('disabled', true).chosen().find('option[value="-1"]').val('');
            $('.datepicker').datepicker();

            this.BulkImport.validate();
            this.Variations.validate();
            this.License.validate();
            this.CategoriesSettings.validate();
            this.GeneralSettings.validate();

            $( document ).on('click', '.photos-import-form .item-delete', this.BulkImport.remove);
            $( document ).on('click', '#quick-add-category', this.BulkImport.quickAddCategory);
            $( document ).on('click', '.photos-import-form .add-category', this.BulkImport.toggleQuickAddCategory);

            $( document ).on('click', '.photos-import-form .category .chzn-results li', this.BulkImport.preselectVaritionGroupDropdown);

            $( document ).on('click', '.add-variation-row', this.Variations.addRow);
            $( document ).on('click', '.remove-variation-row', this.Variations.removeRow);
            $( document ).on('click', '.delete-variations-group', this.Variations.remove);
            $( document ).on('click', '.quick-edit-variations-group', this.Variations.editForm);
            $( document ).on('click', '.cancel-quick-edit-variations-group', this.Variations.cancelEdit);
            $( document ).on('click', '.bptpi_page_ptp_variations .actions #doaction, .actions #doaction2', this.Variations.bulkRemove);

            $( document ).on('click', '#migrate-variations', this.GeneralSettings.migrate);

            // Add 1 or more variation fields on load
            for ( i = 0; i < 1; i++ ) {
                this.Variations.addRow();
            }
        },

        BulkImport: {
            validate: function (e) {
                $('.photos-import-form').validate({
                    ignore: [], // <-- option so that hidden elements are validated
                    rules: {
                        term_id: {  // <-- name of actual text input
                            required: true
                        },
                        variation_group:{  // <-- name of actual select input
                            required: true
                        },
                        date:{
                            required: true
                        }
                    },
                    invalidHandler: function(form, validator) {
                        var errors = validator.numberOfInvalids();
                        if (errors) {
                            $.map($.makeArray(validator.errorList), function(obj, i){
                                $(obj.element).addClass('chzn-error').siblings('.chzn-container').addClass('chzn-error');
                            });
                        }
                    },
                    submitHandler: function (form) {
                        var errorCount = 0;

                        if ( !$('.upload-filelist').children().length ) {
                            $('.attachment-area').addClass('chzn-error');
                            $('.photos-import-form').data('errorCount', ++errorCount);
                        }

                        // Add hook
                        $('.photos-import-form').trigger( 'ptp_validate_import_form', errorCount );

                        if ( $('.photos-import-form').data('errorCount') > 0 )
                            return;

                        PTPImporter.BulkImport.addNew.call(form);

                        return false;
                    }
                });
            },
            addNew: function (e) {
                var that = $(this),
                data = that.serialize();

                $('#import_photos').after('<div class="ptp-loading">Saving...</div>');
                $('#import_photos').css('width', $('#import_photos').css('width').replace(/[^-\d\.]/g, '')).val( 'Saving...' ).prop( 'disabled', 'disabled' ).addClass('loading-primary');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {

                        var counter = 1,
                            els = $( '.upload-filelist' ).find( '*' );

                        els.animate({ opacity: 0 }, 300, function(){
                            if ( ++counter == els.length ) {
                                $( '.upload-filelist' ).after( '<p class="import" style="display:none;"><a class="browser button button-hero" id="upload-pickfiles" href="#">Select Photos</a></p>' );
                                $( '.import' ).fadeIn(function(){
                                    $( '#import_photos' ).val( 'Save and Continue' ).prop( 'disabled', false );
                                    $('.uploaded-item').remove();
                                    $('.upload-filelist').css({ 'display': 'block'});
                                    new PTPImporter_Uploader('upload-pickfiles', 'upload-container');
                                });
                                $('.uploaded-item').remove();
                            }
                        });

                        that.trigger('reset');
                        $('.photos-import-form input[type=text]').val('');
                        $('.photos-import-form select').val('').trigger('liszt:updated');

                        // Add hook
                        $('.photos-import-form').trigger( 'ptp_reset_import_form' );

                        if ($('.updated').is(':visible')) {
                            $('.updated p').text( 'Your photo\'s were successfully imported and converted into WooCommerce Products. You may continue editing them from the Products menu item.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'Your photo\'s were successfully imported and converted into WooCommerce Products. You may continue editing them from the Products menu item.' );
                            });
                        }
                    } else {
                        console.log(res.error);

                        if ($('.error').is(':visible')) {
                            $('.error p').text( 'Unable to import photos. Please check the documentation and see if you configured you server properly.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Unable to import photos. Please check the documentation and see if you configured you server properly.' );
                            });
                        }
                    }

                    $('.chzn-error').removeClass('chzn-error');
                    $('#import_photos').removeClass('loading-primary');
                    $('.ptp-loading').remove();
                });

                return false;
            },
            remove: function (e) {
                e.preventDefault();

                var that = $(this),
                    data = {
                        file_id: that.data('id'),
                        action: 'ptp_product_delete',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if (res.success) {
                        that.closest('.uploaded-item').fadeOut(function(){
                            $(this).remove();
                        });
                    } else {
                        console.log(res.error);
                    }
                });
            },
            quickAddCategory: function(e) {
                e.preventDefault();

                if ( !$( 'input[name=category_name]' ).val() ) {
                    $( 'input[name=category_name]' ).css( 'border', '1px solid red' );
                    return;
                }

                var that = $(this),
                    data = {
                        name: $( 'input[name=category_name]' ).val(),
                        parent: $('#parent_term_id').val(),
                        action: $( 'input[name=quick_add_category_action]' ).val(),
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                that.before('<div class="ptp-loading-white">Saving...</div>');
                that.css('width', that.css('width').replace(/[^-\d\.]/g, '')).val( 'Saving...' ).prop( 'disabled', 'disabled' ).addClass('loading-secondary');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if (res.success) {
                        $( '.photos-import-form .quick-add-category-con' ).slideToggle();

                        var con = $('.photos-import-form .category .dropdown span:first'),
                            data = {
                                name: 'term_id',
                                action: 'ptp_dropdown_bulk_import_categories',
                                '_wpnonce': PTPImporter_Vars.nonce
                            };

                        $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                            res = $.parseJSON(res);
                                
                            if (res) {
                                con.html(res.html);
                                $('.term-id').chosen().find('option[value="-1"]').val('');
                                $('.variation-group').prop('disabled', true).chosen().find('option[value="-1"]').val('');

                                // Reset form
                                that.trigger('reset');
                                $('.photos-import-form input[type=text]').val('');
                                $('.photos-import-form select').val('').trigger('liszt:updated');

                                // Add hook
                                $('.photos-import-form').trigger( 'ptp_reset_import_form' );
                            } else {
                                console.log(res.error);
                            }
                        });

                        that.prop( 'disabled', false );
                        that.val( 'Add Category' );
                        $( '.photos-import-form .add-category' ).removeClass( 'cancel-add-category' );

                        if ($('.updated').is(':visible')) {
                            $('.updated p').text( 'New category created.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'New category created.' );
                            });
                        }
                    } else {
                        console.log(res.error);

                        if ($('.error').is(':visible')) {
                            $('.error p').text( res.error );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( res.error );
                            });
                        }
                    }
                });

                that.removeClass('loading-secondary');
            },
            toggleQuickAddCategory: function(e) {
                var that = $(this),
                    con = $( '.photos-import-form .quick-add-category-con' ),
                    data = {
                        action: 'ptp_category_quick_add_form',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                $( document ).off( 'click', '.photos-import-form .add-category', PTPImporter.BulkImport.toggleQuickAddCategory);

                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if (res) {
                        con.html( res.html );
                        $('.parent-term-id').chosen();
                        con.slideToggle(function(){
                            $( document ).on( 'click', '.photos-import-form .add-category', PTPImporter.BulkImport.toggleQuickAddCategory);
                        });

                        if ( that.hasClass( 'cancel-add-category' ) ) {
                            that.removeClass( 'cancel-add-category' );
                        } else {
                            that.addClass( 'cancel-add-category' );
                        }
                    } else {
                        console.log(res.error);
                    }
                });
            },
            preselectVaritionGroupDropdown: function (e) {
                var con = $('.photos-import-form .variation .dropdown span:first'),
                    data = {
                        term_id: $('#term_id').val(),
                        action: 'ptp_dropdown_variation_groups',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                // Disable it while the preloaded one is being fetched
                $('#variation_group').prop('disabled', 'disabled').trigger('liszt:updated');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if (res) {
                        con.html(res.html);
                        $('.variation-group').chosen().find('option[value="-1"]').val('');

                        // Add hook
                        $('.photos-import-form').trigger( 'ptp_preselect_variation_group_dropdown' );
                    } else {
                        console.log(res.error);
                    }
                });
            }
        },

        Variations: {
            validate: function (formEl) {
                $('.variations-form').validate({
                    ignore: [], // <-- option so that hidden elements are validated
                    rules: {
                        group_name: {  // <-- name of actual text input
                            required: true
                        }
                    },
                    invalidHandler: function(form, validator) {
                        var errors = validator.numberOfInvalids();
                        if (errors) {
                            $.map($.makeArray(validator.errorList), function(obj, i){
                                $(obj.element).addClass('chzn-error').siblings('.chzn-container').addClass('chzn-error');
                            });
                        }
                    },
                    submitHandler: function (form) {
                        var count = 0;

                        $('.variation').each(function(){
                            if (!$(this).val()) {
                                ++count;
                                $(this).addClass('chzn-error');
                            }
                        });

                        $('.price').each(function(){
                            if (!$(this).val()) {
                                ++count;
                                $(this).addClass('chzn-error');
                            }
                        });

                        if ( !count )
                            PTPImporter.Variations.add.call(form);

                        return false;
                    }
                });
            },
            addRow: function (e) {
                var variationCount = $( '.variations-form .variation-count' ).val() ? parseInt( $( '.variations-form .variation-count' ).val() ) : 0,
                    con = $(this).hasClass('control') ? $(this).closest('.variations') : $('.variations-form .variations'),
                    template = $('<table class="row">' +
                                 '<tr>' +
                                 '<td>' +
                                 '<input type="text" class="variation" name="variations['+ variationCount +'][name]" value="" placeholder="Variation Name. Ex. Screen Printed Shirt." />' +
                                 '</td>' +
                                 '<td>' +
                                 '<input type="text" class="price" name="variations['+ variationCount +'][price]" value="" placeholder="Price" />' +
                                 '</td>' +
                                 '<td>' +
                                 '<span type="text" class="remove-variation-row control"></span>' +
                                 '<span type="text" class="add-variation-row control"></span>' +
                                 '</td>' +
                                 '</tr>' +
                                 '</table>'
                    );

                // Update variation value
                $( '.variations-form .variation-count' ).val( ++variationCount );

                if (!con.find('.row').length) {
                    con.empty();
                }
                
                con.append(template);
            },
            removeRow: function (e) {
                var that = $(this);
                
                that.closest('table').remove();
            },
            add: function (e) {
                var that = $(this),
                    data = that.serialize(),
                    list = $('#the-list');

                that.append('<div class="ptp-loading">Saving...</div>');
                $('#add-variation-group').css('width', $('#add-variation-group').css('width').replace(/[^-\d\.]/g, '')).val( 'Saving...' ).prop( 'disabled', 'disabled' ).addClass('loading-primary');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        list.append( res.html );
                        list.find('tr').last().fadeIn();

                        if ( $( '.no-variation-groups' ).length )
                            $( '.no-variation-groups' ).remove();

                        // Reset form
                        that.trigger('reset');
                        that.find('.variations table.row:not(:first)').remove();

                        $('#add-variation-group').val('Add Variation Group').prop('disabled', false);
                    } else {
                        console.log(res.error);
                    }

                    $('.chzn-error').removeClass('chzn-error');
                    $('#add-variation-group').removeClass('loading-primary');
                    $('.ptp-loading').remove();
                });

                return false;
            },
            update: function (e) {
                var that = $(this),
                    data = that.serialize(),
                    tr = that.closest('tr'),
                    con = tr.find('.quick-edit-con');

                that.append('<div class="ptp-loading">Saving...</div>');
                $('#update-variation-group').css('width', $('#update-variation-group').css('width').replace(/[^-\d\.]/g, '')).val( 'Saving...' ).prop( 'disabled', 'disabled' ).addClass('loading-primary');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        // Update title
                        if ( $('.variations-form-update #group-name').val() != tr.prev().find('.column-name strong a').text() ) {
                            tr.prev().find('.column-name strong a').text( $('.variations-form-update #group-name').val() );
                        }

                        // Update description
                        if ( $('.variations-form-update #group-description').val() != tr.prev().find('.column-description span').text() ) {
                            tr.prev().find('.column-description span').text( $('.variations-form-update #group-description').val() );
                        }

                        // Update Variations Count
                        if ( $('.variations-form-update table.row').length != tr.prev().find('.column-variations span').text() ) {
                            tr.prev().find('.column-variations span').text( $('.variations-form-update table.row').length );
                        }

                        if ( tr.prev().hasClass('alternate') )
                            tr.prev().css('background-color', '#fcfcfc');
                        else 
                            tr.prev().css('background-color', 'transparent');

                        con.css({ 'height': con.outerHeight() });
                        con.animate({
                            height: 0
                        }, 500, function() {
                            $(this).closest('tr').remove();
                            
                            if ($('.updated').is(':visible')) {
                                $('.updated p').text( 'Variation Group successfully updated.' );
                            } else {
                                $('.updated').slideToggle(function(){
                                    $('.updated p').text( 'Variation Group successfully updated.' );
                                });
                            }
                        });

                        $('.chzn-error').removeClass('chzn-error');
                        $('#update-variation-group').removeClass('loading-primary');
                        $('.ptp-loading').remove();
                    } else {
                        console.log(res.error);

                        if ($('.error').is(':visible')) {
                            $('.error p').text( res.error );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( res.error );
                            });
                        }
                    }
                });

                return false;
            },
            editForm: function (e) {
                e.preventDefault();

                var that = $(this),
                    td = that.closest('td'),
                    tr = that.closest('tr'),
                    data = {
                        term_id: that.data('id'),
                        action: 'ptp_variations_group_edit_form',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                if ( tr.next().find('.quick-edit-con').length ) {
                    tr.next().find('.quick-edit-con').slideUp(function(){
                        tr.css({ 'background-color': 'transparent' });
                        tr.next().remove();
                    });
                    return;
                }

                $( document ).off('click', '.quick-edit-variations-group', PTPImporter.Variations.editForm);

                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        tr.after(res.html);
                        tr.css({ 'background-color': '#EBFFF2' });

                        var target = tr.next().find('.quick-edit-con');
                        target.slideDown(function(){
                            $( document ).on('click', '.quick-edit-variations-group', PTPImporter.Variations.editForm);
                        });
                        target.closest('tr').siblings().find('.quick-edit-con').slideUp(function(){
                            $(this).closest('tr').prev().css({ 'background-color': 'transparent' });
                            $(this).closest('tr').remove();
                        });
                        $('.variations-form-update').validate({
                            ignore: [], // <-- option so that hidden elements are validated
                            rules: {
                                group_name: {  // <-- name of actual text input
                                    required: true
                                }
                            },
                            invalidHandler: function(form, validator) {
                                var errors = validator.numberOfInvalids();
                                if (errors) {
                                    $.map($.makeArray(validator.errorList), function(obj, i){
                                        $(obj.element).addClass('chzn-error').siblings('.chzn-container').addClass('chzn-error');
                                    });
                                }
                            },
                            submitHandler: function (form) {
                                var count = 0;

                                $('.variations-form-update .variation').each(function(){
                                    if (!$(this).val()) {
                                        ++count;
                                        $(this).addClass('chzn-error');
                                    }
                                });

                                $('.variations-form-update .price').each(function(){
                                    if (!$(this).val()) {
                                        ++count;
                                        $(this).addClass('chzn-error');
                                    }
                                });

                                if ( !count )
                                    PTPImporter.Variations.update.call(form);

                                return false;
                            }
                        });
                    } else {
                        console.log(res.error);
                    }
                });
            },
            cancelEdit: function (e) {
                var that = $(this),
                    tr = that.closest('tr'),
                    prev = tr.prev();

                tr.find('.quick-edit-con').slideUp(function(){
                    prev.css({ 'background-color': 'transparent' });
                    tr.remove();
                });
            },
            remove: function (e) {
                e.preventDefault();

                var that = $(this),
                    data = {
                        term_ids: [that.data('id')],
                        action: 'ptp_variations_group_delete',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                // Get dialog content
                $.post(PTPImporter_Vars.ajaxurl, {action:'ptp_variations_group_delete_dialog', '_wpnonce': PTPImporter_Vars.nonce}, function(res) {
                    res = $.parseJSON(res);

                    $( res.html ).dialog({ 
                        modal: true,
                        resizable: false,
                        width: 385,
                        height: 200,
                        buttons: {
                            'Okay': function() { 
                                $(this).dialog('close'); 

                                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                                    res = $.parseJSON(res);

                                    if (res.success) {
                                        if ( that.closest('tr').next().find('.quick-edit-wrap').length ) {
                                            that.closest('tr').next().find('.quick-edit-wrap').remove();
                                        }

                                        that.closest('tr').fadeOut(function(){
                                            $(this).remove();
                                        });
                                    } else {
                                        console.log(res.error);
                                    }
                                });
                            },
                            Cancel: function() { 
                                $(this).dialog('close'); 
                            }
                        } 
                    });
                });
            },
            bulkRemove: function (e) {
                if ( $(this).siblings('select').val() != 'delete' || !$(this).closest('.manage').find('#the-list input[type=checkbox]:checked').length ) 
                    return;

                $(this).prop('disabled', 'disabled');

                var terms = []; 
                    targets = $(this).closest('.manage').find('#the-list input[type=checkbox]:checked');

                targets.each(function(i, el){
                    terms[i] = $(this).closest('th').siblings('.column-name').find('.delete-variations-group').data('id');
                });

                var that = $(this),
                    data = {
                        term_ids: terms,
                        action: 'ptp_variations_group_delete',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                // Get dialog content
                $.post(PTPImporter_Vars.ajaxurl, {action:'ptp_variations_group_delete_dialog', '_wpnonce': PTPImporter_Vars.nonce}, function(res) {
                    res = $.parseJSON(res);

                    $( res.html ).dialog({ 
                        modal: true,
                        resizable: false,
                        width: 385,
                        height: 200,
                        buttons: {
                            'Okay': function() { 
                                $(this).dialog('close'); 
                                
                                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                                    res = $.parseJSON(res);

                                    if (res.success) {
                                        that.closest('.manage').find('input[type=checkbox]:checked').prop('checked', false);
                                        that.siblings('select').prop('selectedIndex', 0);
                                        that.prop('disabled', false);

                                        targets.each(function(i, el){
                                            if ( $(this).closest('tr').next().find('.quick-edit-wrap').length ) {
                                                $(this).closest('tr').next().find('.quick-edit-wrap').remove();
                                            }

                                            $(this).closest('tr').fadeOut(function(){
                                                $(this).remove();
                                            });
                                        });
                                    } else {
                                        console.log(res.error);
                                    }
                                });
                            },
                            Cancel: function() { 
                                $(this).dialog('close'); 
                            }
                        } 
                    });
                });
            }
        },

        License : {
            validate: function (e) {
                $('#license-form').validate({
                    rules: {
                        serial_key: {  // <-- name of actual text input
                            required: true
                        }
                    },
                    submitHandler: function (form) {
                        PTPImporter.License.activate.call(form);

                        return false;
                    }
                });
            },
            activate: function(e) {
                var that = $(this),
                    data = that.serialize() + '&_wpnonce=' + PTPImporter_Vars.nonce,
                    action = $( 'input[name=action]' ).val(),
                    buttonActiveLabel = action == 'ptp_activate' ? 'Activating...' : 'Deactivating...';

                $( '#ptp-activate' ).after( '<div class="ptp-loading">'+ buttonActiveLabel +'</div>' );
                $( '#ptp-activate' ).addClass( 'ptp-button-active' ).val( buttonActiveLabel ).prop( 'disabled', true ).css({ 'cursor': 'default' });
                $( '.checked, .failed' ).remove();
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        if ( action == 'ptp_activate' ) {
                            $( '#ptp-activate' ).val( 'Deactivate' );
                            $( 'input[name=action]' ).val( 'ptp_deactivate' );
                            $( '#serial-key' ).prop( 'readonly', true ).addClass( 'serial-key-disabled' );
                            $( '#serial-key' ).after( '<span class="checked">Checked</span>' );
                        } else {
                            $( '#ptp-activate' ).val( 'Activate' );
                            $( 'input[name=action]' ).val( 'ptp_activate' );
                            $( '#serial-key' ).prop( 'readonly', false ).removeClass( 'serial-key-disabled' );
                        }

                        location.reload();
                    } else {
                        console.log(res.message);

                        $( '#serial-key' ).after( '<span class="failed">Failed</span>' );

                        if ( action == 'ptp_activate' ) {
                            $( '#ptp-activate' ).val( 'Activate' );
                            $( 'input[name=action]' ).val( 'ptp_activate' );
                        } else {
                            $( '#ptp-activate' ).val( 'Deactivate' );
                            $( 'input[name=action]' ).val( 'ptp_deactivate' );
                        }
                    }

                    $( '#ptp-activate' ).removeClass( 'ptp-button-active' ).prop( 'disabled', false ).css({ 'cursor': 'pointer' });
                    $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                });

                return false;
            }
        },

        CategoriesSettings: {
            validate: function (e) {
            
                $('#categories-settings-form').validate({
                    ignore: [], // <-- option so that hidden elements are validated
                    rules: {},
                    invalidHandler: function(form, validator) {
                        var errors = validator.numberOfInvalids();
                        if (errors) {
                            $.map($.makeArray(validator.errorList), function(obj, i){
                                $(obj.element).addClass('chzn-error');
                            });
                        }
                    },
                    submitHandler: function (form) {
                        var errorCount = 0;

                        // Add hook
                        $('#categories-settings-form').trigger( 'ptp_categories_settings_validate', errorCount );

                        if ( $('#categories-settings-form').data('errorCount') > 0 )
                            return;

                        PTPImporter.CategoriesSettings.save.call(form);

                        return false;
                    }
                });
            },
            save: function (e) {
                var that = $(this),
                    data = that.serialize();

                that.find('input[type=submit]').after('<div class="ptp-loading">Saving...</div>');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        if ($('.updated').is(':visible')) {
                            $('.updated p').text( 'Categories settings saved.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'Categories settings saved.' );
                            });
                        }
                    } else {
                        console.log(res.error);
                        if ($('.error').is(':visible')) { 
                            $('.error p').text( 'Unable to save categories settings.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Unable to save categories settings.' );
                            });
                        }
                    }

                    $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                });

                return false;
            }
        },

        GeneralSettings: {
            validate: function (e) {
                $('#general-settings-form').validate({
                    ignore: [], // <-- option so that hidden elements are validated
                    rules: {
                        watermark_path: {  // <-- name of actual text input
                            required: true
                        }
                    },
                    invalidHandler: function(form, validator) {
                        var errors = validator.numberOfInvalids();
                        if (errors) {
                            $.map($.makeArray(validator.errorList), function(obj, i){
                                $(obj.element).addClass('chzn-error');
                            });
                        }
                    },
                    submitHandler: function (form) {
                        PTPImporter.GeneralSettings.save.call(form);

                        return false;
                    }
                });
            },
            migrate: function (e) {
                var that = $(this),
                    data = { 
                        action: 'ptp_variations_group_migrate',
                        '_wpnonce': PTPImporter_Vars.nonce
                    };

                that.after('<div class="ptp-loading">Saving...</div>');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        if ($('.updated').is(':visible')) {
                             $('.updated p').text( 'Migration successful.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'Migration successful.' );
                            });
                        }
                    } else {
                        console.log(res.error);
                        if ($('.error').is(':visible')) {
                            $('.error p').text( 'Migration failed.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Migration failed.' );
                            });
                        }
                    }

                    $('.ptp-loading').remove();
                });
            },
            save: function (e) {
                var that = $(this),
                    data = that.serialize() + '&watermark_url=' + $('#watermark-path').data('url');

                if ( $('#hide-variations').is(':checked') ) {
                    data = data.replace('hide_variations=0', 'hide_variations=1');
                } else {
                    data = 'hide_variations=0&' + data;
                }

                that.find('input[type=submit]').after('<div class="ptp-loading">Saving...</div>');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        if ($('.updated').is(':visible')) {
                            $('.updated p').text( 'General settings saved.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'General settings saved.' );
                            });
                        }

                    } else {
                        console.log(res.error);
                        if ($('.error').is(':visible')) {
                            $('.error p').text( 'Unable to save general settings.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Unable to save general settings.' );
                            });
                        }
                    }

                    $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                });

                return false;
            }
        }
    };

    //dom ready
    $(function() {
        PTPImporter.init();
    });

})(jQuery);
