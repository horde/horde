AnselImageView = {
    urls: {},
    arrowHandler: function(e)
    {
        if (e.altKey || e.shiftKey || e.ctrlKey) {
            return;
        }

        theElement = Event.element(e);
        switch (theElement.tagName) {
        case 'INPUT':
        case 'SELECT':
        case 'TEXTAREA':
            return;
        }
        switch (e.keyCode || e.charCode) {
        case Event.KEY_LEFT:
            if ($('PrevLink')) {
                document.location.href = $('PrevLink').href;
            }
            break;

        case Event.KEY_RIGHT:
            if ($('NextLink')) {
                document.location.href = $('NextLink').href;
            }
            break;
        }
    },

    onload: function()
    {
        Event.observe($('ansel-photodiv'), 'load', function() {
            new Effect.Appear($('ansel-photodiv'), {
                duration: 0.5,
                afterFinish: function() {
                    $$('.imgloading').each(function(n) { n.setStyle({ visibility: 'hidden' }) });
                   new Effect.Appear($('anselcaption'), { duration: 0.5 });
                }
            });
            var nextImg = new Image();
            var prvImg = new Image();
            nextImg.src = AnselImageView.nextImgSrc;
            prvImg.src = AnselImageView.prevImgSrc;
        });
        new Effect.Opacity('ansel-photodiv', {
            to: 0,
            duration: 0.5,
            afterFinish: function() { $('ansel-photodiv').src = AnselImageView.urls['imgsrc'] }
        });

        // Arrow keys for navigation
        document.observe('keydown', AnselImageView.arrowHandler);
    }
};

//document.observe('dom:loaded', AnselImageView.onload);