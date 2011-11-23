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
                    Horde_Service_Weather::UNITS_STANDARD => _("Standard"),
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
        } else {
            $instance = hash('md5', mt_rand());
            $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Imple')
                ->create(
                    'WeatherLocationAutoCompleter',
                    array(
                        'triggerId' => 'location' . $instance,
                        'instance' => $instance
                    )
                );

            $html = '<input id="location' . $instance . '" name="location' . $instance . '"><a id="button' . $instance . '" href="#" class="button">'
                . _("Change Location") . '</a><span style="display:none;" id="location' . $instance . '_loading_img">'
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
            . '<strong>' . $station->name . '</strong> ' . _("Local time: ")
            . $current->time->strftime($GLOBALS['prefs']->getValue('date_format'))
            . ' '
            . $current->time->strftime($GLOBALS['prefs']->getValue('time_format'))
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
            $current->temp . '&deg;' . Horde_String::upper($units['temp']);

        // Dew point.
        if ($current->dewpoint) {
            $html .= ' <strong>' . _("Dew point: ") . '</strong>' .
                ($dewpoint !== false ?
                    round($current->dewpoint) . '&deg;' . Horde_String::upper($units['temp']) :
                    _("N/A"));
        }

        // Feels like temperature.
        // @TODO: Need to parse if wind chill/heat index etc..
        // $html .= ' <strong>' . _("Feels like: ") . '</strong>' .
        //     round($weather['feltTemperature']) . '&deg;' . Horde_String::upper($units['temp']);

        // Pressure and trend.
        if ($current->pressure) {
            $html .= '<br /><strong>' . _("Pressure: ") . '</strong>';
            $trend = $current->pressure_trend;
            if (empty($trend)) {
                $html .= sprintf(_("%d %s"),
                    round($current->pressure), $units['pres']);
            } else {
                $html .= sprintf(_("%d %s and %s"),
                    round($current->pressure), $units['pres'],
                    _($trend));
            }
        }
        if ($current->wind_direction) {
            // Wind.
            $html .= '<br /><strong>' . _("Wind: ") . '</strong>';

            $html .= _("From the ") . $current->wind_direction
                . ' ('. $current->wind_degrees . '&deg;) ' . _("at")
                . ' ' . $current->wind_speed;
            if ($current->wind_gust > 0) {
                $html .= ', ' . _("gusting") . ' ' . $current->wind_gust .
                    ' ' . $units['wind'];
            }
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

        // Forecast
        if ($this->_params['days'] > 0) {
            $html .= '<div class="control"><strong>' .
                sprintf(_("%d-day forecast"), $this->_params['days']) .
                '</strong></div>';

            $futureDays = 0;
            $html .= '<table width="100%" cellspacing="3">';
            // Headers.
            $html .= '<tr>';
            // $html .= '<th>' . _("Day") . '</th><th>&nbsp;</th><th>' .
            $html .= '<th>' . _("Day") . '</th><th>' .
            sprintf(_("Temperature<br />(%sHi%s/%sLo%s) &deg;%s"),
                        '<span style="color:red">', '</span>',
                        '<span style="color:blue">', '</span>',
                        Horde_String::upper($units['temp'])) .
                '</th><th>' . _("Condition") . '</th>' .
                '<th>' . _("Precipitation<br />chance") . '</th>';
            if (isset($this->_params['detailedForecast'])) {
                $html .= '<th>' . _("Humidity") . '</th><th>' . _("Wind") . '</th>';
            }
            $html .= '</tr>';
            $which = -1;
            foreach ($forecast as $day) {
                 $which++;
                 if ($which > $this->_params['days']) {
                     break;
                 }
                 $html .= '<tr class="item0">';
                 // Day name.
                 // $html .= '<td rowspan="2" style="border:1px solid #ddd; text-align:center"><strong>';
                 $html .= '<td style="border:1px solid #ddd; text-align:center"><strong>';

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

                // The day portion of the forecast is no longer available after 2:00 p.m. local today.
                // ...but only check if we have a day/night forecast.
                if ($forecast->detail == Horde_Service_Weather::FORECAST_TYPE_DETAILED &&
                    $which == 0 &&
                    (strtotime($location['time']) >= strtotime('14:00'))) {
                    // Balance the grid.
                    $html .= '<td colspan="' .
                            ((isset($this->_params['detailedForecast']) ? '5' : '3') . '"') .
                            ' style="border:1px solid #ddd; text-align:center">' .
                            '&nbsp;<br />' . _("Information no longer available.") . '<br />&nbsp;' .
                            '</td>';
                } else {
                    // Forecast condition.
                     $condition = $day->conditions;

                    // Temperature.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">'
                        . '<span style="color:red">' . $day->high . '</span>/'
                        .  '<span style="color:blue">' . $day->low . '</span></td>';

                    // Condition.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">'
                        . Horde::img(Horde_Themes::img('weather/32x32/' . $day->icon))
                        . '<br />' . $condition . '</td>';

                    // Precipitation chance.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">'
                        . ($day->precipitation_percent ? $day->precipitation_percent . '%' : _("N/A")) . '</td>';

                    // If a detailed forecast was requested, show humidity and
                    // winds.
                    if (isset($this->_params['detailedForecast'])) {
                        // Humidity.
                        $html .= '<td style="border:1px solid #ddd; text-align:center">'
                            . ($day->humidity ? $day->humidity . '%': _("N/A")) . '</td>';

                        // Winds.
                        if ($day->wind_direction) {
                            $html .= '<td style="border:1px solid #ddd">' .
                                _("From the ") . $day->wind_direction .
                                _(" at ") . $day->wind_speed .
                                ' ' . $units['wind'];
                            if ($day->wind_gust && $day->wind_gust > 0) {
                                $html .= _(", gusting ") . $day->wind_gust .
                                    ' ' . $units['wind'];
                            }
                            $html .= '</td>';
                        } else {
                            $html .= '<td style="border:1px solid #ddd;text-align:center;">' . _("N/A") . '</td>';
                        }
                    }

                    $html .= '</tr>';
                }
                // @TODO: Support day/night portions when we have the driver support.
                // Prepare for next day.
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
