<?php
/**
 * Portal block for displaying weather information obtained via
 * Horde_Service_Weather.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package Horde
 */

/**
 * Horde_Block_Weather
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @package  Horde
 */
class Horde_Block_Weather extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;

    protected $_refreshParams;

    public $autoUpdateMethod = 'refreshContent';

    /**
     * @var Horde_Service_Weather_Base
     */
    protected $_weather;

    /**
     */
    public function __construct($app, $params = array())
    {
        global $injector;

        parent::__construct($app, $params);
        try {
            $this->_weather = $injector->getInstance('Horde_Weather');
        } catch (Horde_Exception $e) {
            $this->enabled = false;
        }
        $this->_name = _("Weather");
    }

    /**
     * Handle user initiated block refresh. Set a private member to avoid
     * BC issues with having to add a parameter to the _content method.
     *
     * @param Horde_Variables $vars
     *
     * @return string
     */
    public function refreshContent($vars = null)
    {
        if (empty($vars) || empty($vars->location)) {
            $this->_refreshParams = Horde_Variables::getDefaultVariables();
            $this->_refreshParams->set('location', $this->_params['location']);
        } else {
            $this->_refreshParams = $vars;
        }

        return $this->_content();
    }

    /**
     */
    protected function _title()
    {
        return _("Weather");
    }

    /**
     */
    protected function _params()
    {
        $lengths = $this->_weather->getSupportedForecastLengths();
        return array(
            'location' => array(
                'type' => 'text',
                'name' => _("Location"),
                'default' => 'Boston,MA'
            ),
            'units' => array(
                'type' => 'enum',
                'name' => _("Units"),
                'default' => 'standard',
                'values' => array(
                    Horde_Service_Weather::UNITS_STANDARD => _("English"),
                    Horde_Service_Weather::UNITS_METRIC =>  _("Metric")
                )
            ),
            'days' => array(
                'type' => 'enum',
                'name' => _("Forecast Days (note that the returned forecast returns both day and night; a large number here could result in a wide block.)"),
                'default' => 3,
                'values' => $lengths
            ),
            'detailedForecast' => array(
                'type' => 'checkbox',
                'name' => _("Display detailed forecast?"),
                'default' => 0
            ),
            'showMap' => array(
                'type' => 'checkbox',
                'name' => _("Display the OpenWeatherMap map?"),
                'default' => 0)
        );
    }

    /**
     */
    protected function _content()
    {
        global $injector, $language, $page_output, $prefs, $registry;

        // Set the requested units.
        $this->_weather->units = $this->_params['units'];
        $view = $injector->getInstance('Horde_View');

        if (!empty($this->_refreshParams) && !empty($this->_refreshParams->location)) {
            $location = $this->_refreshParams->location;
            $view->instance = '';
        } else {
            $view->instance = hash('md5', mt_rand());
            $injector->getInstance('Horde_Core_Factory_Imple')->create(
                'WeatherLocationAutoCompleter_Weather',
                array(
                    'id' => 'location' . $view->instance,
                    'instance' => $view->instance
                )
            );
            $view->requested_location = $this->_params['location'];
            $location = $this->_params['location'];
        }

        $view->units = $this->_weather->getUnits($this->_weather->units);
        $view->params = $this->_params;
        $view->link = $this->_weather->link;
        $view->title = $this->_weather->title;
        if ($this->_weather->logo) {
            $view->logo = $this->_weather->logo;
        }

        // Test location
        try {
            $view->location = $this->_weather->searchLocations($location);
        } catch (Horde_Service_Weather_Exception $e) {
            return $e->getMessage();
        } catch (Horde_Exception_NotFound $e) {
            return _(sprintf("Location %s not found."), $location);
        }
        try {
            $view->forecast = $this->_weather->getForecast($view->location->code, $this->_params['days']);
            $view->station = $this->_weather->getStation();
            $view->current = $this->_weather->getCurrentConditions($view->location->code);
            // @todo: Add link to put alert text in redbox.
            $view->timezone = $prefs->getValue('timezone');
            $view->dateFormat = $prefs->getValue('date_format');
            $view->timeFormat = $prefs->getValue('time_format');
            $view->alerts = $this->_weather->getAlerts($view->location->code);
            $view->radar = $this->_weather->getRadarImageUrl($location);
        } catch (Horde_Service_Weather_Exception $e) {
            return $e->getMessage();
        }
        $view->languageFilter = '/^'
            . $registry->nlsconfig->languages[$language] . ': /i';

        if (!empty($this->_params['showMap']) && !empty($view->instance)) {
            $view->map = true;
            $page_output->addScriptFile('weatherblockmap.js', 'horde');
            Horde_Core_HordeMap::init(array('providers' => array('owm', 'osm')));
            $page_output->addInlineScript(array(
                'WeatherBlockMap.initializeMap("' . $view->instance . '", { lat: "' . $view->location->lat . '", lon: "' . $view->location->lon . '"});$("weathermaplayer_' . $view->instance . '").show();'
            ), true);
        }
        if (!empty($view->instance)) {
            return $view->render('block/weather');
        } else {
            return $view->render('block/weather_content');
        }
    }

}
