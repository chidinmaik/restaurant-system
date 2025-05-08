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

    $('[data-module="cta-grid-filter"]').each(function() {
        var options = $(this).data('module-options');

        new CN.GridFilter($(this), {
            'item': '.c-cta',
            'layoutMode': options.layout
        });
    });

    var filterGrid = getParameterByName('filter-grid');

    if (filterGrid) {
        var filterValues = filterGrid.split(',');

        $.each(filterValues, function (index, filterValue) {
            var foundMatch = false;

            $('[data-module="cta-grid-filter"] .js-filter').each(function() {
                var filterChildren = $(this).children();
                var filterOptions = (filterChildren.length) ? $(this).children('option') : $(this);

                $.each(filterOptions, function() {
                    var filterOption = $(this);

                    if (filterOption.val() === filterValue) {
                        filterOption.attr('selected', 'selected');
                        filterOption.attr('checked', 'checked');
                        foundMatch = true;
                        return;
                    }
                });

                if (foundMatch) {
                    $(this).trigger('change');
                    return;
                }
            });
        });
    }

})(jQuery);