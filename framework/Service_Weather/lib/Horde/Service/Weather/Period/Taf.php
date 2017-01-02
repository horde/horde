<?php
/**
 * This file contains the Horde_Service_Weather_Period class for abstracting
 * access to a single forecast period from TAF encoded sources.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Period_Taf
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
class Horde_Service_Weather_Period_Taf extends Horde_Service_Weather_Period_Base
{
    /**
     * Property Map
     *
     * @var array
     */
     protected $_map = array(
        'wind_speed' => 'wind',
        'wind_direction' => 'windDirection',
        'wind_degrees' => 'windDegrees',
        'wind_gust' => 'windGust',
        'high' => 'temperatureHigh',
        'low' => 'temperatureLow'
    );

    /**
     * Accessor so we can lazy-parse the results.
     *
     * @param string $property  The property name.
     *
     * @return mixed  The value of requested property
     * @throws Horde_Service_Weather_Exception_InvalidProperty
     */
    public function __get($property)
    {
        switch ($property) {
        case 'is_pm':
        case 'hour':
        case 'humidity':
        case 'precipitation_percent':
        case 'wind_gust':
        case 'snow_total':
        case 'rain_total':
        case 'icon_url':
        case 'icon':
            return false;

        case 'conditions':
            $units = $this->_forecast->weather->getUnits();
            $conds = '';
            // Note that most of these properties will only
            // be included if different from the main MFC section.
            // Wind
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

            if (!empty($this->_properties['condition'])) {
                $conds .= $this->_properties['condition'] . ' ';
            }

            // @todo This isn't totally acurate since you could have e.g., BKN
            // clouds below OVC cloud cover. Probably should iterate over all
            // layers and just include the highest coverage.
            if (!empty($this->_properties['clouds'])) {
                $conds .= sprintf('Sky %s ',
                    $this->_properties['clouds'][0]['amount']
                );
            }
            return trim($conds);

        case 'date':
            return new Horde_Date($this->_forecast->validFrom);

        default:
            if (!empty($this->_properties[$property])) {
                return $this->_properties[$property];
            }
            if (!empty($this->_map[$property])) {
                return !empty($this->_properties[$this->_map[$property]])
                    ? $this->_properties[$this->_map[$property]]
                    : false;
            }
            throw new Horde_Service_Weather_Exception_InvalidProperty('This provider does not support the "' . $property . '" property');
        }
    }

}