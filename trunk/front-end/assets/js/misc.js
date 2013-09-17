;(function ($) {
    //dom ready
    $(function() {
        // Center dialog
        $('.ui-dialog').css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +  $(window).scrollTop()) + "px");
        $('.ui-dialog').css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +  $(window).scrollLeft()) + "px");
    });

})(jQuery);
