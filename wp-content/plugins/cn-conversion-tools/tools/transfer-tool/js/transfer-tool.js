var CN = CN || {};

CN.TransferTool = function(el) {
    this.elements = {
        base: el,
        body: $('body'),
        toggleButton: el.find('.js-transfer-tool-toggle'),
        ctaButton: el.find('.js-transfer-tool-button')
    };

    this.options = el.data('module-options');

    this.bindEvents();

    this.trackInitialShow();
};

CN.TransferTool.prototype.bindEvents = function () {
    this.elements.toggleButton.on('click', this.toggleState.bind(this));
    this.elements.ctaButton.on('click', this.trackCtaClick.bind(this));
};

CN.TransferTool.prototype.toggleState = function (e) {
    e.preventDefault();

    if (this.elements.base.hasClass('is-active')) {
        this.triggerEvent('close');
    } else {
        this.triggerEvent('open');
    }

    this.elements.base.toggleClass('is-active');
};

CN.TransferTool.prototype.trackInitialShow = function () {
    if (document.documentElement.clientWidth >= this.options.breakpoint) {
        this.triggerEvent('show');
    }
};

CN.TransferTool.prototype.trackCtaClick = function () {
    this.triggerEvent('click');
};

CN.TransferTool.prototype.triggerEvent = function (type) {
    if ('ga' in window && typeof ga.getAll === 'function') {
        var label = this.options.label;

        $.each(ga.getAll(), function(index, tracker) {
            tracker.send('event', 'transfer_tool', type, label, {
                nonInteraction: true
            });
        });
    }
};

$('[data-module="transfer-tool"]').each(function () {
    new CN.TransferTool($(this));
});
