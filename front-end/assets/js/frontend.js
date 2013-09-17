;(function ($) {

    var PTPImporter_Frontend = {
        init: function () {
            $('.select-product').on('click', this.QuickOrder.loadProduct);
            $('.quick-order-form').on('submit', this.QuickOrder.addToCart);
            $('.quick-order-launch').on('click', this.QuickOrder.openDialog);
            $('.quick-order-close').on('click', this.QuickOrder.closeDialog);
            $('#quick-order-cancel, .okay').on('click', this.QuickOrder.closeDialog);

            $('.ptp-widget-cart-wrap').on('mouseover', '.toggle', this.Cart.show);
        },
        QuickOrder: {
            openDialog: function (e) {
                e.preventDefault();

                if ($('input[name^=ptp_grouped_products]').length) {
                    $('.queue-empty').hide().siblings().show().siblings('.products-added').hide();
                } else {
                    $('.queue-empty').show().siblings().hide();
                }

                $('.quick-order-dialog').dialog('open');
            },
            closeDialog: function (e) {
                e.preventDefault();

                $('.quick-order-dialog').dialog('close');
            },
            loadProduct: function (e) {
                var that = $(this),
                    form = $('.quick-order-form');

                if (form.find('input#' + that.data('id')).length) {
                    $('input#' + that.data('id')).remove();
                    that.removeClass('selected');
                } else {
                    form.prepend('<input id="'+ that.data('id') +'" type="hidden" name="ptp_grouped_products[]" value="'+ that.data('id') +'" />');
                    that.addClass('selected');
                }
            },
            addToCart: function (e) {
                e.preventDefault();

                var that = $(this),
                data = that.serialize();

                that.append('<div class="ptp-loading">Saving...</div>');
                $.post(PTPImporter_Vars_Frontend.ajaxurl, data, function(res) {
                    res = $.parseJSON(res);

                    if( res.success ) {
                        $('.products-added').fadeIn().siblings().hide();
                    } else {
                        console.log(res.error);
                    }

                    $('.ptp-loading').remove();
                });
            } 
        },
        Cart: {
            show: function (e) {
                var that = $(this);

                $.post(PTPImporter_Vars_Frontend.ajaxurl, { action: 'ptp_get_refreshed_fragments' }, function(res) {
                    res = $.parseJSON(res);

                    if ( res && res.fragments ) {
                        $.each( res.fragments, function( key, value ) {
                            $(key).replaceWith(value);
                        });

                        that.addClass('show');
                    }
                });
            },
        }
    };

    //dom ready
    $(function() {
        PTPImporter_Frontend.init();

        $('.quick-order-dialog').dialog({
            autoOpen: false,
            modal: true,
            dialogClass: 'ptp-ui-dialog',
            width: 485,
            height: 130
        });
        $('.datepicker').datepicker();
    });

})(jQuery);
