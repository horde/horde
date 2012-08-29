<?php
/**
 * Portal block for displaying weather information obtained via
 * Horde_Service_Weather.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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

    /**
     */
    public function __construct($app, $params = array())
    {
        // @TODO: Check config key etc...
        parent::__construct($app, $params);
        $this->_name = _("weather");
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
        $this->_refreshParams = $vars;
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
        $weather = $GLOBALS['injector']
            ->getInstance('Horde_Weather');
        $lengths = $weather->getSupportedForecastLengths();
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
                'name' => _("Forecast Days (note that the returned forecast returns both day and night; a large number here could result in a wide block)"),
                'default' => 3,
                'values' => $lengths
            ),
            'detailedForecast' => array(
                'type' => 'checkbox',
                'name' => _("Display detailed forecast"),
                'default' => 0
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $conf, $prefs;

        $weather = $GLOBALS['injector']
            ->getInstance('Horde_Weather');

        // Set the requested units.
        $weather->units = $this->_params['units'];

        if (!empty($this->_refreshParams) && !empty($this->_refreshParams->location)) {
            $location = $this->_refreshParams->location;
            $html = '';
            $instance = '';
        } else {
            $instance = hash('md5', mt_rand());
            $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Imple')
                ->create(
                    'WeatherLocationAutoCompleter',
                    array(
                        'id' => 'location' . $instance,
                        'instance' => $instance
                    )
                );

            $html = '<input id="location' . $instance . '" name="location' . $instance . '"><input type="button" id="button' . $instance . '" class="button" value="'
                . _("Change Location") . '" /><span style="display:none;" id="location' . $instance . '_loading_img">'
                . Horde::img('loading.gif') . '</span>';
            $location = $this->_params['location'];
        }

        // Test location
        try {
            $location = $weather->searchLocations($location);
        } catch (Horde_Service_Weather_Exception $e) {
            return $e->getMessage();
        }

        $html .= '<div id="weathercontent' . $instance . '">';

        if (is_array($location)) {
            // Several locations returned due to imprecise location parameter.
            $html = sprintf(_("Several locations possible with the parameter: %s"), $this->_params['location'])
                . '<br />';
            foreach ($location as $real_location) {
                $html .= '<li>' . $real_location->city . ', ' . $real_location->state . '(' . $real_location->code . ")</li>\n";
            }
            $html .= '</ul>';
            return $html;
        }
        try {
            $forecast = $weather->getForecast($location->code, $this->_params['days']);
            $station = $weather->getStation();
            $current = $weather->getCurrentConditions($location->code);
        } catch (Horde_Service_Weather_Exception $e) {
            return $e->getMessage();
        }

        // Units to display as
        $units = $weather->getUnits($weather->units);

        // Location and local time.
        $html .= '<div class="control">'
            . '<strong>' . $station->name . '</strong>';
        if ($current->time->timestamp()) {
            $html .= ' ' . sprintf(_("Local time: %s %s"), $current->time->strftime($GLOBALS['prefs']->getValue('date_format')), $current->time->strftime($GLOBALS['prefs']->getValue('time_format')));
        }
        $html .= '</div>';

        // Sunrise/sunset.
        if ($station->sunrise) {
            $html .= '<strong>' . _("Sunrise") . ': </strong>'
                . Horde::img('block/sunrise/sunrise.png', _("Sunrise"))
                . sprintf("%s %s", $station->sunrise->strftime($GLOBALS['prefs']->getValue('date_format')), $station->sunrise->strftime($GLOBALS['prefs']->getValue('time_format')));
            $html .= ' <strong>' . _("Sunset") . ': </strong>'
                . Horde::img('block/sunrise/sunset.png', _("Sunset"))
                . sprintf("%s %s", $station->sunset->strftime($GLOBALS['prefs']->getValue('date_format')), $station->sunset->strftime($GLOBALS['prefs']->getValue('time_format')));
            $html .= '<br />';
        }

        // Temperature.
        $html .= '<strong>' . _("Temperature") . ': </strong>' .
            $current->temp . '&deg;' . Horde_String::upper($units['temp']);

        // Dew point.
        if (is_numeric($current->dewpoint)) {
            $html .= ' <strong>' . _("Dew point") . ': </strong>' .
                    round($current->dewpoint) . '&deg;' . Horde_String::upper($units['temp']);
        }

        // Feels like temperature.
        // @TODO: Need to parse if wind chill/heat index etc..
        // $html .= ' <strong>' . _("Feels like: ") . '</strong>' .
        //     round($weather['feltTemperature']) . '&deg;' . Horde_String::upper($units['temp']);

        // Pressure and trend.
        if ($current->pressure) {
            $html .= '<br /><strong>' . _("Pressure") . ': </strong>';
            $trend = $current->pressure_trend;
            if (empty($trend)) {
                $html .= sprintf('%d %s',
                                 round($current->pressure), $units['pres']);
            } else {
                $html .= sprintf(_("%d %s and %s"),
                                 round($current->pressure), $units['pres'],
                                 _($trend));
            }
        }
        if ($current->wind_direction) {
            // Wind.
            $html .= '<br /><strong>' . _("Wind") . ': </strong>';
            $html .= sprintf(
                _("From the %s (%s &deg;) at %s %s"),
                $current->wind_direction,
                $current->wind_degrees,
                $current->wind_speed,
                $units['wind']);
            if ($current->wind_gust > 0) {
                $html .= ', ' . _("gusting") . ' ' . $current->wind_gust . ' ' . $units['wind'];
            }
        }

        // Humidity.
        if ($current->humidity) {
            $html .= '<br /><strong>' . _("Humidity") . ': </strong>' . $current->humidity;
        }

        if ($current->visibility) {
            // Visibility.
            $html .= ' <strong>' . _("Visibility") . ': </strong>'
                . round($current->visibility) . ' ' . $units['vis'];
        }

        // Current condition.
        $condition = $current->condition;
        $html .= '<br /><strong>' . _("Current condition") . ': </strong>'
            . Horde::img(Horde_Themes::img('weather/32x32/' . $current->icon))
            .  ' ' . $condition;

        // Forecast
        if ($this->_params['days'] > 0) {
            $html .= '<div class="control"><strong>' .
                sprintf(_("%d-day forecast"), $this->_params['days']) .
                '</strong></div>';

            $futureDays = 0;
            $html .= '<table class="horde-block-weather">';

            // Headers.
            $html .= '<tr>';
            $html .= '<th>' . _("Day") . '</th><th>' .
            sprintf(_("Temperature%s(%sHi%s/%sLo%s)"),
                        '<br />',
                        '<span style="color:red">', '</span>',
                        '<span style="color:blue">', '</span>') .
                '</th><th>' . _("Condition") . '</th>';

            if (isset($this->_params['detailedForecast'])) {
                if (in_array(Horde_Service_Weather::FORECAST_FIELD_PRECIPITATION, $forecast->fields)) {
                    $html .= '<th>' . sprintf(_("Precipitation%schance"), '<br />') . '</th>';
                }

                if (in_array(Horde_Service_Weather::FORECAST_FIELD_HUMIDITY, $forecast->fields)) {
                    $html .= '<th>' . _("Humidity") . '</th>';
                }

                if (in_array(Horde_Service_Weather::FORECAST_FIELD_WIND, $forecast->fields)) {
                    $html .= '<th>' . _("Wind") . '</th>';
                }
            }

            $html .= '</tr>';
            $which = -1;
            foreach ($forecast as $day) {
                 $which++;
                 if ($which > $this->_params['days']) {
                     break;
                 }
                 $html .= '<tr class="rowEven">';

                 // Day name.
                 $html .= '<td><strong>';

                 if ($which == 0) {
                     $html .= _("Today");
                 } elseif ($which == 1) {
                     $html .= _("Tomorrow");
                 } else {
                     $html .= strftime('%A', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y')));
                 }
                $html .= '</strong><br />' .
                    strftime('%b %d', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y'))) .
                    '</td>';

                // Forecast condition.
                $condition = $day->conditions;

                // Temperature.
                $html .= '<td>'
                    . '<span style="color:red">' . $day->high . '&deg;'
                    . Horde_String::upper($units['temp']) . '</span>/'
                    . '<span style="color:blue">' . $day->low . '&deg;'
                    . Horde_String::upper($units['temp']) . '</span></td>';

                // Condition.
                $html .= '<td>'
                    . Horde::img(Horde_Themes::img('weather/32x32/' . $day->icon))
                    . '<br />' . $condition . '</td>';

                if (isset($this->_params['detailedForecast'])) {
                    if (in_array(Horde_Service_Weather::FORECAST_FIELD_PRECIPITATION, $forecast->fields)) {
                        $html .= '<td>'
                            . ($day->precipitation_percent >= 0 ? $day->precipitation_percent . '%' : _("N/A")) . '</td>';
                    }
                    if (in_array(Horde_Service_Weather::FORECAST_FIELD_HUMIDITY, $forecast->fields)) {
                        $html .= '<td>'
                            . ($day->humidity ? $day->humidity . '%': _("N/A")) . '</td>';
                    }
                    if (in_array(Horde_Service_Weather::FORECAST_FIELD_WIND, $forecast->fields)) {
                        // Winds.
                        if ($day->wind_direction) {
                            $html .= '<td>' . ' '
                                . sprintf(_("From the %s at %s %s"),
                                          $day->wind_direction,
                                          $day->wind_speed,
                                          $units['wind']);
                            if ($day->wind_gust && $day->wind_gust > $day->wind_speed) {
                                $html .= ', ' . _("gusting") . ' '
                                    . $day->wind_gust . ' ' . $units['wind'];
                            }
                            $html .= '</td>';
                        } else {
                            $html .= '<td>' . _("N/A") . '</td>';
                        }
                    }
                }
                $html .= '</tr>';
                $futureDays++;
            }
            $html .= '</table>';
        }

        if ($weather->logo) {
            $html .= '<div class="rightAlign">'
                . _("Weather data provided by") . ' '
                . Horde::link(
                    Horde::externalUrl($weather->link),
                    $weather->title, '', '_blank', '', $weather->title)
                . Horde::img(new Horde_Themes_Image($weather->logo))
                . '</a></div>';
        } else {
            $html .= '<div class="rightAlign">'
                . _("Weather data provided by") . ' '
                . Horde::link(
                    Horde::externalUrl($weather->link),
                    $weather->title, '', '_blank', '', $weather->title)
                . '<em>' . $weather->title . '</em>'
                . '</a></div>';
        }

        return $html . '</div>';
    }

}
