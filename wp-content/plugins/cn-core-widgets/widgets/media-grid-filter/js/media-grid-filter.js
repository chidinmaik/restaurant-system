(function($) {
    'use strict';

    /**
     * Utlity function to return part of a URL string
     * e.g. var foo = getParameterByName('foo')
     */
    function getParameterByName(name, url) {
        if (!url) {
          url = window.location.href;
        }
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    $('[data-module="media-grid-filter"]').each(function(){
        new CN.GridFilter($(this), {
            'item': '.c-media'
        });
    });

    var filterGrid = getParameterByName('filter-grid');

    if ('' !== filterGrid) {
        var foundOption = false;
        $('[data-module="media-grid-filter"] .js-filter').each(function() {
            $.each($(this).children('option'), function(){
                if ( filterGrid === $(this).val() ) {
                    $(this).attr("selected", "selected");
                    foundOption = true;
                    return;
                }
            });
            if (foundOption) {
                $(this).trigger('change');
                return;
            }
        });
    }

})(jQuery);