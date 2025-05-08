(function($) {
    'use strict';

    $(document).on('cnInitTabbedLayout', function (event, data) {
        $('.js-layout-builder-tabbed').easytabs({
            tabActiveClass: 'is-active',
            tabs: '.c-tabbed-content__tabs > li',
            animate: false,
            updateHash: false,
            defaultTab: 'li#defaulttab'
        });
    });

    $(document).on('easytabs:before', '.js-layout-builder-tabbed', function (event, $clicked, $targetPanel) {
        $(this).removeClass('is-default-state');
    });

    $(document).on('easytabs:after', '.js-layout-builder-tabbed', function (event, $clicked, $targetPanel) {
        $(document).trigger('resize');
    });

    $(document).trigger('cnInitTabbedLayout');

    $(document).on('ajaxModalHasLoaded', function (event, data) {
        $(document).trigger('cnInitTabbedLayout');
    });

})(jQuery);