HordeMap.Geocoder.Null = Class.create({

    initialize: function(opts)
    {
    },

    geocode: function(address, callback, onErrorCallback)
    {
        // Try to get a lat/long out of this.
        var ll = address.match(/(-?\d+\.\d+)\ (-?\d+\.\d+)/);
        if (ll) {
            return callback([{ lat: ll[1], lon: ll[2], 'address': address, precision: 1 }]);
        }

        return onErrorCallback('No geocoding support. Try entering a longitude latitude pair.');
    },

    reverseGeocode: function(lonlat, completeCallback, errorCallback)
    {
        var ll = { lon: lonlat.lon, lat: lonlat.lat, address: lonlat.lat + ' ' + lonlat.lon, precision: 0 };
        return completeCallback([ll]);
    }
});
