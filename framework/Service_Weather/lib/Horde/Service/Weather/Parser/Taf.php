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
 * Horde_Service_Weather_Parser_Taf
 *
 * Responsible for parsing encoded TAF data.
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
class Horde_Service_Weather_Parser_Taf extends Horde_Service_Weather_Parser_Base
{
    /**
     * Parses TAF data.
     *
     * TAF KLGA 271734Z 271818 11007KT P6SM -RA SCT020 BKN200
     *     FM2300 14007KT P6SM SCT030 BKN150
     *     FM0400 VRB03KT P6SM SCT035 OVC080 PROB30 0509 P6SM -RA BKN035
     *     FM0900 VRB03KT 6SM -RA BR SCT015 OVC035
     *         TEMPO 1215 5SM -RA BR SCT009 BKN015
     *         BECMG 1517 16007KT P6SM NSW SCT015 BKN070
     *
     * @param array $data  The TAF encoded weather data, spilt on line endings.
     *
     * @return  array  An array of forecast data. Keys include:
     *    - station: (string) The station identifier.
     *    - dataRaw: (string) The raw TAF data.
     *    - update:  (timestamp) Timestamp of last update.
     *    - validFrom: (Horde_Date) The valid FROM time.
     *    - validTo: (Horde_Date) The valid TO time.
     *    - time: (array) An array of Horde_Service_Weather_Period objects for
     *      each available valid time provided by the TAF report.
     */
    protected function _parse(array $data)
    {
        $tafCode = $this->_getTafCodes();

        // Eliminate trailing information
        for ($i = 0; $i < sizeof($data); $i++) {
            if (strpos($data[$i], '=') !== false) {
                $data[$i] = substr($data[$i], 0, strpos($data[$i], '='));
                $data = array_slice($data, 0, $i + 1);
                break;
            }
        }

        // Ok, we have correct data, start with parsing the first line for the last update
        $forecastData = array();
        $forecastData['station'] = '';
        $forecastData['dataRaw'] = implode(' ', $data);
        $forecastData['update'] = strtotime(trim($data[0]) . ' GMT');
        $forecastData['updateRaw'] = trim($data[0]);

        // and prepare the rest for stepping through
        array_shift($data);
        $taf = explode(' ', preg_replace('/\s{2,}/', ' ', implode(' ', $data)));

        // The timeperiod the data gets added to
        $fromTime = '';

        // If we have FMCs (Forecast Meteorological Conditions), we need this
        $fmcCount = 0;

        // Pointer to the array we add the data to
        $pointer = &$forecastData;
        for ($i = 0; $i < sizeof($taf); $i++) {
            $taf[$i] = trim($taf[$i]);
            if (!strlen($taf[$i])) {
                continue;
            }

            // Init
            $result   = array();
            $resultVF = array();
            $lresult  = array();
            $found = false;

            foreach ($tafCode as $key => $regexp) {
                // Check if current code matches current taf snippet
                if (($found = preg_match('/^' . $regexp . '$/i', $taf[$i], $result)) == true) {
                    $insert = array();
                    switch ($key) {
                    case 'station':
                        $pointer['station'] = $result[0];
                        unset($tafCode['station']);
                        break;
                    case 'valid':
                        $pointer['validRaw'] = $result[0];
                        // Generates the timeperiod the report is valid for
                        list($year, $month, $day) = explode('-', gmdate('Y-m-d', $forecastData['update']));
                        // Date is in next month
                        if ($result[1] < $day) {
                            $month++;
                        }
                        $pointer['validFrom'] = new Horde_Date(array(
                            'hour' => $result[2],
                            'month' => $month,
                            'mday' => $result[1],
                            'year' => $year), 'GMT');
                        $pointer['validTo'] = new Horde_Date(array(
                            'hour' => $result[4],
                            'month' => $month,
                            'mday' => $result[3],
                            'year' => $year), 'GMT');
                        unset($tafCode['valid']);
                        // Now the groups will start, so initialize the time groups
                        $pointer['time'] = array();
                        $start_time = new Horde_Date(array(
                            'year' => $year,
                            'month' => $month,
                            'mday' => $result[1],
                            'hour' => $result[2]), 'UTC');
                        $fromTime = (string)$start_time;
                        $pointer['time'][$fromTime] = array();
                        // Set pointer to the first timeperiod
                        $pointer = &$pointer['time'][$fromTime];
                        break;
                    case 'wind':
                        if ($result[5] == 'KTS') {
                            $result[5] = 'KT';
                        }
                        $pointer['wind'] = round(Horde_Service_Weather::convertSpeed(
                            $result[2],
                            $result[5],
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        ));
                        if ($result[1] == 'VAR' || $result[1] == 'VRB') {
                            $pointer['windDegrees'] = Horde_Service_Weather_Translation::t('Variable');
                            $pointer['windDirection'] = Horde_Service_Weather_Translation::t('Variable');
                        } else {
                            $pointer['windDegrees'] = $result[1];
                            $pointer['windDirection'] = Horde_Service_Weather::degToDirection($result[1]);
                        }
                        if (is_numeric($result[4])) {
                            $pointer['windGust'] = round(Horde_Service_Weather::convertSpeed(
                                $result[4],
                                $result[5],
                                $this->_unitMap[self::UNIT_KEY_SPEED]
                            ));
                        }
                        if (isset($probability)) {
                            $pointer['windProb'] = $probability;
                            unset($probability);
                        }
                        unset($tafCode['wind']);
                        break;
                    case 'visFrac':
                        // Possible fractional visibility here.
                        // Check if it matches with the next TAF piece for visibility
                        if (!isset($taf[$i + 1]) ||
                            !preg_match('/^' . $tafCode['visibility'] . '$/i', $result[1] . ' ' . $taf[$i + 1], $resultVF)) {
                            // No next TAF piece available or not matching.
                            $found = false;
                            break;
                        }
                        // Match. Hand over result and advance TAF
                        $key = 'visibility';
                        $result = $resultVF;
                        $i++;

                        // Fall through
                    case 'visibility':
                        $pointer['visQualifier'] = Horde_Service_Weather_Translation::t('AT');
                        if (is_numeric($result[1]) && ($result[1] == 9999)) {
                            // Upper limit of visibility range
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
                            $pointer['condition'] = Horde_Service_Weather_Translation::t('No significant weather');
                        }
                        if (isset($probability)) {
                            $pointer['visProb'] = $probability;
                            unset($probability);
                        }
                        $pointer['visibility'] = $visibility;
                        break;
                    case 'condition':
                        // First some basic setups
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
                        if (isset($probability)) {
                            $pointer['condition'] .= ' (' . $probability
                                . '% '
                                . Horde_Service_Weather_Translation::t('probability')
                                . ').';
                            unset($probability);
                        }
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
                        if (isset($probability)) {
                            $cloud['prob'] = $probability;
                            unset($probability);
                        }
                        $pointer['clouds'][] = $cloud;
                        break;
                    case 'windshear':
                        // Parse windshear, if available
                        if ($result[4] == 'KTS') {
                            $result[4] = 'KT';
                        }
                        $pointer['windshear'] = round(Horde_Service_Weather::convertSpeed(
                            $result[3],
                            $result[4],
                            $this->_unitMap[self::UNIT_KEY_SPEED]
                        ));
                        $pointer['windshearHeight'] = $result[1] * 100;
                        $pointer['windshearDegrees'] = $result[2];
                        $pointer['windshearDirection'] = Horde_Service_Weather::degToDirection($result[2]);
                        break;
                    case 'tempmax':
                        $forecastData['temperatureHigh'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        break;
                    case 'tempmin':
                        // Parse max/min temperature
                        $forecastData['temperatureLow'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        break;
                    case 'tempmaxmin':
                        $forecastData['temperatureHigh'] = Horde_Service_Weather::convertTemperature(
                            $result[1],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        $forecastData['temperatureLow'] = Horde_Service_Weather::convertTemperature(
                            $result[4],
                            'c',
                            $this->_unitMap[self::UNIT_KEY_TEMP]
                        );
                        break;
                    case 'from':
                        // Next timeperiod is coming up, prepare array and
                        // set pointer accordingly
                        $fromTime = clone $start_time;
                        if (sizeof($result) > 2) {
                            // The ICAO way
                            $fromTime->hour = $result[2];
                            $fromTime->min = $result[3];
                        } else {
                            // The Australian way (Hey mates!)
                            $fromTime->hour = $result[1];
                        }
                        if ($start_time->compareDateTime($fromTime) >= 1) {
                            $fromTime->mday++;
                        }
                        $fromTime = (string)$fromTime;
                        $forecastData['time'][$fromTime] = array();
                        $fmcCount = 0;
                        $pointer = &$forecastData['time'][$fromTime];
                        break;
                    case 'fmc';
                        // Test, if this is a probability for the next FMC
                        if (isset($result[2]) && preg_match('/^BECMG|TEMPO$/i', $taf[$i + 1], $lresult)) {
                            // Set type to BECMG or TEMPO
                            $type = $lresult[0];
                            // Set probability
                            $probability = $result[2];
                            // Now extract time for this group
                            if (preg_match('/^(\d{2})(\d{2})$/i', $taf[$i + 2], $lresult)) {
                                $from = clone($start_time);
                                $from->hour = $lresult[1];
                                if ($start_time->compareDateTime($from) >= 1) {
                                    $from->mday++;
                                }
                                $to = clone($from);
                                $to->hour = $lresult[2];
                                if ($start_time->compareDateTime($to) >= 1) {
                                    $to->mday++;
                                }
                                // As we now have type, probability and time for this FMC
                                // from our TAF, increase field-counter
                                $i += 2;
                            } else {
                                // No timegroup present, so just increase field-counter by one
                                $i += 1;
                            }
                        } elseif (preg_match('/^(\d{2})(\d{2})\/(\d{2})(\d{2})$/i', $taf[$i + 1], $lresult)) {
                            // Normal group, set type and use extracted time
                            $type = $result[1];
                            // Check for PROBdd
                            if (isset($result[2])) {
                                $probability = $result[2];
                            }
                            $from = clone($start_time);
                            $from->hour = $lresult[2];
                            if ($start_time->compareDateTime($from) >= 1) {
                                $from->mday++;
                            }
                            $to = clone($from);
                            $to->hour = $lresult[4];
                            if ($start_time->compareDateTime($to) >= 1) {
                                $to->mday++;
                            }
                            // Same as above, we have a time for this FMC from our TAF,
                            // increase field-counter
                            $i += 1;
                        } elseif (isset($result[2])) {
                            // This is either a PROBdd or a malformed TAF with missing timegroup
                            $probability = $result[2];
                        }

                        // Handle the FMC, generate neccessary array if it's the first...
                        if (isset($type)) {
                            if (!isset($forecastData['time'][$fromTime]['fmc'])) {
                                $forecastData['time'][$fromTime]['fmc'] = array();
                            }
                            $forecastData['time'][$fromTime]['fmc'][$fmcCount] = array();
                            // ...and set pointer.
                            $pointer = &$forecastData['time'][$fromTime]['fmc'][$fmcCount];
                            $fmcCount++;
                            // Insert data
                            $pointer['type'] = $type;
                            unset($type);
                            if (isset($from)) {
                                $pointer['from'] = $from;
                                $pointer['to']   = $to;
                                unset($from, $to);
                            }
                            if (isset($probability)) {
                                $pointer['probability'] = $probability;
                                unset($probability);
                            }
                        }
                        break;
                    default:
                        // Do nothing
                        break;
                    }
                    if ($found) {
                        break;
                    }
                }
            }
        }

        return $forecastData;
    }

    /**
     * Return a fresh set of the regexps needed for parsing the TAF data.
     *
     * @return array
     */
    protected function _getTafCodes()
    {
        return array(
            'report'      => 'TAF|AMD',
            'station'     => '\w{4}',
            'update'      => '(\d{2})?(\d{4})Z',
            'valid'       => '(\d{2})(\d{2})\/(\d{2})(\d{2})',
            'wind'        => '(\d{3}|VAR|VRB)(\d{2,3})(G(\d{2,3}))?(FPS|KPH|KT|KTS|MPH|MPS)',
            'visFrac'     => '(\d{1})',
            'visibility'  => '(\d{4})|((M|P)?((\d{1,2}|((\d) )?(\d)\/(\d))(SM|KM)))|(CAVOK)',
            'condition'   => '(-|\+|VC|RE|NSW)?(MI|BC|PR|TS|BL|SH|DR|FZ)?((DZ)|(RA)|(SN)|(SG)|(IC)|(PE)|(PL)|(GR)|(GS)|(UP))*(BR|FG|FU|VA|DU|SA|HZ|PY)?(PO|SQ|FC|SS|DS)?',
            'clouds'      => '(SKC|CLR|NSC|((FEW|SCT|BKN|OVC|VV)(\d{3}|\/{3})(TCU|CB)?))',
            'windshear'   => 'WS(\d{3})\/(\d{3})(\d{2,3})(FPS|KPH|KT|KTS|MPH|MPS)',
            'tempmax'     => 'TX(\d{2})\/(\d{2})(\w)',
            'tempmin'     => 'TN(\d{2})\/(\d{2})(\w)',
            'tempmaxmin'  => 'TX(\d{2})\/(\d{2})(\w)TN(\d{2})\/(\d{2})(\w)',
            'from'        => 'FM(\d{2})(\d{2})(\d{2})?Z?',
            'fmc'         => '(PROB|BECMG|TEMPO)(\d{2})?'
        );
    }

}