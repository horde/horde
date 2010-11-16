HordeMap.Geocoder.Null = Class.create({

    initialize: function(opts)
    {
    },

    geocode: function(address, callback, onErrorCallback)
    {
        return onErrorCallback('No geocoding support');
    },

    reverseGeocode: function(lonlat, completeCallback, errorCallback)
    {
        var ll = { lon: lonlat.lon, lat: lonlat.lat, address: lonlat.lon + ' ' + lonlat.lat};
        return completeCallback([ll]);
    }
});
