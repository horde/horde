<?php
/**
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Current_Metar
 *
 * Responsible for parsing encoded METAR text and presenting human readable
 * weather data.
 *
 * Parsing code adapted from PEAR's Services_Weather_Metar class. Original
 * phpdoc attributes as follows:
 * @author      Alexander Wirtz <alex@pc4p.net>
 * @copyright   2005-2011 Alexander Wirtz
 * @link        http://pear.php.net/package/Services_Weather
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Current_Metar extends Horde_Service_Weather_Current_Base
{
    protected $_map = array(
        'dewpoint' => 'dewPoint',
        'wind_direction' => 'windDirection',
        'wind_degrees' => 'windDegrees',
        'wind_speed' => 'wind',
        'wind_gust' => 'windGust',
        'wind_chill' => 'feltTemperature',
        'temp' => 'temperature',
    );

    /**
     * Compatibility layer for old PEAR/Services_Weather data.
     *
     * @return array  The raw parsed data array - keyed by descriptors that are
     *   compatible with PEAR/Services_Weather. The following  keys may be
     *   returned:
     *
     *  - station:
     *  - dataRaw:
     *  - update:
     *  - updateRaw:
     *  - wind:
     *  - windDegrees:
     *  - windDirection:
     *  - windGust:
     *  - windVariability:
     *  - visibility:
     *  - visQualifier:
     *  - clouds:
     *    - amount
     *    - height
     *    - type
     *  - temperature
     *  - dewpoint
     *  - humidity
     *  - felttemperature
     *  - pressure
     *  - trend
     *    - type
     *    - from
     *    - to
     *    - at
     *  - remark
     *    - autostation
     *    - seapressure
     *    - presschg
     *    - snowdepth
     *    - snowequiv
     *    - cloudtypes
     *    - sunduration
     *    - 1hrtemp
     *    - 1hrdew
     *    - 6hmaxtemp
     *    - 6hmintemp
     *    - 24hmaxtemp
     *    - 24hmintemp
     *    - 3hpresstrend
     *    - nospeci
     *    - sensors
     *    - maintain
     *  - precipitation
     *    - amount
     *    - hours
     */
    public function getRawData()
    {
        $this->_properties['update'] = new Horde_Date($this->_properties['update']);
        return $this->_properties;
    }

    public function __get($property)
    {
        switch ($property) {
        // These are unsupported
        case 'logo_url':
        case 'heat_index':
        case 'icon':
        case 'icon_url':
            return null;
        case 'time':
            return new Horde_Date($this->_properties['update']);
        case 'pressure_trend':
            return !empty($this->_properties['remark']['presschg'])
                ? $this->_properties['remark']['presschg']
                : null;
        case 'condition':
        case 'conditions':
            // Not really translatable from METAR data...but try to generate
            // some sensible human readable data.
            $units = $this->_weather->getUnits();
            $conds = '';
            if (!empty($this->_properties['wind'])) {
                $conds .= sprintf(
                    Horde_Service_Weather_Translation::t('Wind from %s at %s%s '),
                    $this->_properties['windDirection'],
                    $this->_properties['wind'],
                    $units['wind']
                );
            }

            // Visibility - this *should* always be here.
            $conds .= sprintf(
                Horde_Service_Weather_Translation::t('Visibility %s %s %s '),
                $this->_properties['visQualifier'],
                $this->_properties['visibility'],
                $units['vis']
            );

            // @todo This isn't totally acurate since you could have e.g., BKN
            // clouds below OVC cloud cover. Probably should iterate over all
            // layers and just include the highest coverage.
            if (!empty($this->_properties['clouds'])) {
                $conds .= sprintf('Sky %s ',
                    $this->_properties['clouds'][0]['amount']
                );
            }
            return trim($conds);

        default:
            if (!empty($this->_properties[$property])) {
                return $this->_properties[$property];
            } elseif (!empty($this->_map[$property])) {
                return $this->_properties[$this->_map[$property]];
            }

            throw new Horde_Service_Weather_Exception_InvalidProperty();
        }
    }

}