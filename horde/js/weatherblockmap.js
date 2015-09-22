var WeatherBlockMap = {

    // Map
    initializeMap: function(instance, point)
    {
        if (this.mapInitialized) {
            return;
        }
        var layers = [], p = new HordeMap.Owm(), map, dimensions;

        $H(p.getLayers()).values().each(function(e) {
            if (e.name == 'OpenWeatherMap Wind Map') {
                e.visibility = false;
            }
            layers.push(e);
        });
        p = new HordeMap.Osm();
        $H(p.getLayers()).values().each(function(e) {
            e.displayInLayerSwitcher = false;
            layers.push(e);
        });

        map = new HordeMap.Map['Horde']({
            elt: 'weathermaplayer_' + instance,
            layers: layers,
            panzoom: false
        });


        dimensions = $('weathermaplayer_' + instance).up().up().getDimensions();
        $('weathermaplayer_' + instance).setStyle({ top: 0, width: ((dimensions.width / 2) + 10) + 'px', height: dimensions.height + 'px' });
        map.updateMapSize();
        map.setCenter(point, 7);
        map.display();
    }
}