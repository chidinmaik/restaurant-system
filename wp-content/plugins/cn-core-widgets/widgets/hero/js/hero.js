(function($) {
    'use strict';

    $('.c-hero').each(function() {
        var heroEl = $(this),
            videoEl = heroEl.find('.js-hero-video');

        if (videoEl.length) {
            var player = new Plyr(videoEl[0]);
        }
    });

    $('.c-hero__backgrounds').each(function() {
        new CN.ElementCycle($(this));
    });

})(jQuery);
