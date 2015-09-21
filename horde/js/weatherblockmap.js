var WeatherBlockMap = {

    // Map
    initializeMap: function(instance, point)
    {
        if (this.mapInitialized) {
            return;
        }
        var layers = [];
        var p = new HordeMap.Owm();
        $H(p.getLayers()).values().each(function(e) {
            if (e.name == 'OpenWeatherMap Wind Map') {
                e.visibility = false;
            }
            console.log(e);
            layers.push(e);
        });
        p = new HordeMap.Osm();
        $H(p.getLayers()).values().each(function(e) {
            e.displayInLayerSwitcher = false;
            layers.push(e);
        });

        var map = new HordeMap.Map['Horde']({
            elt: 'weathermaplayer_' + instance,
            layers: layers
        });

        map.display();
        map.setCenter(point, 7);
    }
}