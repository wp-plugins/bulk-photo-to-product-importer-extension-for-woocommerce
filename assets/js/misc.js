;(function ($) {
	//dom ready
    $(function() {
        $('#ptp-cols, .share, .add-manage-wrap').show();

        // Bulk Importer: Tooltips
        $( '.ptp .form-field .tooltip-icon' ).hover(
            function() {
                var that = $( this ),
                    contentLeft = $('#wpbody-content').offset().left < that.offset().left - 143 ? that.position().left - 90 : 0,
                    arrowLeft = that.offset().left - $('#wpbody-content').offset().left > 150 ? 101 : that.offset().left - $('#wpbody-content').offset().left;

                that.closest( '.form-field' ).find( '.tooltip-content' ).show().css({
                    'top': that.position().top + 26 + 'px',
                    'left': contentLeft - 6.5 + 'px'
                });

                that.closest( '.form-field' ).find( '.tooltip-arrow' ).show().css({
                    'left': arrowLeft + 'px'
                });
            },
            function() {
                var that = $( this );

                that.closest( '.form-field' ).find( '.tooltip-content, .tooltip-arrow' ).hide();
            }
        );
        // Menu
        if ( $('#toplevel_page_ptp_bulk_import').hasClass('wp-has-current-submenu') )
            $('#toplevel_page_ptp_bulk_import').prop('id', 'ptp-toplevel-menu-active');
        else
            $('#toplevel_page_ptp_bulk_import').prop('id', 'toplevel_page_ptp_bulk_import');
    });

})(jQuery);