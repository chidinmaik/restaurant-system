var CN = CN || {};

CN.ImageGallery = function(el) {

    this.elements = {
        base: el,
        photoswipe: $('.js-photoswipe-container')
    };

    // Initialise this module
    this.init();
};

CN.ImageGallery.prototype.init = function() {

    var _this = this;
    var inlineVideos = [];

    // Handle playing/pause state for native html5 video thumbs
    this.elements.base[0].querySelectorAll('video').forEach((videoThumb) => {
        videoThumb.classList.add('is-paused')
        videoThumb.muted = true

        videoThumb.addEventListener('play', () => {
            videoThumb.classList.add('is-playing')
            videoThumb.classList.remove('is-paused')
        })

        videoThumb.addEventListener('pause', () => {
            videoThumb.classList.add('is-paused')
            videoThumb.classList.remove('is-playing')
        })
    })

    if(!_this.elements.photoswipe.find('.pswp__slide-indicators').length){
        _this.elements.photoswipe.find('.pswp__ui').append($('<div class="pswp__slide-indicators"></div>'))
    }

    _this.elements.base.on('click', '.js-gallery-trigger', function(e) {
        e.preventDefault();

        var _thisEl = $(this),
            parentThumbnailEl = ($(_thisEl).hasClass('js-thumbnail')) ? _thisEl : _thisEl.parents('.js-thumbnail'),
            thumbnailsEl = _this.elements.base.find('.js-thumbnail:not(.isotope-hidden)'),
            index = thumbnailsEl.index(parentThumbnailEl);

        var images = thumbnailsEl.get().map( function(item) {
            var itemEl = $(item),
                dataEl = itemEl.children().first();

            if (dataEl.data('media-type') === 'video') {
                var data = {
                    html: dataEl.data('html')
                };
            } else {
                var data = {
                    title: dataEl.data('caption'),
                    src: dataEl.attr('href'),
                    w: dataEl.data('width'),
                    h: dataEl.data('height')
                };
            }

            return data;
        });

        var videos = thumbnailsEl.find('video')
            if(videos) {
                videos.each((_, video) => {
                    video.pause()

                    inlineVideos.push(video)
                });
            }

        var options = {
            index: index,
            history: false,
            showHideOpacity: true,
            getThumbBoundsFn: function(index) {
                var thumbnailEl = _thisEl.find('img, .c-image')[0];

                if (thumbnailEl) {
                    var pageYScroll = window.pageYOffset || document.documentElement.scrollTop;
                    var rect = thumbnailEl.getBoundingClientRect();

                    return {
                        x: rect.left,
                        y: rect.top + pageYScroll,
                        w: rect.width
                    };
                }
            }
        };

        var slideIndicators = _this.elements.photoswipe.find('.pswp__slide-indicators')

        slideIndicators.empty()

        images.forEach(function(){
            slideIndicators.append('<span class="pswp__slide-indicator"></div>')
        })

        var photoswipe = new PhotoSwipe(_this.elements.photoswipe[0], PhotoSwipeUI_Default, images, options);

        photoswipe.listen('initialZoomIn', function() {
            $('body').addClass('photoswipe-is-active');
        });

        photoswipe.listen('initialZoomOut', function() {
            $('body').removeClass('photoswipe-is-active');
        });

        photoswipe.listen('beforeChange', function() {
            var currentItem = photoswipe.currItem;

            if (currentItem.hasOwnProperty('html') && window.currentVideoPlayer) {
                try {
                    window.currentVideoPlayer.stop();
                }
                catch (err) {}
            }
        });

        photoswipe.listen('afterChange', function() {
            var currentItem = photoswipe.currItem;

            if (currentItem.hasOwnProperty('html')) {
                var videoEl = $(currentItem.container).find('> div, > video');

                if (!videoEl.hasClass('plyr')) {
                    window.currentVideoPlayer = new Plyr(videoEl);
                }

                // Stop event propagation for the controls to prevent interference from PhotoSwipe
                if (videoEl.length) {
                    var controls = $(currentItem.container).find('.plyr__controls');
                    if (controls.length) {
                        controls.on('pointerdown touchstart mousedown', function (e) {
                            // Prevent the event from bubbling up to `pswp__scroll-wrap`
                            e.stopPropagation();
                        });
                    }
                }

                setTimeout(function () {
                    window.currentVideoPlayer.play();
                }, 1000);
            }

            slideIndicators.children().each((i, el) => $(el).toggleClass('is-active', photoswipe.getCurrentIndex() == i))

            $(document).trigger('photoSwipeSlideUpdate')
        });

        photoswipe.listen('afterInit', function() {
            photoswipe.shout('bindEvents')
            $(document).trigger('photoSwipeSlideUpdate')
        })

        // resume grid video on play
        photoswipe.listen('close', function() {
            if (!inlineVideos.length > 0) return
            inlineVideos[0].play()
        })

        photoswipe.init();
    });
};