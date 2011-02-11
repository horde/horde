<?php
/**
 * An applet for the portal screen to display weather and forecast data from
 * weather.com for a specified location.
 */
class Horde_Block_Weatherdotcom extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->enabled = false;

        if (!empty($GLOBALS['conf']['weatherdotcom']['partner_id']) &&
            !empty($GLOBALS['conf']['weatherdotcom']['license_key'])) {
            if (!class_exists('Services_Weather') ||
                !class_exists('Cache') ||
                !class_exists('XML_Serializer') ||
                !ini_get('allow_url_fopen')) {
                Horde::logMessage('The weather.com block will not work without PEAR\'s Services_Weather, Cache, and XML_ Serializer packages, and allow_url_fopen enabled. Run `pear install Services_Weather Cache XML_Serializer´ and ensure that allow_url_fopen is enabled in php.ini.', 'DEBUG');
            } else {
                $this->enabled = true;
            }
        }

        $this->_name = _("weather.com");
    }

    /**
     */
    protected function _title()
    {
        return _("Weather Forecast");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'location' => array(
                // 'type' => 'weatherdotcom',
                'type' => 'text',
                'name' => _("Location"),
                'default' => 'Boston, MA'
            ),
            'units' => array(
                'type' => 'enum',
                'name' => _("Units"),
                'default' => 'standard',
                '0' => 'none',
                'values' => array(
                    'standard' => _("Standard"),
                    'metric' => _("Metric")
                )
            ),
            'days' => array(
                'type' => 'enum',
                'name' => _("Forecast Days (note that the returned forecast returns both day and night; a large number here could result in a wide block)"),
                'default' => 3,
                'values' => array(
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                )
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

        $options = array(
            'unitsFormat' => $this->_params['units'],
            'dateFormat' => Horde_Date_Utils::strftime2date($prefs->getValue('date_format')),
            'timeFormat' => $prefs->getValue('twentyFour') ? 'G:i' : 'g:i a');
        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $proxy = 'http://';
            if (!empty($conf['http']['proxy']['proxy_user'])) {
                $proxy .= urlencode($conf['http']['proxy']['proxy_user']);
                if (!empty($conf['http']['proxy']['proxy_pass'])) {
                    $proxy .= ':' . urlencode($conf['http']['proxy']['proxy_pass']);
                }
                $proxy .= '@';
            }
            $proxy .= $conf['http']['proxy']['proxy_host'];
            if (!empty($conf['http']['proxy']['proxy_port'])) {
                $proxy .= ':' . $conf['http']['proxy']['proxy_port'];
            }

            $options['httpProxy'] = $proxy;
        }

        if (empty($this->_params['location'])) {
            return _("No location is set.");
        }

        $weatherDotCom = &Services_Weather::service('WeatherDotCom', $options);
        $weatherDotCom->setAccountData(
            (isset($conf['weatherdotcom']['partner_id']) ? $conf['weatherdotcom']['partner_id'] : ''),
            (isset($conf['weatherdotcom']['license_key']) ? $conf['weatherdotcom']['license_key'] : ''));

        $cacheDir = Horde::getTempDir();
        if (!$cacheDir) {
            throw new Horde_Exception(_("No temporary directory available for cache."));
        } else {
            $weatherDotCom->setCache('file', array('cache_dir' => ($cacheDir . '/')));
        }
        $units = $weatherDotCom->getUnitsFormat();

        // If the user entered a zip code for the location, no need to
        // search (weather.com accepts zip codes as location IDs).
        // The location ID should already have been validated in
        // getParams.
        $search = (preg_match('/\b(?:\\d{5}(-\\d{5})?)|(?:[A-Z]{4}\\d{4})\b/',
            $this->_params['location'], $matches) ?
            $matches[0] :
            $weatherDotCom->searchLocation($this->_params['location']));
        if ($search instanceof PEAR_Error) {
            switch ($search->getCode()) {
            case SERVICES_WEATHER_ERROR_SERVICE_NOT_FOUND:
                return _("Requested service could not be found.");
            case SERVICES_WEATHER_ERROR_UNKNOWN_LOCATION:
                return _("Unknown location provided.");
            case SERVICES_WEATHER_ERROR_WRONG_SERVER_DATA:
                return _("Server data wrong or not available.");
            case SERVICES_WEATHER_ERROR_CACHE_INIT_FAILED:
                return _("Cache init was not completed.");
            case SERVICES_WEATHER_ERROR_DB_NOT_CONNECTED:
                return _("MetarDB is not connected.");
            case SERVICES_WEATHER_ERROR_UNKNOWN_ERROR:
                return _("An unknown error has occured.");
            case SERVICES_WEATHER_ERROR_NO_LOCATION:
                return _("No location provided.");
            case SERVICES_WEATHER_ERROR_INVALID_LOCATION:
                return _("Invalid location provided.");
            case SERVICES_WEATHER_ERROR_INVALID_PARTNER_ID:
                return _("Invalid partner id.");
            case SERVICES_WEATHER_ERROR_INVALID_PRODUCT_CODE:
                return _("Invalid product code.");
            case SERVICES_WEATHER_ERROR_INVALID_LICENSE_KEY:
                return _("Invalid license key.");
            default:
                return $search->getMessage();
            }
        }

        $html = '';
        if (is_array($search)) {
            // Several locations returned due to imprecise location
            // parameter.
            $html = _("Several locations possible with the parameter: ") .
                $this->_params['location'] .
                '<br /><ul>';
            foreach ($search as $id_weather => $real_location) {
                $html .= "<li>$real_location ($id_weather)</li>\n";
            }
            $html .= '</ul>';
            return $html;
        }

        $location = $weatherDotCom->getLocation($search);
        if ($location instanceof PEAR_Error) {
            return $location->getMessage();
        }
        $weather = $weatherDotCom->getWeather($search);
        if ($weather instanceof PEAR_Error) {
            return $weather->getMessage();
        }
        $forecast = $weatherDotCom->getForecast($search, (integer)$this->_params['days']);
        if ($forecast instanceof PEAR_Error) {
            return $forecast->getMessage();
        }

        // Location and local time.
        $html .= '<div class="control">' .
            '<strong>' . $location['name'] . '</strong> ' . _("Local time: ") . $location['time'] .
            '</div>';

        // Sunrise/sunset.
        $html .= '<strong>' . _("Sunrise: ") . '</strong>' .
            Horde::img('block/sunrise/sunrise.png', _("Sunrise")) .
            $location['sunrise'];
        $html .= ' <strong>' . _("Sunset: ") . '</strong>' .
            Horde::img('block/sunrise/sunset.png', _("Sunset")) .
            $location['sunset'];

        // Temperature.
        $html .= '<br /><strong>' . _("Temperature: ") . '</strong>' .
            round($weather['temperature']) . '&deg;' . Horde_String::upper($units['temp']);

        // Dew point.
        $html .= ' <strong>' . _("Dew point: ") . '</strong>' .
            round($weather['dewPoint']) . '&deg;' . Horde_String::upper($units['temp']);

        // Feels like temperature.
        $html .= ' <strong>' . _("Feels like: ") . '</strong>' .
            round($weather['feltTemperature']) . '&deg;' . Horde_String::upper($units['temp']);

        // Pressure and trend.
        $html .= '<br /><strong>' . _("Pressure: ") . '</strong>';
        $trend = $weather['pressureTrend'];
        $html .= sprintf(_("%d %s and %s"),
                         round($weather['pressure']), $units['pres'],
                         _($trend));

        // Wind.
        $html .= '<br /><strong>' . _("Wind: ") . '</strong>';
        if ($weather['windDirection'] == 'VAR') {
            $html .= _("Variable");
        } elseif ($weather['windDirection'] == 'CALM') {
            $html .= _("Calm");
        } else {
            $html .= _("From the ") . $weather['windDirection'];
        if (isset($weather['windGust']) && $weather['windGust'] > 0) {
            $html .= ', ' . _("gusting") . ' ' . $weather['windGust'] .
                ' ' . $units['wind'];
        }

            $html .= ' (' . $weather['windDegrees'] . ')';
        }
        $html .= _(" at ") . round($weather['wind']) . ' ' . $units['wind'];

        // Humidity.
        $html .= '<br /><strong>' . _("Humidity: ") . '</strong>' .
            $weather['humidity'] . '%';

        // Visibility.
        $html .= ' <strong>' . _("Visibility: ") . '</strong>' .
            (is_numeric($weather['visibility'])
             ? round($weather['visibility']) . ' ' . $units['vis']
             : $weather['visibility']);

        // UV index.
        $html .= ' <strong>' . _("U.V. index: ") . '</strong>';
        $uv = $weather['uvText'];
        $html .= $weather['uvIndex'] . ' - ' . _($uv);

        // Current condition.
        $condition = implode(' / ', array_map('_', explode(' / ', $weather['condition'])));
        $html .= '<br /><strong>' . _("Current condition: ") . '</strong>' .
            Horde::img('block/weatherdotcom/32x32/' .
                       ($weather['conditionIcon'] == '-' ? 'na' : $weather['conditionIcon']) . '.png',
                       $condition);
        $html .= ' ' . $condition;

        // Do the forecast now (if requested).
        if ($this->_params['days'] > 0) {
            $html .= '<div class="control"><strong>' .
                sprintf(_("%d-day forecast"), $this->_params['days']) .
                '</strong></div>';

            $futureDays = 0;
            $html .= '<table width="100%" cellspacing="3">';
            // Headers.
            $html .= '<tr>';
            $html .= '<th>' . _("Day") . '</th><th>&nbsp;</th><th>' .
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

            foreach ($forecast['days'] as $which => $day) {
                $html .= '<tr class="item0">';

                // Day name.
                $html .= '<td rowspan="2" style="border:1px solid #ddd; text-align:center"><strong>';
                if ($which == 0) {
                    $html .= _("Today");
                } elseif ($which == 1) {
                    $html .= _("Tomorrow");
                } else {
                    $html .= strftime('%A', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y')));
                }
                $html .= '</strong><br />' .
                    strftime('%b %d', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y'))) .
                    '</td>' .
                    '<td style="border:1px solid #ddd; text-align:center">' .
                    '<span style="color:orange">' .
                    _("Day") . '</span></td>';

                // The day portion of the forecast is no longer available after 2:00 p.m. local today.
                if ($which == 0 && (strtotime($location['time']) >= strtotime('14:00'))) {
                    // Balance the grid.
                    $html .= '<td colspan="' .
                            ((isset($this->_params['detailedForecast']) ? '5' : '3') . '"') .
                            ' style="border:1px solid #ddd; text-align:center">' .
                            '&nbsp;<br />' . _("Information no longer available.") . '<br />&nbsp;' .
                            '</td>';
                } else {
                    // Forecast condition.
                    $condition = implode(' / ', array_map('_', explode(' / ', $day['day']['condition'])));

                    // High temperature.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                        '<span style="color:red">' .
                        round($day['temperatureHigh']) . '</span></td>';

                    // Condition.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                        Horde::img('block/weatherdotcom/23x23/' . ($day['day']['conditionIcon'] == '-' ? 'na' : $day['day']['conditionIcon']) . '.png', $condition) .
                        '<br />' . $condition . '</td>';

                    // Precipitation chance.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                        $day['day']['precipitation'] . '%' . '</td>';

                    // If a detailed forecast was requested, show humidity and
                    // winds.
                    if (isset($this->_params['detailedForecast'])) {

                        // Humidity.
                        $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                            $day['day']['humidity'] . '%</td>';

                        // Winds.
                        $html .= '<td style="border:1px solid #ddd">' .
                            _("From the ") . $day['day']['windDirection'] .
                            _(" at ") . $day['day']['wind'] .
                            ' ' . $units['wind'];
                        if (isset($day['day']['windGust']) &&
                            $day['day']['windGust'] > 0) {
                            $html .= _(", gusting ") . $day['day']['windGust'] .
                                ' ' . $units['wind'];
                        }
                    }

                    $html .= '</tr>';
                }

                // Night forecast
                $night = implode(' / ', array_map('_', explode(' / ', $day['night']['condition'])));

                // Shade it for visual separation.
                $html .= '<tr class="item1">';

                $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                    _("Night") . '</td>';

                // Low temperature.
                $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                    '<span style="color:blue">' .
                    round($day['temperatureLow']) . '</span></td>';

                // Condition.
                $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                    Horde::img('block/weatherdotcom/23x23/' . ($day['night']['conditionIcon'] == '-' ? 'na' : $day['night']['conditionIcon']) . '.png', $night) .
                    '<br />' . $night . '</td>';

                // Precipitation chance.
                $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                    $day['night']['precipitation'] . '%</td>';

                // If a detailed forecast was requested, display humidity and
                // winds.
                if (isset($this->_params['detailedForecast'])) {

                    // Humidity.
                    $html .= '<td style="border:1px solid #ddd; text-align:center">' .
                        $day['night']['humidity'] . '%</td>';

                    // Winds.
                    $html .= '<td style="border:1px solid #ddd">' .
                        _("From the ") . $day['night']['windDirection'] .
                        _(" at ") . $day['night']['wind'] .
                        ' ' . $units['wind'];
                    if (isset($day['night']['windGust']) && $day['night']['windGust'] > 0) {
                        $html .= _(", gusting ") . $day['night']['windGust'] .
                            ' ' . $units['wind'];
                    }
                }

                $html .= '</tr>';

                // Prepare for next day.
                $futureDays++;
            }
            $html .= '</table>';
        }

        // Display a bar at the bottom of the block with the required
        // attribution to weather.com and the logo, both linked to
        // weather.com with the partner ID.
        return $html . '<div class="rightAlign">' .
            _("Weather data provided by") . ' ' .
            Horde::link(Horde::externalUrl('http://www.weather.com/?prod=xoap&amp;par=' .
                        $weatherDotCom->_partnerID),
                        'weather.com', '', '_blank', '', 'weather.com') .
            '<em>weather.com</em>&reg; ' .
            Horde::img('block/weatherdotcom/32x32/TWClogo_32px.png', 'weather.com logo') .
            '</a></div>';
    }

}
