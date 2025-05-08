var CN = CN || {};

CN.ClassicBookingForm = function (el) {
    var _this = this;
    var baseEl = $(el);

    this.checkInDateEl = baseEl.find('input[name="checkInDate"]');
    this.checkOutDateEl = baseEl.find('input[name="checkOutDate"]');
    this.stepperEl = baseEl.find('.js-booking-form-stepper');

    this.options = baseEl.data('module-options');

    this.bindDatepickers();

    this.stepperEl.each(function () {
        _this.bindStepper(this);
    });
};

CN.ClassicBookingForm.prototype.bindDatepickers = function () {
    var _this = this;

    var defaultPickerOptions = {
        format: window.cnBookingConfig.inputDateFormat,
        formatSubmit: window.cnBookingConfig.submitDateFormat,
        hiddenName: true,
        firstDay: 1
    };

    if (this.options.datepickerContainer === 'body') {
        defaultPickerOptions.container = '.js-pickadate-container';
    }

    this.checkInDateEl.pickadate($.extend({}, defaultPickerOptions, {
        min: true,
        onSet: function(option) {
            if (option.hasOwnProperty('select')) {
                // Update check-out datepicker to be a day after the new check-in date
                _this.checkOutDateEl.pickadate('picker').set('min', new Date(option.select + 86400000));
                _this.checkOutDateEl.pickadate('picker').set('select', option.select + 86400000);
            }
        }
    }));

    this.checkOutDateEl.pickadate($.extend({}, defaultPickerOptions, {
        min: 1
    }));
};

CN.ClassicBookingForm.prototype.bindStepper = function (el) {
    var incrementEl = $(el).find('.js-booking-form-step-increment'),
        decrementEl = $(el).find('.js-booking-form-step-decrement'),
        inputEl = $(el).find('.js-booking-form-input'),
        valueEl = $(el).find('.js-booking-form-value'),
        inputMin = parseInt(inputEl.attr('min')),
        inputMax = parseInt(inputEl.attr('max'));

    incrementEl.on('click', function () {
        var value = parseInt(inputEl.val());

        if (value++ < inputMax) {
            inputEl.val(value);
            valueEl.html(value);
        }
    });

    decrementEl.on('click', function (e) {
        var value = parseInt(inputEl.val());

        if (value-- > inputMin) {
            inputEl.val(value);
            valueEl.html(value);
        }
    });
};