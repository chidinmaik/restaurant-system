(function($) {
    'use strict';

    var selectors = {
        bookingBar : '.c-booking-bar',
        datepickerEl : $('.c-booking-bar__date'),
        arrivalEl : $('.c-booking-bar--arrive .c-booking-bar__date'),
        departure : '.c-booking-bar--depart .c-booking-bar__date',
    }

    selectors.datepickerEl.pickadate({
        min: true,
        container: '.js-pickadate-container',
        formatSubmit: 'yyyy-mm-dd',
        hiddenName: true,
        format: window.cnBookingConfig.inputDateFormat
    });

    /**
     * Pre-fill the depature date after the arrival date is selected
     */
    if ( selectors.arrivalEl.length > 0 ) {
        selectors.arrivalEl.each(function() {
            var thisEl = $(this);

            thisEl.pickadate('picker').on({
                set: function( setThing ){
                    var timeSelected = setThing.select;

                    /**
                     * Add one day (in miliseconds) to the selected date
                     */
                    thisEl.parents(selectors.bookingBar).find(selectors.departure).each(function() {
                        $(this).pickadate('picker')
                            .set('select', timeSelected + 1000 * 60 * 60 * 24 )
                            .set('min', new Date(timeSelected + 1000 * 60 * 60 * 24) );
                    });
                }
            });
        });
    }

})(jQuery);
