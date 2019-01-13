jQuery(document).ready(function($) {
    var $ = jQuery;

    // Make it easy for copy/paste
    $('.orbisius_support_tickets_selectable').on('click', function (e) {
        $(this).select();
    } );

    $(".orbisius_support_tickets_dropdown_field").chosen(); // {disable_search_threshold: 10}
} );