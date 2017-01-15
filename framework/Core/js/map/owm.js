/**
 * OpenWeatherMap layer (See http://openweathermap.org/tile_map#list)
 *
 * Copyright 2015-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 *
 */
HordeMap.Owm = Class.create(
{
    initialize: function(opts)
    {
    },

    getLayers: function(layer)
    {
        return {
            'clouds': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Cloud Map',
                    ['http://a.tile.openweathermap.org/map/clouds/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/clouds/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/clouds/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            ),
            'precipitation': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Precipitation Map',
                    ['http://a.tile.openweathermap.org/map/precipitation/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/precipitation/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/precipitation/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            ),
            'rain': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Rain Map',
                    ['http://a.tile.openweathermap.org/map/rain/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/rain/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/rain/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            ),
            'snow': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Snow Map',
                    ['http://a.tile.openweathermap.org/map/snow/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/snow/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/snow/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            ),
            'pressure_cntr': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Sea-Level Pressure Map',
                    ['http://a.tile.openweathermap.org/map/pressure_cntr/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/pressure_cntr/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/pressure_cntr/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            ),
            'wind': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Wind Map',
                    ['http://a.tile.openweathermap.org/map/wind/${z}/${x}/${y}.png',
                    'http://b.tile.openweathermap.org/map/wind/${z}/${x}/${y}.png',
                    'http://c.tile.openweathermap.org/map/wind/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.5
                    }
            )
        };
    }
});