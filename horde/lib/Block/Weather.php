<?php
/**
 * Portal block for displaying weather information obtained via
 * Horde_Service_Weather.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package Horde
 */

/**
 * Horde_Service_Weather_Google.
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @package  Horde
 */
class Horde_Block_Weather extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        // @TODO: Check config key etc...
        parent::__construct($app, $params);
        $this->_name = _("weather");
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
        // @TODO: Autocomplete the location selection? If not via the config
        // screen see if we can set this value from an autocomplete field on
        // the main view.
        return array(
            'location' => array(
                // 'type' => 'weatherdotcom',
                'type' => 'text',
                'name' => _("Location"),
                'default' => 'Boston,MA'
            ),
            'units' => array(
                'type' => 'enum',
                'name' => _("Units"),
                'default' => 'standard',
                'values' => array(
                    Horde_Service_Weather::UNITS_STANDARD => _("Standard"),
                    Horde_Service_Weather::UNITS_METRIC =>  _("Metric")
                )
            ),
            'days' => array(
                'type' => 'enum',
                'name' => _("Forecast Days (note that the returned forecast returns both day and night; a large number here could result in a wide block)"),
                'default' => 3,
                'values' => array(
                    '3' => Horde_Service_Weather::FORECAST_3DAY,
                    '5' => Horde_Service_Weather::FORECAST_5DAY,
                )
            ),
            // 'detailedForecast' => array(
            //     'type' => 'checkbox',
            //     'name' => _("Display detailed forecast"),
            //     'default' => 0
            // )
        );
    }

    /**
     */
    protected function _content()
    {
        global $conf, $prefs;

        $weather = $GLOBALS['injector']
            ->getInstance('Horde_Weather');

        $units = $weather->getUnits();

        // Test location
        try {
            $location = $weather->searchLocations($this->_params['location']);
        } catch (Horde_Service_Weather $e) {
            return $location->getMessage();
        }
        $html = '';
        if (is_array($location)) {
            // Several locations returned due to imprecise location parameter.
            $html = _("Several locations possible with the parameter: ") .
                $this->_params['location'] .
                '<br />';
            foreach ($location as $real_location) {
                $html .= '<li>' . $real_location->city . ', ' . $real_location->state . '(' . $real_location->code . ")</li>\n";
            }
            $html .= '</ul>';
            return $html;
        }

        try {
            $forecast = $weather->getForecast($this->_params['location'], $this->_params['days']);
            $station = $weather->getStation();
            $current = $weather->getCurrentConditions($this->_params['location']);
        } catch (Horde_Service_Weather $e) {
            return $e->getMessage();
        }
        // Location and local time.
        $html .= '<div class="control">'
            . '<strong>' . $station->name . '</strong> ' . _("Local time: ")
            . $forecast->getForecastTime()->strftime($GLOBALS['prefs']->getValue('date_format')) . ' '
            . $forecast->getForecastTime()->strftime($GLOBALS['prefs']->getValue('time_format'))
            . '</div>';

        // Sunrise/sunset.
        if ($station->sunrise) {
            $html .= '<strong>' . _("Sunrise: ") . '</strong>' .
                Horde::img('block/sunrise/sunrise.png', _("Sunrise")) .
                $station->sunrise->strftime($GLOBALS['prefs']->getValue('date_format')) . ' ' .
                $station->sunrise->strftime($GLOBALS['prefs']->getValue('time_format'));
            $html .= ' <strong>' . _("Sunset: ") . '</strong>' .
                Horde::img('block/sunrise/sunset.png', _("Sunset")) .
                $station->sunset->strftime($GLOBALS['prefs']->getValue('date_format')) . ' ' .
                $station->sunset->strftime($GLOBALS['prefs']->getValue('time_format'));

            $html .= '<br />';
        }

        // Temperature.
        $html .= '<strong>' . _("Temperature: ") . '</strong>' .
            round($current->temp) . '&deg;' . Horde_String::upper($units['temp']);

        // Dew point.
        if ($current->dewpoint) {
            $html .= ' <strong>' . _("Dew point: ") . '</strong>' .
                ($dewpoint !== false ?
                    round($current->dewpoint) . '&deg;' . Horde_String::upper($units['temp']) :
                    _("N/A"));
        }

        // // Feels like temperature.
        // @TODO: Need to parse if wind chill/heat index etc..
        // $html .= ' <strong>' . _("Feels like: ") . '</strong>' .
        //     round($weather['feltTemperature']) . '&deg;' . Horde_String::upper($units['temp']);

        // // Pressure and trend.
        if ($current->pressure) {
            $html .= '<br /><strong>' . _("Pressure: ") . '</strong>';
            $trend = $current->pressure_trend;
            $html .= sprintf(_("%d %s and %s"),
                             round($current->pressure), $units['pres'],
                             _($trend));
        }

        if ($current->wind_direction) {
            // // Wind.
            $html .= '<br /><strong>' . _("Wind: ") . '</strong>';

            $html .= _("From the ") . $current->wind_direction
                . ' ' . _("at") . ' ' . $current->wind_speed;
            if ($current->wind_gust > 0) {
                $html .= ', ' . _("gusting") . ' ' . $current->wind_gust .
                    ' ' . $units['wind'];
            }

            $html .= ' (' . $current->wind_degrees . '&deg;)';
            $html .= _(" at ") . round($weather->wind) . ' ' . $units['wind'];
        }

        // Humidity.
        if ($current->humidity) {
            $html .= '<br /><strong>' . _("Humidity: ") . '</strong>' . $current->humidity;
        }

        if ($current->visibility) {
            // Visibility.
            $html .= ' <strong>' . _("Visibility: ") . '</strong>' .
                 round($current->visibility) . ' ' . $units['vis'];
        }

        // Current condition.
        $condition = $current->condition;
        $html .= '<br /><strong>' . _("Current condition: ") . '</strong>' .
            Horde::img(Horde_Themes::img('weather/32x32/' . $current->icon))
            .  ' ' . $condition;

        return $html;
    }

}
