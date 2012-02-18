<?php
/**
 * TimeObjects driver for exposing weatherunderground information via the
 * listTimeObjects API.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package TimeObjects
 */
class TimeObjects_Driver_Weather extends TimeObjects_Driver_Base
{
    protected $_forecastDays = Horde_Service_Weather::FORECAST_7DAY;
    protected $_location;

    public function __construct(array $params)
    {
        global $registry, $prefs;

        // Assume if it's passed in, we know it's valid.
        if (!empty($params['location'])) {
            $this->_location = $params['location'];
        } else {
            $this->_findLocation();
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
        if (empty($GLOBALS['conf']['weather']['provider']) || empty($this->_location)) {
            return false;
        }
        try {
            $this->_create();
        } catch (Exception $e) {
            return false;
        }

        return true;
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
        $forecast_end->mday += 7;
        if ($end->before($forecast_start) || $start->after($forecast_end)) {
            return array();
        }

        $weather = $this->_create();
        $lengths = $weather->getSupportedForecastLengths();
        try {
            $units = $weather->getUnits($weather->units);
            $forecast = $weather->getForecast($this->_location, max(array_keys($lengths)));
            $current = $weather->getCurrentConditions($this->_location);
        } catch (Horde_Service_Weather_Exception $e) {
            throw new Timeobjects_Exception($e);
        }

        $objects = array();
        foreach ($forecast as $data) {
            $day = $data->date;
            $day->hour = 0;
            $day->min = 0;
            $day->sec = 0;
            $day_end = clone $day;
            $day_end->mday++;

            $title = sprintf(
                '%s %d°%s/%d°%s',
                $data->conditions,
                $data->high,
                $units['temp'],
                $data->low,
                $units['temp']
            );

            // Deterine what information we have to display.
            $pop = $data->precipitation_percent === false ? _("N/A") : ($data->precipitation_percent . '%');
            if ($forecast->detail == Horde_Service_Weather::FORECAST_TYPE_STANDARD) {
                if ($data->humidity !== false && $data->wind_direction !== false) {
                    $description = sprintf(
                        _("Conditions: %s\nHigh temperature: %d%s\nPrecipitation: %s\nHumidity: %d%%\nWinds: From the %s at %d%s"),
                        _($data->conditions),
                        $data->high,
                        '°' . $units['temp'],
                        $pop,
                        $data->humidity,
                        $data->wind_direction,
                        $data->wind_speed,
                        $units['wind']
                    );
                } else {
                    $description = sprintf(
                        _("Conditions: %s\nHigh temperature: %d%s\nPrecipitation: %s\n"),
                        _($data->conditions),
                        $data->high,
                        '°' . $units['temp'],
                        $pop
                    );
                }
            } elseif ($forecast->detail == Horde_Service_Weather::FORECAST_TYPE_DETAILED) {
                // @TODO
                // No drivers support this yet. AccuWeather will, and possibly
                // wunderground if they accept my request.
            }
            $station = $weather->getStation();

            $body = sprintf(
                _("Location: %s"),
                $weather->getStation()->name
            );
            if (!empty($weather->getStation()->sunrise)) {
                $body .= sprintf(
                    _("Sunrise: %s\nSunset: %s\n"),
                   $weather->getStation()->sunrise,
                   $weather->getStation()->sunset
                );
            }

            $body  .= "\n" . $description;

            $objects[] = array(
                'id' => $day->timestamp(), //???
                'title' => $title,
                'description' => $body,
                'start' => $day->strftime('%Y-%m-%dT00:00:00'),
                'end' => $day_end->strftime('%Y-%m-%dT00:00:00'),
                'recurrence' => Horde_Date_Recurrence::RECUR_NONE,
                'params' => array(),
                'link' => new Horde_Url('#'),
                'icon' => (string)Horde_Themes::img('weather/23x23/' . $data->icon)
            );

            $day->mday++;
        }

        return $objects;
    }

    protected function _findLocation()
    {
        global $registry, $injector;

        // First use the location pref, then turba's "own" contact, followed
        // general IP location?
        $identity = $injector
            ->getInstance('Horde_Core_Factory_Identity')
            ->create();
        if (!($location = $identity->getValue('location')) &&
            $registry->hasInterface('contacts')) {
            try {
                $contact = $GLOBALS['registry']->contacts->ownContact();
            } catch (Exception $e) {
            }
            if (!empty($contact['homeCountry'])) {
                $country = $contact['homeCountry'];
            } elseif (!empty($contact['workCountry'])) {
                $country = $contact['workCountry'];
            }
            if (!empty($contact['homeCity'])) {
                $location = $contact['homeCity']
                    . (!empty($contact['homeProvince']) ? ', ' . $contact['homeProvince'] : '')
                    . (!empty($contact['homeCountry']) ? ', ' . $contact['homeCountry'] : '');
            } elseif (!empty($contact['workCity'])) {
                $location = $contact['workCity']
                    . (!empty($contact['workProvince']) ? ', ' . $contact['workProvince'] : '')
                    . (!empty($contact['workCountry']) ? ', ' . $contact['workCountry'] : '');
            }
        }

        // Ensure we have a valid location code for the location.
        try {
            $driver = $this->_create();
        } catch (Exception $e) {
            return;
        }
        if (!empty($location)) {
            try {
                $location = $driver->searchLocations($location);
            } catch (Horde_Service_Weather_Exception $e) {
                Horde::logMessage($e, 'ERR');
                throw new Timeobjects_Exception($e);
            }
        } else {
            try {
                $location = $driver->searchLocations(
                    $GLOBALS['browser']->getIPAddress(),
                    Horde_Service_Weather::SEARCHTYPE_IP);
            } catch (Horde_Service_Weather_Exception $e) {
                return;
            }
        }

        if (is_array($location)) {
            $location = $location[0];
        }
        $this->_location = $location->code;
    }

    /**
     * Private factory for weather driver.
     *
     * @return Horde_Service_Weather_Base
     */
    protected function _create()
    {
        try {
            $driver = $GLOBALS['injector']->getInstance('Horde_Weather');
        } catch (Exception $e) {
            throw new Timeobjects_Exception($e);
        }
        // Suggest units, but the driver may override this (like Google).
        $country = substr($GLOBALS['language'], -2);
        $driver->units = $country == 'US'
            ? Horde_Service_Weather::UNITS_STANDARD
            : Horde_Service_Weather::UNITS_METRIC;

        return $driver;
    }

}
