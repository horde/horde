/**
 * Initial bootstrap file for hordemap.
 *
 * This file is responsible for loading the javascript for the map driver we are
 * using. Horde ships with a Horde driver that relies on OpenLayers.js. The
 * Horde driver is able to support any mapping provider that can serve map
 * tiles. We have built in support for Google, Yahoo, and Bing as well as built
 * in support for OpenStreetMaps. To write a new driver to support a new
 * provider, include a new {drivername}.js file in the same directory as this
 * file. Your js file is responsible for including any additional javascript
 * files you may need (such as externally served api files for example). Take
 * a look at the public.js or google.js files for the interface that needs to be
 * implemented.
 */
HordeMap = {

    Map: {},
    _includes: [],
    _opts: {},
    conf: {},

    /**
     * Initialize hordemap javascript
     *
     * @param object opts  Hash containing:
     *      'driver':    HordeMap driver to use (Horde | SAPO)
     *      'geocoder':  Geocoder driver to use
     *      'providers': Default provider layers to add (Google, Yahoo etc...)
     *      'jsuri':     The uri to the hordemap directory
     *
     *      'conf': Any driver specific config settings such as:
     *          'language':
     *          'apikeys': An object containing any api keys needed by the mapping
     *                     provider(s). {'google': 'xxxxx', ...}
     *          'useMarkerLayer': whether or not to use the 'built-in' marker
     *                            layer (only applies to the Horde driver).
     *
     *          'markerImage':  Path to a marker icon.
     *          'markerBackground':  Path to a marker icon background.
     */
    initialize: function(opts)
    {
        var path;
        this._opts = opts;
        if(!opts.jsuri) {
            path = this._getScriptLocation();
        } else {
            path = opts.jsuri;
        }
        this.conf = this._opts.conf;
        if (this._opts.driver == 'Horde') {
            this._addScript(path + 'OpenLayers.js');
            if (this._opts.conf.language != 'en-US') {
                this._addScript(path + '/lang' + this._opts.conf.language + '.js');
            }
        }
        
        this._addScript(path + this._opts.driver.toLowerCase() + '.js');

        if (this._opts.geocoder) {
            this._addScript(this._getProviderUrl(this._opts.geocoder));
            this._addScript(path + this._opts.geocoder.toLowerCase() + '.js');
        }

        if (this._opts.providers) {
            this._opts.providers.each(function(p) {
                var u = this._getProviderUrl(p);
                if (u) {
                    this._addScript(u);
                }
                this._addScript(path + p.toLowerCase() + '.js');
            }.bind(this));
        }

        this._includeScripts();
    },

    _includeScripts: function()
    {
        var files = this._includes;

        // Need to use document.write instead of inserting into DOM directly
        // to play nice with horde's javascript caching/loading
        var writeFiles = [];
        for (var i = 0, len = files.length; i < len; i++) {
            writeFiles.push('<script src="' + files[i] + '"></script>');
        }
        document.write(writeFiles.join(""));
    },

    _addScript: function(s)
    {
        if (s.length == 0) {
            return;
        }
        var l = this._includes.length;
        for (var i = 0; i < l; i++) {
            if (this._includes[i] == s) {
                return;
            }
        }
        this._includes.push(s);
    },

    /**
     * Return the path to this script.
     *
     * @return string Path to this script
     */
    _getScriptLocation: function () {
        var scriptLocation = "";
        var isMap = new RegExp("(^|(.*?\hordemap\/))(map.js)(\\?|$)");

        var scripts = document.getElementsByTagName('script');
        for (var i=0, len=scripts.length; i<len; i++) {
            var src = scripts[i].getAttribute('src');
            if (src) {
                var match = src.match(isMap);
                if(match) {
                    scriptLocation = match[1];
                    break;
                }
            }
        }
        return scriptLocation;
    },

    _getProviderUrl: function(p)
    {
        switch (p) {
        case 'Google':
            return 'http://maps.google.com/maps?file=api&v=2&sensor=false&key=' + this.conf['apikeys']['google'];
        case 'Yahoo':
            return 'http://api.maps.yahoo.com/ajaxymap?v=3.8&appid=' + this.conf['apikeys']['yahoo'];
        case 'Ve':
            return 'http://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6.1';

        default:
            return '';
        }
    },

    /**
     * Base Geocoder implementations.
     * The Horde Class will implement a geocoding service utilizing the various
     * Horde_Core_Ajax_Imple_Geocoder_* classes. Mapping providers that include
     * geocoding services will have HordeMap.Geocoder implementations in their
     * respective *.js files.  The Null driver provides fallback implementaions
     * for those without geocoder support.
     *
     */
    Geocoder: {}
};

