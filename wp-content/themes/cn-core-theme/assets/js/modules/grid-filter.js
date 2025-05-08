var CN = CN || {};

/**
 * Create the object literal
 * @param {object} el The object element with the data-attribute
 */
CN.GridFilter = function(el, customSettings) {

    // Ability to pass custom settings array
    this.customSettings = customSettings || {};

    this.elements = {
        base: el,
    };

    // Setup options based on any customSettings added
    this.setupOptions();

    this.grid = {};

    // Initialise this module
    this.init();
};

CN.GridFilter.prototype.init = function() {

    var _this = this;

    // Initialise Isotope grid
    _this.grid = _this.elements.container.isotope({
        itemSelector: _this.customSettings.itemSelector,
        layoutMode: _this.customSettings.layoutMode,
        stagger: _this.customSettings.stagger,
        transitionDuration: _this.customSettings.transitionDuration,
        getSortData: _this.customSettings.getSortData,
        sortBy: 'sortOrder',
        sortAscending: _this.customSettings.sortAscending,
        masonry: {
            columnWidth: '.grid-sizer'
        }
    });

    // Add classes for hidden Isotope elements
    var itemHide   = Isotope.Item.prototype.hide;
    var itemReveal = Isotope.Item.prototype.reveal;

    Isotope.Item.prototype.hide = function() {
        itemHide.apply( this, arguments );
        $( this.element ).addClass('isotope-hidden');
    };

    Isotope.Item.prototype.reveal = function() {
        itemReveal.apply( this, arguments );
        $( this.element ).removeClass('isotope-hidden');
    };

    // Perform initial filter
    _this.filter();

    // Watch for filter changes
    _this.elements.filterEl.on('change', function() {
        _this.filter();
    });

    // Relayout the grid after SiteOrigin stretches rows
    (function(grid) {
        return $( window ).on( 'panelsStretchRows', function() {
            if(grid && grid.data('isotope')) {
                grid.isotope('layout');
            }
        } );
    })(_this.grid);
};

CN.GridFilter.prototype.filter = function() {

    var _this = this,
        filter = _this.customSettings.defaultFilter;

    _this.elements.filterSelect.each(function(index) {

        var filterType = $(this).data('grid-filter'),
            filterValue = $(this).val(),
            filterNode = $(this).prop('nodeName');

        switch (filterNode) {

            case 'SELECT':
                if (filterValue && filterValue !== '*') {
                    if (filterValue && typeof filterValue === 'object') {
                        $.each(filterValue, function (key, value) {
                            filter += '[data-filter-' + filterType + '*=' + value + ']';
                        });
                    } else {
                        filter += '[data-filter-' + filterType + '*=' + filterValue + ']';
                    }

                    _this.elements.base.find('[data-filter-term]').removeClass('is-active');
                    _this.elements.base.find('[data-filter-term="' + filterValue + '"]').addClass('is-active');
                }
                break;

            case 'INPUT':
                if (filterValue === '*') {
                    _this.elements.base.find('[data-filter-term]').removeClass('is-active');
                }

                if ($(this).prop('checked') && filterValue !== '*') {
                    filter += '[data-filter-' + filterType + '*=' + filterValue + '], ';
                    _this.elements.base.find('[data-filter-term]').removeClass('is-active');
                    _this.elements.base.find('[data-filter-term="' + filterValue + '"]').addClass('is-active');
                }

                if (filterValue === '*') {
                    _this.elements.base.find('[data-filter-term="' + filterValue + '"]').addClass('is-active');
                }
                break;
        }
    });

    var separatorPosition = filter.lastIndexOf(', ');

    filter = filter.substring(0,separatorPosition) + filter.substring(separatorPosition+1);

    _this.grid.isotope({ filter: filter.replace(/\s+/g, '') });

    $(_this.customSettings.itemSelector).removeAttr('data-grid-item-index');

    $.each(_this.grid.data('isotope').filteredItems, function (index, item) {
        var el = $(item.element);
        el.attr('data-grid-item-index', index);
    });

    var noResultsText = _this.elements.base.find('.js-no-results');
    if (noResultsText){
        if (_this.grid.data('isotope').filteredItems.length < 1 ){
            noResultsText.show();
        }else{
            noResultsText.hide();
        }
    }
};

CN.GridFilter.prototype.setupOptions = function() {

    var _this = this;

    /**
     * Firstly check for element selectors
     */
    if (typeof _this.customSettings.container === 'undefined') {
        _this.elements.container = _this.elements.base.find('.js-grid-container');
    } else {
        _this.elements.container = _this.customSettings.container;
    }
    if (typeof _this.customSettings.item === 'undefined') {
        _this.elements.item = _this.elements.base.find('.js-grid-item');
    } else {
        _this.elements.item = _this.customSettings.item;
    }
    if (typeof _this.customSettings.filterEl === 'undefined') {
        _this.elements.filterEl = _this.elements.base.find('.js-filter');
    } else {
        _this.elements.filterEl = _this.customSettings.filterEl;
    }
    if (typeof _this.customSettings.filterSelect === 'undefined') {
        _this.elements.filterSelect = _this.elements.base.find('[data-grid-filter]');
    } else {
        _this.elements.filterSelect = _this.customSettings.filterSelect;
    }
    if (typeof _this.customSettings.defaultFilter === 'undefined') {
        _this.customSettings.defaultFilter = '';
    }

    /**
     * Check for isotope init settings
     */
    if (typeof _this.customSettings.itemSelector === 'undefined') {
        _this.customSettings.itemSelector = '.js-grid-item';
    }
    if (typeof _this.customSettings.layoutMode === 'undefined') {
        _this.customSettings.layoutMode = 'fitRows';
    }
    if (typeof _this.customSettings.stagger === 'undefined') {
        _this.customSettings.stagger = 50;
    }
    if (typeof _this.customSettings.transitionDuration === 'undefined') {
        _this.customSettings.transitionDuration = 400;
    }
    if (typeof _this.customSettings.sortAscending === 'undefined') {
        _this.customSettings.sortAscending = true;
    }
    if (typeof _this.customSettings.getSortData === 'undefined') {
        _this.customSettings.getSortData = {
            sortOrder: '[data-sort-order] parseInt'
        }
    }
};