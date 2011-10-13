<?php
/**
 * TimeObjects driver for exposing weather.com data via the listTimeObjects
 * API.
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
class TimeObjects_Driver_Weatherdotcom extends TimeObjects_Driver_Base
{
    protected $_params = array('units' => 'standard',
                               'days' => 5);

    public function __construct(array $params)
    {
        global $registry, $prefs;

        $country = substr($GLOBALS['language'], -2);
        if (empty($params['location'])) {
            // First use the location pref, then turba's "own" contact, followed
            // by perhaps a general IP location?
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
            if ($location = $identity->getValue('location')) {
                $params['location'] = $location;
            } elseif ($registry->hasInterface('contacts')) {
                try {
                    $contact = $GLOBALS['registry']->contacts->ownContact();
                } catch (Exception $e) {
                    throw new TimeObjects_Exception($e);
                }
                if (!empty($contact['homeCountry'])) {
                    $country = $contact['homeCountry'];
                } elseif (!empty($contact['workCountry'])) {
                    $country = $contact['workCountry'];
                }
                if (!empty($contact['homeCity'])) {
                    $params['location'] = $contact['homeCity']
                        . (!empty($contact['homeProvince']) ? ', ' . $contact['homeProvince'] : '')
                        . (!empty($contact['homeCountry']) ? ', ' . $contact['homeCountry'] : '');
                } else {
                    $params['location'] = $contact['workCity']
                        . (!empty($contact['workProvince']) ? ', ' . $contact['workProvince'] : '')
                        . (!empty($contact['workCountry']) ? ', ' . $contact['workCountry'] : '');
                }
            }
        }

        if ($country != 'US') {
            $params['units'] = 'metric';
        }

        parent::__construct($params);
    }

    /**
     * Ensures that we meet all requirements to use this time object
     *
     * @return boolean
     */
    public function ensure()
    {
        return class_exists('Services_Weather') &&
            class_exists('Cache') &&
            !empty($this->_params['location']) &&
            !empty($GLOBALS['conf']['weatherdotcom']['partner_id']) &&
            !empty($GLOBALS['conf']['weatherdotcom']['license_key']);
    }

    /**
     *
     * @param Horde_Date $start  The start time of the period
     * @param Horde_Date $end   The end time of the period
     *
     * @return array of listTimeObjects arrays.
     */
    public function listTimeObjects(Horde_Date $start = null, Horde_Date $end = null)
    {
        global $conf, $prefs;

        // No need to continue if the forecast days are not in the current
        // range.
        $forecast_start = new Horde_Date(time());
        $forecast_end = clone $forecast_start;

        // Today is day 1, so subtract a day
        $forecast_end->mday += $this->_params['days'] - 1;
        if ($end->before($forecast_start) || $start->after($forecast_end)) {
            return array();
        }

        if (!class_exists('Services_Weather') || !class_exists('Cache')) {
            throw new TimeObjects_Exception('Services_Weather or PEAR Cache Classes not found.');
        }

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
            throw new TimeObjects_Exception(_("No location is set."));
        }

        $weatherDotCom = &Services_Weather::service('WeatherDotCom', $options);
        $weatherDotCom->setAccountData($conf['weatherdotcom']['partner_id'],
                                       $conf['weatherdotcom']['license_key']);

        $cacheDir = Horde::getTempDir();
        if (!$cacheDir) {
            throw new TimeObjects_Exception(_("No temporary directory available for cache."));
        } else {
            $weatherDotCom->setCache('file', array('cache_dir' => ($cacheDir . '/')));
        }
        $units = $weatherDotCom->getUnitsFormat();

        // If the user entered a zip code for the location, no need to
        // search (weather.com accepts zip codes as location IDs).
        $search = (preg_match('/\b(?:\\d{5}(-\\d{5})?)|(?:[A-Z]{4}\\d{4})\b/',
            $this->_params['location'], $matches) ?
            $matches[0] :
            $weatherDotCom->searchLocation($this->_params['location']));
        if (is_a($search, 'PEAR_Error')) {
            switch ($search->getCode()) {
            case SERVICES_WEATHER_ERROR_SERVICE_NOT_FOUND:
                throw new TimeObjects_Exception(_("Requested service could not be found."));
            case SERVICES_WEATHER_ERROR_UNKNOWN_LOCATION:
                throw new TimeObjects_Exception(_("Unknown location provided."));
            case SERVICES_WEATHER_ERROR_WRONG_SERVER_DATA:
                throw new TimeObjects_Exception(_("Server data wrong or not available."));
            case SERVICES_WEATHER_ERROR_CACHE_INIT_FAILED:
                throw new TimeObjects_Exception(_("Cache init was not completed."));
            case SERVICES_WEATHER_ERROR_DB_NOT_CONNECTED:
                throw new TimeObjects_Exception(_("MetarDB is not connected."));
            case SERVICES_WEATHER_ERROR_UNKNOWN_ERROR:
                throw new TimeObjects_Exception(_("An unknown error has occured."));
            case SERVICES_WEATHER_ERROR_NO_LOCATION:
                throw new TimeObjects_Exception(_("No location provided."));
            case SERVICES_WEATHER_ERROR_INVALID_LOCATION:
                throw new TimeObjects_Exception(_("Invalid location provided."));
            case SERVICES_WEATHER_ERROR_INVALID_PARTNER_ID:
                throw new TimeObjects_Exception(_("Invalid partner id."));
            case SERVICES_WEATHER_ERROR_INVALID_PRODUCT_CODE:
                throw new TimeObjects_Exception(_("Invalid product code."));
            case SERVICES_WEATHER_ERROR_INVALID_LICENSE_KEY:
               throw new TimeObjects_Exception(_("Invalid license key."));
            default:
                throw new TimeObjects_Exception($search->getMessage());
            }
        }
        if (is_array($search)) {
            $search = key($search);
        }
        $forecast = $weatherDotCom->getForecast($search, $this->_params['days']);
        if (is_a($forecast, 'PEAR_Error')) {
            throw new TimeObjects_Exception($forecast->getMessage());
        }

        $location = $weatherDotCom->getLocation($search);
        if (is_a($location, 'PEAR_Error')) {
            throw new TimeObjects_Exception($location->getMessage());
        }

        $day = new Horde_Date($forecast['update']);
        $objects = array();
        foreach ($forecast['days'] as $which => $data) {
            $day_end = clone $day;
            $day_end->mday++;

            // For day 0, the day portion isn't available after a certain time
            // simplify and just check for it's presence or use night.
            if (empty($data['day']['condition']) ||
                $data['day']['condition'] == 'N/A') {
                $condition = $data['night']['condition'];
            } else {
                $condition = $data['day']['condition'];
            }
            $condition = implode(' / ', array_map('_', explode(' / ', $condition)));
            if ($data['temperatureHigh'] == 'N/A') {
                $title = sprintf('%s %d°%s',
                                 $condition,
                                 $data['temperatureLow'],
                                 Horde_String::upper($units['temp']));
            } else {
                $title = sprintf('%s %d°%s/%d°%s',
                                 $condition,
                                 $data['temperatureHigh'],
                                 Horde_String::upper($units['temp']),
                                 $data['temperatureLow'],
                                 Horde_String::upper($units['temp']));
            }
            $daytime = sprintf(_("Conditions: %s\nHigh temperature: %d%s\nPrecipitation: %d%%\nHumidity: %d%%\nWinds: From the %s at %d%s"),
                               _($data['day']['condition']),
                               $data['temperatureHigh'],
                               '°' . Horde_String::upper($units['temp']),
                               $data['day']['precipitation'],
                               $data['day']['humidity'],
                               $data['day']['windDirection'],
                               $data['day']['wind'],
                               $units['wind']);
            if (!empty($data['day']['windGust']) &&
                $data['day']['windgust'] > 0) {
                $daytime .= sprintf(_(" gusting %d%s"),
                                    $data['day']['windgust'],
                                    Horde_String::upper($units['wind']));
            }
            $nighttime = sprintf(_("Conditions: %s\nLow temperature: %d%s\nPrecipitation: %d%%\nHumidity: %d%%\nWinds: From the %s at %d%s"),
                                 _($data['night']['condition']),
                                 $data['temperatureLow'],
                                 '°' . Horde_String::upper($units['temp']),
                                 $data['night']['precipitation'],
                                 $data['night']['humidity'],
                                 $data['night']['windDirection'],
                                 $data['night']['wind'],
                                 $units['wind']);
            if (!empty($data['night']['windGust']) &&
                $data['night']['windgust'] > 0) {
                $nighttime .= sprintf(_(" gusting %d%s"),
                                      $data['night']['windgust'],
                                      $units['wind']);
            }
            $description = sprintf(_("Location: %s\nSunrise: %s\nSunset: %s\n\nDay\n%s\n\nEvening\n%s"),
                                   $location['name'],
                                   $data['sunrise'],
                                   $data['sunset'],
                                   $daytime,
                                   $nighttime);
            $objects[] = array('id' => $day->timestamp(), //???
                               'title' => $title,
                               'description' => $description,
                               'start' => $day->strftime('%Y-%m-%dT00:00:00'),
                               'end' => $day_end->strftime('%Y-%m-%dT00:00:00'),
                               'recurrence' => Horde_Date_Recurrence::RECUR_NONE,
                               'params' => array(),
                               'link' => new Horde_Url('#'),
                               'icon' =>  (string)Horde::url(Horde_Themes::img('block/weatherdotcom/23x23/' . ($data['day']['conditionIcon'] == '-' ? 'na' : $data['day']['conditionIcon']) . '.png'), true, false));

            $day->mday++;
        }
        return $objects;
    }

}
