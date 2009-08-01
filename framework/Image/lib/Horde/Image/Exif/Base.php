<?php
/**
 *
 */
abstract class Horde_Image_Exif_Base
{
    abstract public function getData($image);

    /**
     *
     * @return unknown_type
     */
    static public function getFields()
    {
        return array('Make' => array('description' => _("Camera Make"), 'type' => 'text'),
                     'Model' => array('description' => _("Camera Model"), 'type' => 'text'),
                     'ImageType' => array('description' => _("Photo Type"), 'type' => 'text'),
                     'ImageDescription' => array('description' => _("Photo Description"), 'type' => 'text'),
                     'FileSize' => array('description' => _("File Size"), 'type' => 'number'),
                     'DateTime' => array('description' => _("Date Photo Modified"), 'type' => 'date'),
                     'DateTimeOriginal' => array('description' => _("Date Photo Taken"), 'type' => 'date'),
                     'DateTimeDigitized' => array('description' => _("Date Photo Digitized"), 'type' => 'date'),
                     'ExifImageWidth' => array('description' => _("Width"), 'type' => 'number'),
                     'ExifImageLength' => array('description' => _("Height"), 'type' => 'number'),
                     'XResolution' => array('description' => _("X Resolution"), 'type' => 'number'),
                     'YResolution' => array('description' => _("Y Resolution"), 'type' => 'number'),
                     'ResolutionUnit' => array('description' => _("Resolution Unit"), 'type' => 'text'),
                     'ShutterSpeedValue' => array('description' => _("Shutter Speed"), 'type' => 'number'),
                     'ExposureTime' => array('description' => _("Exposure"), 'type' => 'number'),
                     'FocalLength' => array('description' => _("Focal Length"), 'type' => 'number'),
                     'FocalLengthIn35mmFilm' => array('description' => _("Focal Length (35mm equiv)"), 'type' => 'number'),
                     'ApertureValue' => array('description' => _("Aperture"), 'type' => 'number'),
                     'FNumber' => array('description' => _("F-Number"), 'type' => 'number'),
                     'ISOSpeedRatings' => array('description' => _("ISO Setting"), 'type' => 'number'),
                     'ExposureBiasValue' => array('description' => _("Exposure Bias"), 'type' => 'number'),
                     'ExposureMode' => array('description' => _("Exposure Mode"), 'type' => 'number'),
                     'ExposureProgram' => array('description' => _("Exposure Program"), 'type' => 'number'),
                     'MeteringMode' => array('description' => _("Metering Mode"), 'type' => 'number'),
                     'Flash' => array('description' => _("Flash Setting"), 'type' => 'number'),
                     'UserComment' => array('description' => _("User Comment"), 'type' => 'text'),
                     'ColorSpace' => array('description' => _("Color Space"), 'type' => 'number'),
                     'SensingMethod' => array('description' => _("Sensing Method"), 'type' => 'number'),
                     'WhiteBalance' => array('description' => _("White Balance"), 'type' => 'number'),
                     'Orientation' => array('description' => _("Camera Orientation"), 'type' => 'number'),
                     'Copyright' => array('description' => _("Copyright"), 'type' => 'text'),
                     'Artist' => array('description' => _("Artist"), 'type' => 'text'),
                     'GPSLatitude' => array('description' => _("Latitude"), 'type' => 'gps'),
                     'GPSLongitude' => array('description' => _("Longitude"), 'type' => 'gps'),
                     'LightSource' => array('description' => _("Light source"), 'type' => 'number'),
                     'FileSource' => array('description' => _("File source"), 'type' => 'number'),
        );
    }

    /**
     *
     * @param $exif
     * @return unknown_type
     */
    protected function _processData($exif)
    {
        $results = array();
        if ($exif) {
            $fields = self::getFields();

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