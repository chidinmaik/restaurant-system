var CN = CN || {};

/**
 * Create the object literal
 * @param {object} el The object element with the data-attribute
 */
CN.ElementCycle = function(el) {

    this.elements = el.children();

    this.interval = el.data('element-cycle-interval');

    this.currentElement = 0;

    this.init();
};

CN.ElementCycle.prototype.init = function() {

    var _this = this;

    this.nextElement();

    setInterval(function () {
        _this.nextElement();
    }, this.interval);
};

CN.ElementCycle.prototype.nextElement = function() {

    var _this = this;

    $.each(this.elements, function(element) {

        if (_this.currentElement === element) {
            $(this).addClass('is-active');
        } else {
            $(this).removeClass('is-active');
        }
    });

    this.currentElement++;

    if (this.currentElement >= this.elements.length) {
        this.currentElement = 0;
    }
};