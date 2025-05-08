(function($) {
    'use strict';

    $(document).on('cnInitMediaWidget', function (event, data) {
        $('.c-media').each(function() {
            var mediaEl = $(this),
                options = $(this).data('module-options'),
                videoEl = mediaEl.find('.c-media__video'),
                audioEl = mediaEl.find('.c-media__audio'),
                actionEl = mediaEl.find('.c-media__actions li'),
                galleryTriggerEl = mediaEl.find('[data-module="media-gallery-trigger"]');

            if (videoEl.length) {
                var player = new Plyr(videoEl[0]);
            }

            if(audioEl.length) {
                let audioPlayer = audioEl.find('audio')[0]

                audioEl.find('.js-media-audio-play').on('click', function(e) {
                    e.preventDefault()

                    if(audioPlayer.paused){
                        audioPlayer.play()
                    } else {
                        audioPlayer.pause()
                    }
                })

                $(audioPlayer).on({
                    'play': function(){
                        audioEl.addClass('is-playing')
                    },
                    'pause ended': function(){
                        audioEl.removeClass('is-playing')
                    },
                    'canplay': function() {
                        $(this).trigger('displayTime', { time: this.duration })
                    },
                    'timeupdate': function(e){
                        $(this).trigger('displayTime', { time: this.currentTime })
                    },
                    'displayTime': function(e, data){
                        let minutes = "0" + Math.floor(data.time / 60);
                        let seconds = "0" + (Math.floor(data.time) - minutes * 60);
                        let timeDisplay = minutes.substr(-2) + ":" + seconds.substr(-2);

                        audioEl.find('.js-media-audio-time').html(timeDisplay)
                    }
                })
            }

            if (options.actionImageBehaviour === 'replace_on_hover') {
                actionEl.on('mouseenter mouseleave', function () {
                    var imageId = $(this).data('image-id');
                    var relatedImageEl = $('.c-media__image--' + imageId);

                    if (relatedImageEl.length) {
                        relatedImageEl.toggleClass('is-active');
                    }
                });
            }

            if (galleryTriggerEl.length) {
                new CN.ImageGallery(galleryTriggerEl);
            }
        });
    });

    $(document).trigger('cnInitMediaWidget');

})(jQuery);
