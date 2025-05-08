(function($) {
    'use strict';

    $('[data-module="image-gallery-trigger"]').each(function(){
        new CN.ImageGallery( $(this) );
    });

})(jQuery);