HordeMap.Geocoder.Null = Class.create({

    initialize: function(opts)
    {
    },

    geocode: function(address, callback, onErrorCallback)
    {
        // Try to get a lon/lat out of this.
        var ll = address.match(/(-?\d+\.\d+)\ (-?\d+\.\d+)/);
        if (ll) {
            return callback([{ lat: ll[2], lon: ll[1], 'address': address, precision: 1 }]);
        }

        return onErrorCallback('No geocoding support. Try entering a longitude latitude pair.');
    },

    reverseGeocode: function(lonlat, completeCallback, errorCallback)
    {
        var ll = { lon: lonlat.lon, lat: lonlat.lat, address: lonlat.lon + ' ' + lonlat.lat };
        return completeCallback([ll]);
    }
});
