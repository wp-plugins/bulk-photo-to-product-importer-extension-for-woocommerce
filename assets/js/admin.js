;(function (w, $) {
    var PTPImporter = {

        init: function () {
            $('.term-id').chosen().find('option[value="-1"]').val('');
            var vg = $('.variation-group');
            vg
                .prop( 'disabled', true )
                .chosen( )
                .find( 'option[value="-1"]' )
                .val( '' );

            $('.datepicker').datepicker();
			
			var d = new Date();
			var today = [d.getMonth()+1,d.getDate(),d.getFullYear()];
			today = today.join("/");
			console.log(today.toString());
			$('.datepicker').datepicker("setDate", today);
			
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
                var _this = this;

                $('.photos-import-form').submit( function( e ) {
                    e.preventDefault( );
                } );
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
								var myNull = null;
                        // PTPImporter.BulkImport.addNew.call(form,myNull);
                       _this.addNew( );

                        return false;
                    }
                });
            },
            addNew: function() {
				var _this         = this;
				var form          = $( '.photos-import-form' );
				var data          = form.serializeArray( );
				var submitBtn     = $( '#import_photos' );
				var picker		  = $( '.import' );
				var fileList	  = $( '.upload-filelist' );
				var attach        = {};
				
				attach.medias = $.grep( data, function( i ) { return i.name == "attachments[]"; });
                attach.titles = $.grep( data, function( i ) { return /^titles\[\d+\]$/.test(i.name); });
				 
				submitBtn
                    .css('width', $('#import_photos')
                        .css('width').replace(/[^-\d\.]/g, ''))
                    .val( 'Saving...' )
                    .prop( 'disabled', true );
				
				submitBtn.after('<div class="saving"><img src="' + PTPImporter_Vars.pluginurl + '/assets/images/wpspin.gif" width="18" height="18" /></div>');
				picker.fadeOut();
				_this.recursiveAdd(attach, data);
            },
			recursiveAdd: function( filelist, data ) {
				var _this         = this;
				var form          = $( '.photos-import-form' );
				var picker		  = $( '.import' );
				var submitBtn     = $( '#import_photos' );
				var chunk   = $.grep( data, function( i ) { return /^(attachments\[\]|titles\[\d+\])$/.test(i.name) === false; });
				if(filelist.medias.length > 0){
					var currentfile = filelist.medias.shift();
					var mediaid = currentfile.value;
					var elem    = form.find( '.uploaded-item' ).has( '[name="titles[' + mediaid + ']"]' );
					var regx    = new RegExp( '\\\[' + mediaid + '\\\]' );
					var title   = $.grep( filelist.titles, function( i ) { return regx.test(i.name); } );
					var title   = title.length ? title.shift( ) : {};
					
					chunk.push( currentfile );
					chunk.push( title );
					
					$.ajax({type: "POST",url: PTPImporter_Vars.ajaxurl, data: chunk, success: function(){
						elem.delay( 2000 ).fadeOut( 'fast', function( ) {
								elem.remove( );
							} );
						_this.recursiveAdd(filelist, data);
					}});					
				}
				else
				{
					_this.showSuccessMessage( "Images successfully uploaded and associated products were created", " " );
					$(".saving").remove();
					submitBtn
						.css('width', $('#import_photos')
							.css('width').replace(/[^-\d\.]/g, ''))
						.val( 'Save and Continue' )
						.prop( 'disabled', false );
					submitBtn.fadeOut();	
					picker.fadeIn();
				}
			},
			showErrorMessage: function( message, add ) {
                var add = add || null;
                var div = $( '.error' );
                if ( div.is(':visible') ) {
                   div.find( 'p' ).html( message + add );
                } else {
                     div.slideToggle(function(){
                       div.find( 'p' ).append( add );
                    });
                }
            },
            showSuccessMessage: function( message, add ) {
                var add = add || null;
                var div = $( '.updated' );
                if ( div.is(':visible') ) {
                   div.find( 'p' ).html( message + add );
                } else {
                     div.slideToggle(function(){
                       div.find( 'p' ).append( add );
                    });
                }
            },
            remove: function (e) {
                e.preventDefault();
				var that = $(this);
				that.closest('.uploaded-item').fadeOut(function(){
                    $(this).remove();
                });
				if($( '.upload-filelist' ).children().length == 1)
					$('#import_photos').fadeOut();
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
					auxdata = $( 'input[name=category_name]' ).val();

                that.before('<div class="ptp-loading-white">Saving...</div>');
                that.css('width', that.css('width').replace(/[^-\d\.]/g, '')).val( 'Saving...' ).prop( 'disabled', 'disabled' ).addClass('loading-secondary');
                $.post(PTPImporter_Vars.ajaxurl, data, function(res) {
        					try{
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
                        $( '.photos-import-form .add-category' ).removeClass( 'cancel-add-category dashicons-no' );
                        $( '.photos-import-form .add-category' ).addClass( 'dashicons-plus-alt' );

						var data = {
							action: 'ptp_category_list',
							'_wpnonce': PTPImporter_Vars.nonce
						};
						$.post(PTPImporter_Vars.ajaxurl, data, function(res) {
							try{
								res = $.parseJSON(res);
								if (res) {
									$(".add-category").prev().html('');
									$(".add-category").prev().html(res.html);
									$("#term_id").chosen();	
									//reverted back to nonselection
									$("#term_id").prop("selectedIndex",0);
									$("#term_id").trigger('liszt:updated');
								}
								else {
									console.log(res.error);
									
								}
							}
							catch(err){
								
							}
						});
						
						
                        if ($('.updated').is(':visible')) {
                            $('.updated p').text( 'New category created.' );
                        } else {
                            $('.updated').slideToggle(function(){
                                $('.updated p').text( 'New category created.' );
                            });
                        }
                    } else {
						console.log(res.error);
								$( '.photos-import-form .quick-add-category-con' ).slideToggle();
								that.trigger('reset');
                                $('.photos-import-form input[type=text]').val('');
                                $('.photos-import-form select').val('').trigger('liszt:updated');
								$('.photos-import-form').trigger( 'ptp_reset_import_form' );
								$( '.photos-import-form .add-category' ).removeClass( 'cancel-add-category dashicons-no' );
                                $( '.photos-import-form .add-category' ).addClass( 'dashicons-plus-alt' );
                        if ($('.error').is(':visible')) {
                            $('.error p').text( res.error );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( res.error );
                            });
                        }
						
                    }
                }catch(err){
                console.log(err);
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
                	try{
                    res = $.parseJSON(res);

                    if (res) {
                        con.html( res.html );
                        $('.parent-term-id').chosen();
                        con.slideToggle(function(){
                            $( document ).on( 'click', '.photos-import-form .add-category', PTPImporter.BulkImport.toggleQuickAddCategory);
                        });

                        if ( that.hasClass( 'cancel-add-category' ) ) {
                            that.removeClass( 'cancel-add-category dashicons-no' );
                            that.addClass( 'dashicons-plus-alt' );
                        } else {
                            that.removeClass( 'dashicons-plus-alt' );
                            that.addClass( 'cancel-add-category dashicons-no' );
                        }
                    } else {
                       // console.log(res.error);
                    }
                }catch(err){
					 console.log(err); 
					 $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });              
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
                	try{
                    res = $.parseJSON(res);

                    if (res) {
                        con.html(res.html);
                        $('.variation-group').chosen().find('option[value="-1"]').val('');

                        // Add hook
                        $('.photos-import-form').trigger( 'ptp_preselect_variation_group_dropdown' );
                    } else {
                        console.log(res.error);
                    }
                }catch(err){
                console.log(err);
                $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
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
                                 '<span type="text" class="remove-variation-row control dashicons dashicons-no"></span>' +
                                 '<span type="text" class="add-variation-row control dashicons dashicons-plus-alt"></span>' +
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
                    try{
                    res = $.parseJSON(res);

                    if( res.success ) {
                        var tr = $( res.html );
                        if( res.parent ) {
                            list.find('#variation-group-row-' + res.parent).after( tr );
                            tr.fadeIn( );
                        } else {
                            list.append( tr );
                            tr.fadeIn( );
                        }
                        // list.append( res.html );
                        // list.find('tr').last().fadeIn();

                        if ( $( '.no-variation-groups' ).length )
                            $( '.no-variation-groups' ).remove();

                        // Reset form
                        that.trigger('reset');
                        that.find('.variations table.row:not(:first)').remove();

                        $('#add-variation-group').val('Add Variation Group').prop('disabled', false);

                        // Update the parent-group dropdown
                        parentgroupDropdownHtml = res.dropdown;
                        prevParentGroupDropdownHtml = $('select[name*="parent-group"]').replaceWith(parentgroupDropdownHtml);
                    } else {
                        console.log(res.error);
                    }

                    $('.chzn-error').removeClass('chzn-error');
                    $('#add-variation-group').removeClass('loading-primary');
                    $('.ptp-loading').remove();
                 }catch(err){
                 console.log(err);
                  $('.chzn-error').removeClass('chzn-error');
                    $('#add-variation-group').removeClass('loading-primary');
                    $('.ptp-loading').remove();
                    $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
                 }
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
                	try{
                    res = $.parseJSON(res);

                    if( res.success ) {
                        var thisTr = $( '#variation-group-row-' + res.term_id );
                        var thatTr = $( '#variation-group-row-' + res.parent );

                        // Update title
                        thisTr.find('.column-name a strong').html( res.name );

                        // Update description
                        if ( $('.variations-form-update #group-description').val() != thisTr.find('.column-description span').text() ) {
                            thisTr.find('.column-description span').text( $('.variations-form-update #group-description').val() );
                        }

                        // Update Variations Count
                        if ( $('.variations-form-update table.row').length != thisTr.find('.column-variations span').text() ) {
                            thisTr.find('.column-variations span').text( $('.variations-form-update table.row').length );
                        }

                        if ( thisTr.hasClass('alternate') )
                            thisTr.css('background-color', '#fcfcfc');
                        else 
                            thisTr.css('background-color', 'transparent');

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

                        if( res.is_moved ) {
                            thisTr.slideUp( 'fast', function( ) {
                                thisTr.detach( ).addClass( 'hidden' );

                                if( res.parent ) {
                                    thisTr.insertAfter( thatTr );
                                } else {
                                    thisTr.appendTo( '#the-list' );
                                }
                                thisTr.slideDown( 'fast' );
                            })
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
             }catch(err){
             console.log(err);

                        if ($('.error').is(':visible')) {
                            $('.error p').text( res.error );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( res.error );
                            });
                        }
                    $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
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
                	try{
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
                }catch(err){
                 console.log(res.error);
                 $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
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
                		try{
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
                 }catch(err){
                 console.log(err);
                 $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
                 }
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
                	try {
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
                    }catch(err){
                    console.log(err);
                    $('.error').slideToggle(function(){
                               $('.error p').html( 'Unable to import photos. Please check the documentation and see if you configured you server properly.<br\> <b>Try increasing the Time Interval</b> in the settings section<br/>Still having problems? We can help! <br />Our payed support forum is the best way to get the assistance you need' );
                            });
                    }
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
                	try{
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
                    }catch(err){
                    	console.log(err);
                    	 $( '#serial-key' ).after( '<span class="failed">Failed</span>' );

                        if ( action == 'ptp_activate' ) {
                            $( '#ptp-activate' ).val( 'Activate' );
                            $( 'input[name=action]' ).val( 'ptp_activate' );
                        } else {
                            $( '#ptp-activate' ).val( 'Deactivate' );
                            $( 'input[name=action]' ).val( 'ptp_deactivate' );
                        }
                    	
                    $( '#ptp-activate' ).removeClass( 'ptp-button-active' ).prop( 'disabled', false ).css({ 'cursor': 'pointer' });
                    $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                    }
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
                	try{
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
                    }catch(err){
                     console.log(err);
                        if ($('.error').is(':visible')) { 
                            $('.error p').text( 'Unable to save categories settings.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Unable to save categories settings.' );
                            });
                        }
                      $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                    }
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
                	try{
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
                    }catch(err){
                    console.log(err);
                        if ($('.error').is(':visible')) {
                            $('.error p').text( 'Migration failed.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Migration failed.' );
                            });
                        }
                    $('.ptp-loading').remove();                    
                    }
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
                	try{
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
                    }catch(err){
                     console.log(err);
                        if ($('.error').is(':visible')) {
                            $('.error p').text( 'Unable to save general settings.' );
                        } else {
                            $('.error').slideToggle(function(){
                                $('.error p').text( 'Unable to save general settings.' );
                            });
                        }
                     $('.chzn-error').removeClass('chzn-error');
                    $('.ptp-loading').remove();
                    }
                });

                return false;
            }
        }
    };

    //dom ready
    $(function() {
        PTPImporter.init();
    });
	$(function($) {
				var items = $("table #the-list tr");

				var numItems = items.length;
				var perPage = 10;
				
				items.slice(perPage).hide();

				$(".pagination-page").pagination({
					items: numItems,
					itemsOnPage: perPage,
					cssStyle: "compact-theme",
					onPageClick: function(pageNumber) {
						var showFrom = perPage * (pageNumber - 1);
						var showTo = showFrom + perPage;
						items.hide()
							 .slice(showFrom, showTo).show();
					}
				});
			});
   w.PTPImporter = PTPImporter; 
})(window, jQuery);