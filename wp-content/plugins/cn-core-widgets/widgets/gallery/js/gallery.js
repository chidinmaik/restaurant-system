(function($) {
    'use strict';

    $(document).on('cnInitGallery', function (event, data) {

        var element = data && data.element || document;

        $(element).find('[data-module="grid-image-filter"]').each(function(){
            new CN.GridFilter( $(this), {
                'itemSelector': '.js-thumbnail',
                'layoutMode': 'masonry'
            } );
        });

        $(element).find('[data-module="image-gallery"]').each(function(){
            new CN.ImageGallery( $(this) );
        });

        $('.pswp__button--close').on('click', function() {

            if (window.currentVideoPlayer) {
                try {
                    window.currentVideoPlayer.stop();
                }
                catch (err) {}
            }
        });

    });

    $(document).trigger('cnInitGallery');

})(jQuery);