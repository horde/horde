AnselBlockGeoTag = Class.create({

    _map: null,
    _imgs: null,

    initialize: function(imgs, opts)
    {
        this.opts = opts;
        AnselMap.initMainMap('ansel_map', {
            'onHover': function() { return true },
            'onClick': function(f) {
                var uri = f.feature.attributes.image_link;
                location.href = uri;
            }.bind(this),
           'defaultBaseLayer': opts.defaultBaseLayer,
           'onBaseLayerChange': this.updateBaseLayer.bind(this)
        });
        this.placeImages(imgs);
    },

    /**
     * Place image markers on the map.
     *
     * @param array imgs  An array of image definition hashes.
     */
    placeImages: function(imgs)
    {
        // Place the image markers
        for (var i = 0; i < imgs.length; i++) {
            var m = AnselMap.placeMapMarker(
                'ansel_map',
                {
                    'lat': imgs[i].image_latitude,
                    'lon': imgs[i].image_longitude
                },
                {
                    'img': (!imgs[i].markerOnly) ? imgs[i].icon : Ansel.conf.markeruri,
                    'background': (!imgs[i].markerOnly) ? Ansel.conf.pixeluri + '?c=ffffff' : Ansel.conf.markerBackground,
                    'image_id': imgs[i].image_id,
                    'markerOnly': (imgs[i].markerOnly) ? 'markerOnly' : 'noMarkerOnly',
                    'center': true,
                    'image_link': imgs[i].link
                }
            );
        }
    },

    updateBaseLayer: function(l)
    {
        new Ajax.Request(this.opts.layerUpdateEndpoint, {
            method: 'post',
            parameters: {
                pref: this.opts.layerUpdatePref,
                value: l.layer.name
            },
            onComplete: function(transport) {
                 if (typeof Horde_ToolTips != 'undefined') {
                     Horde_ToolTips.out();
                 }
             }.bind(this)
        });
    }

});
