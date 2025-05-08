(function($) {
    'use strict';

    $('.js-map-overlay').on('click', function(e) {
        var _this = $(this);
        _this.fadeOut(function(){
            _this.remove();
        });
    });

})(jQuery);