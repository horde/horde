<?php
/**
 * Base class for Horde_Image_Exif drivers.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Image
 */
abstract class Horde_Image_Exif_Base
{
    /**
     * Instance parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Optional Logger
     */
    protected $_logger;

    /**
     *
     * @param $params
     */
    public function __construct($params = array())
    {
        if (!empty($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }
        $this->_params = $params;
    }

    /**
     *
     * @param $exif
     * @return unknown_type
     */
    protected function _processData($exif)
    {
        if (!$exif) {
            return array();
        }

        $results = array();
        $fields = Horde_Image_Exif::getFields($this);
        foreach ($fields as $field => $data) {
            $value = isset($exif[$field]) ? $exif[$field] : '';
            // Don't store empty fields.
            if ($value === '') {
                continue;
            }

            /* Special handling of GPS data */
            if ($data['type'] == 'gps') {
                $value = $this->_parseGPSData($exif[$field]);
                if (!empty($exif[$field . 'Ref']) &&
                    in_array($exif[$field . 'Ref'], array('S', 'South', 'W', 'West'))) {
                    $value = '-' . abs($value);
                }
            }

            /* Date fields are converted to a timestamp.*/
            if ($data['type'] == 'date') {
                @list($ymd, $hms) = explode(' ', $value, 2);
                @list($year, $month, $day) = explode(':', $ymd, 3);
                if (!empty($hms) && !empty($year) && !empty($month) && !empty($day)) {
                    $time = "$month/$day/$year $hms";
                    $value = strtotime($time);
                }
            }

            if ($data['type'] == 'array') {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
            }

            $results[$field] = $value;
        }

        return $results;
    }

    /**
     * Parse the Longitude and Latitude values into a standardized format
     * regardless of the source format.
     *
     * @param mixed $data  An array containing degrees, minutes, seconds
     *                     in index 0, 1, 2 respectifully.
     *
     * @return double  The location data in a decimal format.
     */
    protected function _parseGPSData($data)
    {
        // According to EXIF standard, GPS data can be in the form of
        // dd/1 mm/1 ss/1 or as a decimal reprentation.
        if (!is_array($data)) {
            // Assume a scalar is a decimal representation. Cast it to a float
            // which will get rid of any stray ordinal indicators. (N, S,
            // etc...)
            return (double)$data;
        }

        if ($data[0] == 0) {
            return 0;
        }
        $min = explode('/', $data[1]);
        if (count($min) > 1) {
            $min = $min[0] / $min[1];
        } else {
            $min = $min[0];
        }

        $sec = explode('/', $data[2]);
        if (count($sec) > 1) {
            $sec = $sec[0] / $sec[1];
        } else {
            $sec = $sec[0];
        }

        return self::_degToDecimal($data[0], $min, $sec);
    }

    /**
     *
     * @param $degrees
     * @param $minutes
     * @param $seconds
     * @return unknown_type
     */
    protected function _degToDecimal($degrees, $minutes, $seconds)
    {
        $degs = (double)($degrees + ($minutes / 60) + ($seconds / 3600));
        return round($degs, 6);
    }

    protected function _logDebug($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->debug($message);
        }
    }

    protected function _logErr($message)
    {
        if (!empty($this->_logger)) {
            $this->_logger->err($message);
        }
    }

    abstract public function getData($image);

    abstract public function supportedCategories();
}