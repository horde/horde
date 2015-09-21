/**
 * OpenWeatherMap layer (See http://openweathermap.org/tile_map#list)
 *
 * Copyright 2015 Horde LLC (http://www.horde.org/)
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
                    ['http://${s}.tile.openweathermap.org/map/clouds/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            ),
            'precipitation': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Precipitation Map',
                    ['http://${s}.tile.openweathermap.org/map/precipitation/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            ),
            'rain': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Rain Map',
                    ['http://${s}.tile.openweathermap.org/map/rain/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            ),
            'snow': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Snow Map',
                    ['http://${s}.tile.openweathermap.org/map/snow/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            ),
            'pressure_cntr': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Sea-Level Pressure Map',
                    ['http://${s}.tile.openweathermap.org/map/pressure_cntr/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            ),
            'wind': new OpenLayers.Layer.XYZ(
                'OpenWeatherMap Wind Map',
                    ['http://${s}.tile.openweathermap.org/map/wind/${z}/${x}/${y}.png'],
                    {
                        'isBaseLayer': false,
                        'sphericalMercator': true,
                        'opacity': 0.7
                    }
            )
        };
    }
});