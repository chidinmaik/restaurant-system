(function($){
    'use strict';

    $('[data-module="announcement"]').each(function () {
        new CN.Announcement($(this));
    });

})(jQuery);