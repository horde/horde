<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Service_Weather
 */

/**
 * Horde_Service_Weather_Parser_Base
 *
 * Base class for parsing TAF/METAR data.
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
abstract class Horde_Service_Weather_Parser_Base
{
    const UNIT_KEY_TEMP = 'temp';
    const UNIT_KEY_SPEED = 'speed';
    const UNIT_KEY_PRESSURE = 'pressure';
    const UNIT_KEY_DISTANCE = 'distance';

    /**
     * The type of units to convert to.
     *
     * @var integer  A Horde_Service_Weather::UNITS_* constant.
     */
    protected $_units;

    /**
     * Mapping of what units to use for each type of value.
     * Built using self::_units
     *
     * @var array
     */
    protected $_unitMap;

    /**
     * Cloud cover code map.
     *
     * @var array
     */
    protected $_clouds = array();

    /**
     * CloudType map
     *
     * @var array
     */
    protected $_cloudTypes =  array();

    /**
     * Conditions map
     *
     * @var array
     */
    protected $_conditions = array();

    /**
     * Sensors map
     *
     * @var array
     */
    protected $_sensors = array();

    /**
     * constructor
     *
     * @param array $params  Parameter array:
     *   - units: (integer) The Horde_Service_Weather::UNITS_* constant.
     */
    public function __construct(array $params = array())
    {
        $this->_units = $params['units'];
        $this->_unitMap = array(
            self::UNIT_KEY_TEMP => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'c' : 'f',
            self::UNIT_KEY_SPEED => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'kph' : 'mph',
            self::UNIT_KEY_PRESSURE => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'mb' : 'in',
            self::UNIT_KEY_DISTANCE => $this->_units == Horde_Service_Weather::UNITS_METRIC ? 'km' : 'sm'
        );

        $this->_conditions = array(
            '+'   => Horde_Service_Weather_Translation::t('heavy'),
            '-'   => Horde_Service_Weather_Translation::t('light'),
            'vc'  => Horde_Service_Weather_Translation::t('vicinity'),
            're'  => Horde_Service_Weather_Translation::t('recent'),
            'nsw' => Horde_Service_Weather_Translation::t('no significant weather'),
            'mi'  => Horde_Service_Weather_Translation::t('shallow'),
            'bc'  => Horde_Service_Weather_Translation::t('patches'),
            'pr'  => Horde_Service_Weather_Translation::t('partial'),
            'ts'  => Horde_Service_Weather_Translation::t('thunderstorm'),
            'bl'  => Horde_Service_Weather_Translation::t('blowing'),
            'sh'  => Horde_Service_Weather_Translation::t('showers'),
            'dr'  => Horde_Service_Weather_Translation::t('low drifting'),
            'fz'  => Horde_Service_Weather_Translation::t('freezing'),
            'dz'  => Horde_Service_Weather_Translation::t('drizzle'),
            'ra'  => Horde_Service_Weather_Translation::t('rain'),
            'sn'  => Horde_Service_Weather_Translation::t('snow'),
            'sg'  => Horde_Service_Weather_Translation::t('snow grains'),
            'ic'  => Horde_Service_Weather_Translation::t('ice crystals'),
            'pe'  => Horde_Service_Weather_Translation::t('ice pellets'),
            'pl'  => Horde_Service_Weather_Translation::t('ice pellets'),
            'gr'  => Horde_Service_Weather_Translation::t('hail'),
            'gs'  => Horde_Service_Weather_Translation::t('small hail/snow pellets'),
            'up'  => Horde_Service_Weather_Translation::t('unknown precipitation'),
            'br'  => Horde_Service_Weather_Translation::t('mist'),
            'fg'  => Horde_Service_Weather_Translation::t('fog'),
            'fu'  => Horde_Service_Weather_Translation::t('smoke'),
            'va'  => Horde_Service_Weather_Translation::t('volcanic ash'),
            'sa'  => Horde_Service_Weather_Translation::t('sand'),
            'hz'  => Horde_Service_Weather_Translation::t('haze'),
            'py'  => Horde_Service_Weather_Translation::t('spray'),
            'du'  => Horde_Service_Weather_Translation::t('widespread dust'),
            'sq'  => Horde_Service_Weather_Translation::t('squall'),
            'ss'  => Horde_Service_Weather_Translation::t('sandstorm'),
            'ds'  => Horde_Service_Weather_Translation::t('duststorm'),
            'po'  => Horde_Service_Weather_Translation::t('well developed dust/sand whirls'),
            'fc'  => Horde_Service_Weather_Translation::t('funnel cloud'),
            '+fc' => Horde_Service_Weather_Translation::t('tornado/waterspout')
        );
        $this->_clouds = array(
            'skc' => Horde_Service_Weather_Translation::t('sky clear'),
            'nsc' => Horde_Service_Weather_Translation::t('no significant cloud'),
            'few' => Horde_Service_Weather_Translation::t('few'),
            'sct' => Horde_Service_Weather_Translation::t('scattered'),
            'bkn' => Horde_Service_Weather_Translation::t('broken'),
            'ovc' => Horde_Service_Weather_Translation::t('overcast'),
            'vv'  => Horde_Service_Weather_Translation::t('vertical visibility'),
            'tcu' => Horde_Service_Weather_Translation::t('Towering Cumulus'),
            'cb'  => Horde_Service_Weather_Translation::t('Cumulonimbus'),
            'clr' => Horde_Service_Weather_Translation::t('clear below 12,000 ft')
        );
        $this->_cloudTypes =  array(
            'low' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Cumulus (fair weather)'),
                '2' => Horde_Service_Weather_Translation::t('Cumulus (towering)'),
                '3' => Horde_Service_Weather_Translation::t('Cumulonimbus (no anvil)'),
                '4' => Horde_Service_Weather_Translation::t('Stratocumulus (from Cumulus)'),
                '5' => Horde_Service_Weather_Translation::t('Stratocumulus (not Cumulus)'),
                '6' => Horde_Service_Weather_Translation::t('Stratus or Fractostratus (fair)'),
                '7' => Horde_Service_Weather_Translation::t('Fractocumulus/Fractostratus (bad weather)'),
                '8' => Horde_Service_Weather_Translation::t('Cumulus and Stratocumulus'),
                '9' => Horde_Service_Weather_Translation::t('Cumulonimbus (thunderstorm)')
            ),
            'middle' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Altostratus (thin)'),
                '2' => Horde_Service_Weather_Translation::t('Altostratus (thick)'),
                '3' => Horde_Service_Weather_Translation::t('Altocumulus (thin)'),
                '4' => Horde_Service_Weather_Translation::t('Altocumulus (patchy)'),
                '5' => Horde_Service_Weather_Translation::t('Altocumulus (thickening)'),
                '6' => Horde_Service_Weather_Translation::t('Altocumulus (from Cumulus)'),
                '7' => Horde_Service_Weather_Translation::t('Altocumulus (w/ Altocumulus, Altostratus, Nimbostratus)'),
                '8' => Horde_Service_Weather_Translation::t('Altocumulus (w/ turrets)'),
                '9' => Horde_Service_Weather_Translation::t('Altocumulus (chaotic)')
            ),
            'high' => array(
                '/' => Horde_Service_Weather_Translation::t('Overcast'),
                '0' => Horde_Service_Weather_Translation::t('None'),
                '1' => Horde_Service_Weather_Translation::t('Cirrus (filaments)'),
                '2' => Horde_Service_Weather_Translation::t('Cirrus (dense)'),
                '3' => Horde_Service_Weather_Translation::t('Cirrus (often w/ Cumulonimbus)'),
                '4' => Horde_Service_Weather_Translation::t('Cirrus (thickening)'),
                '5' => Horde_Service_Weather_Translation::t('Cirrus/Cirrostratus (low in sky)'),
                '6' => Horde_Service_Weather_Translation::t('Cirrus/Cirrostratus (high in sky)'),
                '7' => Horde_Service_Weather_Translation::t('Cirrostratus (entire sky)'),
                '8' => Horde_Service_Weather_Translation::t('Cirrostratus (partial)'),
                '9' => Horde_Service_Weather_Translation::t('Cirrocumulus or Cirrocumulus/Cirrus/Cirrostratus')
            )
        );

        $this->_sensors = array(
            'rvrno'  => Horde_Service_Weather_Translation::t('Runway Visual Range Detector offline'),
            'pwino'  => Horde_Service_Weather_Translation::t('Present Weather Identifier offline'),
            'pno'    => Horde_Service_Weather_Translation::t('Tipping Bucket Rain Gauge offline'),
            'fzrano' => Horde_Service_Weather_Translation::t('Freezing Rain Sensor offline'),
            'tsno'   => Horde_Service_Weather_Translation::t('Lightning Detection System offline'),
            'visno'  => Horde_Service_Weather_Translation::t('2nd Visibility Sensor offline'),
            'chino'  => Horde_Service_Weather_Translation::t('2nd Ceiling Height Indicator offline')
        );
    }

    /**
     * Parse the raw data.
     *
     * @param string $data  The raw TAF or METAR data.
     *
     * @return array  The parsed data array.
     */
    public function parse($data)
    {
        return $this->_parse(preg_split('/\n|\r\n|\n\r/', $data));
    }

    abstract protected function _parse(array $data);
}