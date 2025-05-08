(function($) {
    'use strict';

    function cnBookingThrowErrorIfUndefined(object, requiredStructure) {
        for (var key in requiredStructure) {
            if (typeof object[key] === 'undefined') {
                throw new Error(key + ' is undefined.');
            }

            if (object[key] && typeof object[key] === 'object' && Object.keys(object[key]).length !== 0) {
                cnBookingThrowErrorIfUndefined(object[key], requiredStructure[key]);
            }
        }
    }

    var requiredStructure = {
        cnBookingConfig: {
            inputDateFormat: {},
            bookingEngine: {
                provider: {}
            }
        }
    };

    switch (window.cnBookingConfig.bookingEngine.provider) {
        case 'profitroom':
        case 'profitroom-multi':
            requiredStructure.cnBookingConfig.bookingEngine = {
                provider: {},
                params: {},
                locations: {},
                openOverlay: {}
            }
            break;
    }

    cnBookingThrowErrorIfUndefined(window, requiredStructure);

})(jQuery);
