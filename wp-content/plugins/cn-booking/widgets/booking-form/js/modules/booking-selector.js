var CN = CN || {};

/**
 * Create the object literal
 * @param {object} el The object element with the data-attribute
 */
CN.BookingSelector = function() {

    this.elements = {
        base: $("[data-booking-selector-form]"),
        selector: $("[data-booking-selector-form] [data-booking-selector]"),
        hiddenInputs: $("[data-booking-selector-form] .hidden-inputs")
    };

    this.data = {};

    if ( "undefined" !== typeof ihotelierBookingLocations ) {
        this.data.locations = ihotelierBookingLocations;
    }

    this.init();
};

CN.BookingSelector.prototype.init = function() {

    this.changeEvent();
};

CN.BookingSelector.prototype.changeEvent = function() {

    var $this = this;

    this.elements.selector.on('change', function(){
        var val = $(this).find('option:selected').val();

        $this.updateSelectedOption(val);

        var selectedLocation = $this.getSelectedLocation(val);

        if( selectedLocation ) {
            $this.elements.base.each(function(){
                var $self = $(this);

                $self.attr('action', selectedLocation.booking_url);
                $self.removeClass(function(index, className){
                    var existingClass = className.match(/(^|\s)c-booking-bar--location\S+/g);
                    if ( existingClass ) {
                        return existingClass.join(' ');
                    }
                    return '';
                });
                $self.addClass('c-booking-bar--location-'+ selectedLocation.original_key);
            });

            $this.elements.hiddenInputs.html('');

            $.each(selectedLocation.hidden_inputs, function(index,object){
                $this.elements.hiddenInputs.append('<input type="hidden" name="'+ $(this)[0].name +'" value="'+ $(this)[0].value +'">');
            });
        }
    });
};

CN.BookingSelector.prototype.updateSelectedOption = function( $value ) {
    var $this = this;

    this.elements.selector.each(function(){
        $(this).find('option').removeAttr('selected');
        $(this).val($value);
    })
};

CN.BookingSelector.prototype.getSelectedLocation = function( $value ) {

    var $this = this;
    var selected = false;

    if ($this.data.locations) {

        $.each($this.data.locations, function(index,object) {
            if (object.identifier === $value) {
                selected = object;
                return false;
            }
        });
    }

    return selected;
};