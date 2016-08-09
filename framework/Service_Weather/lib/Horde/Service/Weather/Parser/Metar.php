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
 * Horde_Service_Weather_Parser_Metar
 *
 * Responsible for parsing encoded METAR data.
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
class Horde_Service_Weather_Parser_Metar extends Horde_Service_Weather_Parser_Base
{

    /**
     * Map of hours descriptors.
     *
     * @var array
     */
    protected $_hours = array(
        'P' => '1',
        '6' => '3/6',
        '7' => '24'
    );

    protected function _parse(array $data)
    {
        // Eliminate trailing information
        for ($i = 0; $i < sizeof($data); $i++) {
            if (strpos($data[$i], '=') !== false) {
                $data[$i] = substr($data[$i], 0, strpos($data[$i], '='));
                $data = array_slice($data, 0, $i + 1);
                break;
            }
        }

        // Start with parsing the first line for the last update
        $weatherData = array();
        $weatherData['station'] = '';
        $weatherData['dataRaw'] = implode(' ', $data);
        $weatherData['update'] = strtotime(trim($data[0]) .' GMT');
        $weatherData['updateRaw'] = trim($data[0]);

        // and prepare the rest for stepping through
        array_shift($data);
        $metar = explode(' ', preg_replace('/\s{2,}/', ' ', implode(' ', $data)));

        // Trend handling
        $trendCount = 0;

        // Pointer to the array we add the data to. Needed for handling trends.
        $pointer = &$weatherData;

        // Load the metar codes for this go around.
        $metarCode = $this->_getMetarCodes();

        for ($i = 0; $i < sizeof($metar); $i++) {
            $metar[$i] = trim($metar[$i]);
            if (!strlen($metar[$i])) {
                continue;
            }
            $result   = array();
            $resultVF = array();
            $lresult  = array();
            $found = false;

            foreach ($metarCode as $key => $regexp) {
                // Check if current code matches current metar snippet
                if (($found = preg_match('/^' . $regexp . '$/i', $metar[$i], $result)) == true) {
                    switch ($key) {
                    case 'station':
                        $pointer['station'] = $result[0];
                        unset($metarCode['station']);
                        break;
                    case 'wind':
                        $pointer['wind'] = Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        );
                        $wind_mph = Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            'mph',
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        );
                        if ($result[1] == 'VAR' || $result[1] == 'VRB') {
                            // Variable winds
                            $pointer['windDegrees']   = Horde_Service_Weather_Translation::t('Variable');
                            $pointer['windDirection'] = Horde_Service_Weather_Translation::t('Variable');
                        } else {
                            // Save wind degree and calc direction
                            $pointer['windDegrees']   = intval($result[1]);
                            $pointer['windDirection'] = Horde_Service_Weather::degToDirection($result[1]);
                        }
                        if (is_numeric($result[4])) {
                            // Wind with gusts...
                            $pointer['windGust'] = Horde_Service_Weather::convertSpeed(
                                $result[4],
                                $result[5],
                                $this->_unitMap[self::UNIT_KEY_SPEED]
                            );
                        }
                        break;
                    case 'windVar':
                        // Once more wind, now variability around the current wind-direction
                        $pointer['windVariability'] = array(
                            'from' => intval($result[1]),
                            'to' => intval($result[2])
                        );
                        break;
                    case 'visFrac':
                        // Possible fractional visibility here. Check if it matches with the next METAR piece for visibility
                        if (!isset($metar[$i + 1]) ||
                            !preg_match('/^' . $metarCode['visibility'] . '$/i', $result[1] . ' ' . $metar[$i + 1], $resultVF)) {
                            // No next METAR piece available or not matching.
                            $found = false;
                            break;
                        } else {
                            // Match. Hand over result and advance METAR
                            $key = 'visibility';
                            $result = $resultVF;
                            $i++;
                        }
                    case 'visibility':
                        $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('AT');
                        if (is_numeric($result[1]) && ($result[1] == 9999)) {
                            // Upper limit of visibility range is 10KM.
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                        } elseif (is_numeric($result[1])) {
                            // 4-digit visibility in m
                            $visibility = Horde_Service_Weather::convertDistance(
                                $result[1],
                                'm',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                        } elseif (!isset($result[11]) || $result[11] != 'CAVOK') {
                            if ($result[3] == 'M') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BELOW');
                            } elseif ($result[3] == 'P') {
                                $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            }
                            if (is_numeric($result[5])) {
                                // visibility as one/two-digit number
                                $visibility = Horde_Service_Weather::convertDistance(
                                    $result[5],
                                    $result[10],
                                    $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                );
                            } else {
                                // the y/z part, add if we had a x part (see visibility1)
                                if (is_numeric($result[7])) {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[7] + $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                    );
                                } else {
                                    $visibility = Horde_Service_Weather::convertDistance(
                                        $result[8] / $result[9],
                                        $result[10],
                                        $this->_unitMap[self::UNIT_KEY_DISTANCE]
                                    );
                                }
                            }
                        } else {
                            $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('BEYOND');
                            $visibility = Horde_Service_Weather::convertDistance(
                                10,
                                'km',
                                $this->_unitMap[self::UNIT_KEY_DISTANCE]
                            );
                            $pointer['clouds'] = array(array(
                                'amount' => Horde_Service_Weather_Translation::t('Clear below'),
                                'height' => 5000)
                            );
                            $pointer['condition'] = Horde_Service_Weather_Translation::t('no significant weather');
                        }
                        $pointer['visibility'] = $visibility;
                        break;
                    case 'condition':
                        if (!isset($pointer['condition'])) {
                            $pointer['condition'] = '';
                        } elseif (strlen($pointer['condition']) > 0) {
                            $pointer['condition'] .= ',';
                        }

                        if (in_array(strtolower($result[0]), $this->_conditions)) {
                            // First try matching the complete string
                            $pointer['condition'] .= ' ' . $this->_conditions[strtolower($result[0])];
                        } else {
                            // No luck, match part by part
                            array_shift($result);
                            $result = array_unique($result);
                            foreach ($result as $condition) {
                                if (strlen($condition) > 0) {
                                    $pointer['condition'] .= ' ' . $this->_conditions[strtolower($condition)];
                                }
                            }
                        }
                        $pointer['condition'] = trim($pointer['condition']);
                        break;
                    case 'clouds':
                        if (!isset($pointer['clouds'])) {
                            $pointer['clouds'] = array();
                        }

                        if (sizeof($result) == 5) {
                            // Only amount and height
                            $cloud = array('amount' => $this->_clouds[strtolower($result[3])]);
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } elseif (sizeof($result) == 6) {
                            // Amount, height and type
                            $cloud = array(
                                'amount' => $this->_clouds[strtolower($result[3])],
                                'type' => $this->_clouds[strtolower($result[5])]
                            );
                            if ($result[4] == '///') {
                                $cloud['height'] = Horde_Service_Weather_Translation::t('station level or below');
                            } else {
                                $cloud['height'] = $result[4] * 100;
                            }
                        } else {
                            // SKC or CLR or NSC
                            $cloud = array('amount' => $this->_clouds[strtolower($result[0])]);
                        }
                        $pointer['clouds'][] = $cloud;
                        break;
                    case 'temperature':
                        // normal temperature in first part
                        // negative value
                        if ($result[1] == 'M') {
                            $result[2] *= -1;
                        }
                        $pointer['temperature'] = Horde_Service_Weather::convertTemperature(
                            $result[2],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        $temp_f = Horde_Service_Weather::convertTemperature($result[2], 'c', 'f');
                        if (sizeof($result) > 4) {
                            // same for dewpoint
                            if ($result[4] == 'M') {
                                $result[5] *= -1;
                            }
                            $pointer['dewPoint'] = Horde_Service_Weather::convertTemperature(
                                $result[5],
                                'c',
                                $this->_unitMap[self::UNIT_KEY_TEMP]
                            );
                            $pointer['humidity'] = Horde_Service_Weather::calculateHumidity($result[2], $result[5]);
                        }
                        if (isset($pointer['wind'])) {
                            // Now calculate windchill from temperature and windspeed
                            // Note these must be in F and MPH.
                            $pointer['feltTemperature'] = Horde_Service_Weather::calculateWindChill($temp_f, $wind_mph);
                        }
                        break;
                    case 'pressure':
                        if ($result[1] == 'A') {
                            // Pressure provided in inches
                            $pointer['pressure'] = $result[2] / 100;
                        } elseif ($result[3] == 'Q') {
                            // ... in hectopascal
                            $pointer['pressure'] = Horde_Service_Weather::convertPressure(
                                $result[4],
                                'hpa',
                                $this->_unitMap[self::UNIT_KEY_PRESSURE]
                            );
                        }
                        break;
                    case 'trend':
                        // We may have a trend here... extract type and set pointer on
                        // created new array
                        if (!isset($weatherData['trend'])) {
                            $weatherData['trend'] = array();
                            $weatherData['trend'][$trendCount] = array();
                        }
                        $pointer = &$weatherData['trend'][$trendCount];
                        $trendCount++;
                        $pointer['type'] = $result[0];
                        while (isset($metar[$i + 1]) && preg_match('/^(FM|TL|AT)(\d{2})(\d{2})$/i', $metar[$i + 1], $lresult)) {
                            if ($lresult[1] == 'FM') {
                                $pointer['from'] = $lresult[2] . ':' . $lresult[3];
                            } elseif ($lresult[1] == 'TL') {
                                $pointer['to'] = $lresult[2] . ':' . $lresult[3];
                            } else {
                                $pointer['at'] = $lresult[2] . ':' . $lresult[3];
                            }
                            // As we have just extracted the time for this trend
                            // from our METAR, increase field-counter
                            $i++;
                        }
                        break;
                    case 'remark':
                        // Remark part begins
                        $metarCode = $this->_getRemarks();
                        $weatherData['remark'] = array();
                        break;
                    case 'autostation':
                        // Which autostation do we have here?
                        if ($result[1] == 0) {
                            $weatherData['remark']['autostation'] = Horde_Service_Weather_Translation::t('Automatic weatherstation w/o precipitation discriminator');
                        } else {
                            $weatherData['remark']['autostation'] = Horde_Service_Weather_Translation::t('Automatic weatherstation w/ precipitation discriminator');
                        }
                        unset($metarCode['autostation']);
                        break;
                    case 'presschg':
                        // Decoding for rapid pressure changes
                        if (strtolower($result[1]) == 'r') {
                            $weatherData['remark']['presschg'] = Horde_Service_Weather_Translation::t('Pressure rising rapidly');
                        } else {
                            $weatherData['remark']['presschg'] = Horde_Service_Weather_Translation::t('Pressure falling rapidly');
                        }
                        unset($metarCode['presschg']);
                        break;
                    case 'seapressure':
                        // Pressure at sea level (delivered in hpa)
                        // Decoding is a bit obscure as 982 gets 998.2
                        // whereas 113 becomes 1113 -> no real rule here
                        if (strtolower($result[1]) != 'no') {
                            if ($result[1] > 500) {
                                $press = 900 + round($result[1] / 100, 1);
                            } else {
                                $press = 1000 + $result[1];
                            }
                            $weatherData['remark']['seapressure'] = Horde_Service_Weather::convertPressure(
                                $press,
                                'hpa',
                                $this->_unitMap[self::UNIT_KEY_PRESSURE]
                            );
                        }
                        unset($metarCode['seapressure']);
                        break;
                    case 'precip':
                        // Precipitation in inches
                        if (!isset($weatherData['precipitation'])) {
                            $weatherData['precipitation'] = array();
                        }
                        if (!is_numeric($result[2])) {
                            $precip = 'indeterminable';
                        } elseif ($result[2] == '0000') {
                            $precip = 'traceable';
                        } else {
                            $precip = $result[2] / 100;
                        }
                        $weatherData['precipitation'][] = array(
                            'amount' => $precip,
                            'hours'  => $this->_hours[$result[1]]
                        );
                        break;
                    case 'snowdepth':
                        // Snow depth in inches
                        // @todo convert to metric
                        $weatherData['remark']['snowdepth'] = $result[1];
                        unset($metarCode['snowdepth']);
                        break;
                    case 'snowequiv':
                        // Same for equivalent in Water... (inches)
                        // @todo convert
                        $weatherData['remark']['snowequiv'] = $result[1] / 10;
                        unset($metarCode['snowequiv']);
                        break;
                    case 'cloudtypes':
                        // Cloud types
                        $weatherData['remark']['cloudtypes'] = array(
                            'low'    => $this->_cloudTypes['low'][$result[1]],
                            'middle' => $this->_cloudTypes['middle'][$result[2]],
                            'high'   => $this->_cloudTypes['high'][$result[3]]
                        );
                        unset($metarCode['cloudtypes']);
                        break;
                    case 'sunduration':
                        // Duration of sunshine (in minutes)
                        $weatherData['remark']['sunduration'] = sprintf(
                            Horde_Service_Weather_Translation::t('Total minutes of sunshine: %s'),
                            $result[1]
                        );
                        unset($metarCode['sunduration']);
                        break;
                    case '1htempdew':
                        // Temperatures in the last hour in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['1htemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );

                        if (sizeof($result) > 3) {
                            // same for dewpoint
                            if ($result[4] == '1') {
                                $result[5] *= -1;
                            }
                            $weatherData['remark']['1hdew'] = Horde_Service_Weather::convertTemperature(
                                $result[5] / 10,
                                'c',
                                $this->_unitMap[self::UNIT_KEY_TEMP]
                            );
                        }
                        unset($metarCode['1htempdew']);
                        break;
                    case '6hmaxtemp':
                        // Max temperature in the last 6 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['6hmaxtemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['6hmaxtemp']);
                        break;
                    case '6hmintemp':
                        // Min temperature in the last 6 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['6hmintemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['6hmintemp']);
                        break;
                    case '24htemp':
                        // Max/Min temperatures in the last 24 hours in C
                        if ($result[1] == '1') {
                            $result[2] *= -1;
                        }
                        $weatherData['remark']['24hmaxtemp'] = Horde_Service_Weather::convertTemperature(
                            $result[2] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );

                        if ($result[3] == '1') {
                            $result[4] *= -1;
                        }
                        $weatherData['remark']['24hmintemp'] = Horde_Service_Weather::convertTemperature(
                            $result[4] / 10,
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        unset($metarCode['24htemp']);
                        break;
                    case '3hpresstend':
                        // Pressure tendency of the last 3 hours
                        // no special processing, just passing the data
                        $weatherData['remark']['3hpresstend'] = array(
                            'presscode' => $result[1],
                            'presschng' => Horde_Service_Weather::convertPressure($result[2] / 10, 'hpa', $this->_unitMap[self::UNIT_KEY_PRESSURE])
                        );
                        unset($metarCode['3hpresstend']);
                        break;
                    case 'nospeci':
                        // No change during the last hour
                        $weatherData['remark']['nospeci'] = Horde_Service_Weather_Translation::t('No changes in weather conditions');
                        unset($metarCode['nospeci']);
                        break;
                    case 'sensors':
                        // We may have multiple broken sensors, so do not unset
                        if (!isset($weatherData['remark']['sensors'])) {
                            $weatherData['remark']['sensors'] = array();
                        }
                        $weatherData['remark']['sensors'][strtolower($result[0])] = $this->_sensors[strtolower($result[0])];
                        break;
                    case 'maintain':
                        $weatherData['remark']['maintain'] = Horde_Service_Weather_Translation::t('Maintainance needed');
                        unset($metarCode['maintain']);
                        break;
                    default:
                        // Do nothing, just prevent further matching
                        unset($metarCode[$key]);
                        break;
                    }
                    if ($found) {
                        break;
                    }
                }
            }
        }

        return $weatherData;
    }

    /**
     * Return the array of regexps used to parse METAR text. We don't define
     * this in the declaration since we unset the entries as they are parsed.
     *
     * @return array
     */
    protected function _getMetarCodes()
    {
        return array(
            'report'      => 'METAR|SPECI',
            'station'     => '\w{4}',
            'update'      => '(\d{2})?(\d{4})Z',
            'type'        => 'AUTO|COR',
            'wind'        => '(\d{3}|VAR|VRB)(\d{2,3})(G(\d{2,3}))?(FPS|KPH|KT|KTS|MPH|MPS)',
            'windVar'     => '(\d{3})V(\d{3})',
            'visFrac'     => '(\d{1})',
            'visibility'  => '(\d{4})|((M|P)?((\d{1,2}|((\d) )?(\d)\/(\d))(SM|KM)))|(CAVOK)',
            'runway'      => 'R(\d{2})(\w)?\/(P|M)?(\d{4})(FT)?(V(P|M)?(\d{4})(FT)?)?(\w)?',
            'condition'   => '(-|\+|VC|RE|NSW)?(MI|BC|PR|TS|BL|SH|DR|FZ)?((DZ)|(RA)|(SN)|(SG)|(IC)|(PE)|(PL)|(GR)|(GS)|(UP))*(BR|FG|FU|VA|DU|SA|HZ|PY)?(PO|SQ|FC|SS|DS)?',
            'clouds'      => '(SKC|CLR|NSC|((FEW|SCT|BKN|OVC|VV)(\d{3}|\/{3})(TCU|CB)?))',
            'temperature' => '(M)?(\d{2})\/((M)?(\d{2})|XX|\/\/)?',
            'pressure'    => '(A)(\d{4})|(Q)(\d{4})',
            'trend'       => 'NOSIG|TEMPO|BECMG',
            'remark'      => 'RMK'
        );
    }

    /**
     * Return the array of regexps used to parse METAR remarks section.
     *
     * @return array
     */
    protected function _getRemarks()
    {
        return array(
            'nospeci'     => 'NOSPECI',
            'autostation' => 'AO(1|2)',
            'presschg'    => 'PRES(R|F)R',
            'seapressure' => 'SLP(\d{3}|NO)',
            'precip'      => '(P|6|7)(\d{4}|\/{4})',
            'snowdepth'   => '4\/(\d{3})',
            'snowequiv'   => '933(\d{3})',
            'cloudtypes'  => '8\/(\d|\/)(\d|\/)(\d|\/)',
            'sunduration' => '98(\d{3})',
            '1htempdew'   => 'T(0|1)(\d{3})((0|1)(\d{3}))?',
            '6hmaxtemp'   => '1(0|1)(\d{3})',
            '6hmintemp'   => '2(0|1)(\d{3})',
            '24htemp'     => '4(0|1)(\d{3})(0|1)(\d{3})',
            '3hpresstend' => '5([0-8])(\d{3})',
            'sensors'     => 'RVRNO|PWINO|PNO|FZRANO|TSNO|VISNO|CHINO',
            'maintain'    => '[\$]'
        );
    }

}