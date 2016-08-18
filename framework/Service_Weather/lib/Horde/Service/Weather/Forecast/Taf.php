<?php
/**
 * This file contains the Horde_Service_Weather_Forecast class for abstracting
 * access to forecast data from TAF encoded weather sources.
 *
 * Copyright 2011-2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Forecast_Taf
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Service_Weather
 */
 class Horde_Service_Weather_Forecast_Taf extends Horde_Service_Weather_Forecast_Base
 {
    /**
     * Const'r
     *
     * @param array $properties                    Forecast properties.
     * @param Horde_Service_Weather_base $weather  The base driver.
     * @param integer $type                        The forecast type.
     */
    public function __construct(
        $properties,
        Horde_Service_Weather_Base $weather,
        $type = Horde_Service_Weather::FORECAST_TYPE_STANDARD)
    {
        parent::__construct($properties, $weather, $type);
        $this->_parsePeriods();
    }

    /**
     * Compatibility layer for old PEAR/Services_Weather data.
     *
     * @return array  The raw parsed data array - keyed by descriptors that are
     *                compatible with PEAR/Services_Weather. Structure of data:
     *                Data is converted into the appropriate units based on
     *                the Horde_Service_Weather_Base::units setting at the time
     *                or parsing.
     *
     * - station:         The station identifier.
     * - dataRaw:         The raw TAF data.
     * - update:          The update timestamp.
     * - updateRaw:       The raw TAF encoded update time.
     * - validRaw:        The raw TAF encoded valid forecast times.
     * - validFrom:       The valid forecast FROM time.
     * - validTo:         The valid forecast TO time.
     * - time:            Array containing an entry for each weather section.
     *                    Basically each entry contains forcasted changes
     *                    beginning at the time of the key to the entry.
     *   - wind:              The wind speed.
     *   - windDegrees:       The wind direction in degrees.
     *   - windDirection:     The wind direction in a cardinal compass direction.
     *   - windGust:          The wind gust speed.
     *   - windProb:          Probability of forecast wind.
     *   - visibility:        Visibility distance.
     *   - visQualifier:      Qualifier of visibility. I.e., "AT", "BEYOND", "BELOW"
     *   - visProb:           Probability of forecast visibility.
     *   - clouds:            Array containing cloud layer information:
     *     - amount:            Amount of sky cover. I.e., "BROKEN", "OVERCAST"
     *     - height:            The height of the base of the cloud layer.
     *     - type:              The type of clouds if available.
     *   - condition:         The weather condition. I.e., "RAIN", "MIST"
     *   - windshear:         Windshear delta.
     *   - windshearHeight:   The height of windshear.
     *   - windshearDegrees:  The degrees of windshear.
     *   - windshearDirection:The compass direction of windshear.
     *   - temperatureLow:    The forecast low temperature.
     *   - temperatureHigh:   The forecast high temperature.
     *   - fmc:               Array containing any FMC changes. I.e, "TEMPO", or
     *                        "BECMG" lines.
     *     - from:            Horde_Date representing the starting time of the
     *                        FMC change.
     *     - to:              Horde_Date representing the ending time of the FMC
     *                        period.
     */
    public function getRawData()
    {
        return $this->_properties;
    }

    /**
     * Return the time of the forecast, in local (to station) time.
     *
     * @return Horde_Date  The time of the forecast.
     */
    public function getForecastTime()
    {
        return new Horde_Date($this->_properties['update']);
    }

    protected function _parsePeriods()
    {
        foreach ($this->_properties['time'] as $time => $data) {
            $data['period'] = $time;
            $this->_periods[] = new Horde_Service_Weather_Period_Taf($data, $this);
        }
    }

 }