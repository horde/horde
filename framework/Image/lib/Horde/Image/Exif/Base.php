<?php
/**
 *
 */
abstract class Horde_Image_Exif_Base
{
    abstract public function getData($image);

    /**
     *
     * @param $exif
     * @return unknown_type
     */
    protected function _processData($exif)
    {
        $results = array();
        if ($exif) {
            $fields = Horde_Image_Exif::getFields();

            foreach ($fields as $field => $data) {
                $value = isset($exif[$field]) ? $exif[$field] : '';
                // Don't store empty fields.
                if ($value === '') {
                    continue;
                }

                /* Special handling of GPS data */
                if ($data['type'] == 'gps') {
                    $value = self::_parseGPSData($exif[$field]);
                    if (!empty($exif[$field . 'Ref']) && ($exif[$field . 'Ref'] == 'S' || $exif[$field . 'Ref'] == 'W')) {
                        $value = '-' . $value;
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

                $results[$field] = $value;
            }
        }
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
        $degs = (double)($degrees + ($minutes / 60) + ($seconds/3600));
        return round($degs, 6);
    }

}