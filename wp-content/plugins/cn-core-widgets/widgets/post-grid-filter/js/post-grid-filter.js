(function($) {
    'use strict';

    /**
     * Utlity function to return part of a URL string
     * e.g. var foo = getParameterByName('foo')
     */
    function getParameterByName(name) {
        var url = window.location.search.substring(1);
        var params = url.split('&');
        var value = null;

        $.each(params, function (index, param) {
            var param = param.split('=');

            if (param[0] === name) {
                value = param[1];
            }
        });

        return value;
    }

    $('[data-module="grid-filter"]').each(function(){
        new CN.GridFilter( $(this) );
    });

    var filterGrid = getParameterByName('filter-grid');

    if (filterGrid) {
        var filterValues = filterGrid.split(',');

        $.each(filterValues, function (index, filterValue) {
            var foundMatch = false;

            $('[data-module="grid-filter"] .js-filter').each(function() {
                var filterChildren = $(this).children();
                var filterOptions = (filterChildren.length) ? $(this).children('option') : $(this);

                $.each(filterOptions, function() {
                    var filterOption = $(this);

                    if (filterOption.val() === filterValue) {
                        filterOption.prop('selected', 'selected');
                        filterOption.prop('checked', 'checked');
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