var CN = CN || {};

CN.Announcement = function(el) {
    this.elements = {
        base: el,
        body: $('body'),
        innerContainer: el.find('.js-announcement-inner'),
        actionButton: el.find('.js-announcement-button'),
        dismissButton: el.find('.js-announcement-dismiss'),
    };

    this.options = el.data('module-options');

    if (this.options.dismissAction !== 'disabled') {
        this.elements.actionButton.on('click', this.closeAnnouncement.bind(this));
    }

    this.elements.dismissButton.on('click', this.closeAnnouncement.bind(this));

    this.handleLegacyBrowsers();

    if (this.announcementIsActive()) {
        if (this.options.type === 'exit') {
            this.elements.body.on('mouseleave', this.revealExit.bind(this))
        }
        else {
            this.openAnnouncement();
            this.initModules();
        }
    }
};

CN.Announcement.prototype.announcementIsActive = function () {
    if (this.options.previewMode) {
        return this.options.userLoggedIn;
    }

    if (!this.options.schedule.start && !this.options.schedule.stop) {
        return true;
    }

    var now = Math.floor(new Date().getTime() / 1000);

    if (this.options.schedule.start && this.options.schedule.start > now) {
        return false;
    }

    if (this.options.schedule.stop && this.options.schedule.stop < now) {
        return false;
    }

    return true;
};

CN.Announcement.prototype.openAnnouncement = function () {
    var _this = this;

    if (!sessionStorage.getItem(this.options.id)) {
        setTimeout(function () {
            _this.elements.base.addClass('is-active');
            _this.triggerEvent('open');
        }, this.options.delay);
    }
};

CN.Announcement.prototype.closeAnnouncement = function (event) {
    var buttonEl = $(event.currentTarget);

    if (buttonEl.hasClass('js-announcement-button')) {
        this.triggerEvent('click');
    }

    if (buttonEl.hasClass('js-announcement-dismiss')) {
        this.triggerEvent('dismiss');
    }

    this.elements.base.removeClass('is-active');
    this.setCookie();
};

CN.Announcement.prototype.initModules = function () {
    this.elements.base.find('[data-module="countdown"]').each(function () {
        new CN.Countdown($(this));
    });
};

CN.Announcement.prototype.setCookie = function () {
    let timestamp = new Date().getTime();
    sessionStorage.setItem(this.options.id, timestamp);
};

CN.Announcement.prototype.triggerEvent = function (type) {
    $(window).trigger(type + '.cnAnnouncements', {
        elements: this.elements,
        options: this.options
    });

    if ('ga' in window && typeof ga.getAll === 'function') {
        var label = this.options.label;

        $.each(ga.getAll(), function(index, tracker) {
            tracker.send('event', 'announcement', type, label, {
                nonInteraction: true
            });
        });
    }
};

CN.Announcement.prototype.revealExit = function (event) {
    this.openAnnouncement();
};

CN.Announcement.prototype.handleLegacyBrowsers = function () {
    var isIE11 = !!window.MSInputMethodContext && !!document.documentMode;

    if (this.options.type === 'modal' || this.options.type === 'exit' && isIE11) {
        this.elements.innerContainer.css('max-width', this.options.maxWidth);
    }
};
