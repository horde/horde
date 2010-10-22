<?php
/**
 * General class for fetching and parsing EXIF information from images.
 *
 * Works equally well with either the built in php exif functions (if PHP
 * compiled with exif support), the Exiftool package (more complete but slower),
 * or the bundled exif library.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package Horde_Image
 */
class Horde_Image_Exif
{
    /**
     * Factory method for instantiating a Horde_Image_Exif object.
     *
     * @param string $driver
     * @param array $params
     *
     * @return Horde_Image_Exif
     */
    static public function factory($driver = null, $params = array())
    {
        if (empty($driver) && function_exists('exif_read_data')) {
            $driver = 'Php';
        } elseif (empty($driver)) {
            $driver = 'Bundled';
        } else {
            $driver = basename($driver);
        }

        $class = 'Horde_Image_Exif_' . $driver;

        return new $class($params);
    }

    /**
     * Converts from Intel to Motorola endien.  Just reverses the bytes
     * (assumes hex is passed in)
     *
     * @param $intel
     *
     * @return
     */
    static public function intel2Moto($intel)
    {
        $len  = strlen($intel);
        $moto = '';
        for($i = 0; $i <= $len; $i += 2) {
            $moto .= substr($intel, $len-$i, 2);
        }

        return $moto;
    }

    /**
     * Obtain an array of supported meta data fields.
     *
     * @TODO: This should probably be extended by the subclass?
     *
     * @return array
     */
    static public function getCategories()
    {
        return array(
            'IPTC' => array(
                'Keywords' => array('description' => Horde_Image_Translation::t("Image keywords"), 'type' => 'array'),
                'ObjectName' => array('description' => Horde_Image_Translation::t("Image Title"), 'type' => 'text'),
                'By-line' => array('description' => Horde_Image_Translation::t("By"), 'type' => 'text'),
                'CopyrightNotice' => array('description' => Horde_Image_Translation::t("Copyright"), 'type' => 'text'),
                'Caption-Abstract' => array('description' => Horde_Image_Translation::t("Caption"), 'type' => 'text'),
            ),

            'XMP' => array(
                'Creator' => array('description' => Horde_Image_Translation::t("Image Creator"), 'type' => 'text'),
                'Rights' => array('description' => Horde_Image_Translation::t("Rights"), 'type' => 'text'),
                'UsageTerms' => array('description' => Horde_Image_Translation::t("Usage Terms"), 'type' => 'type'),
            ),

            'EXIF' => array(
                'DateTime' => array('description' => Horde_Image_Translation::t("Date Photo Modified"), 'type' => 'date'),
                'DateTimeOriginal' => array('description' => Horde_Image_Translation::t("Date Photo Taken"), 'type' => 'date'),
                'DateTimeDigitized' => array('description' => Horde_Image_Translation::t("Date Photo Digitized"), 'type' => 'date'),
                'GPSLatitude' => array('description' => Horde_Image_Translation::t("Latitude"), 'type' => 'gps'),
                'GPSLongitude' => array('description' => Horde_Image_Translation::t("Longitude"), 'type' => 'gps'),
                'Make' => array('description' => Horde_Image_Translation::t("Camera Make"), 'type' => 'text'),
                'Model' => array('description' => Horde_Image_Translation::t("Camera Model"), 'type' => 'text'),
                'Software' => array('description' => Horde_Image_Translation::t("Software Version"), 'type' => 'text'),
                'ImageType' => array('description' => Horde_Image_Translation::t("Photo Type"), 'type' => 'text'),
                'ImageDescription' => array('description' => Horde_Image_Translation::t("Photo Description"), 'type' => 'text'),
                'FileSize' => array('description' => Horde_Image_Translation::t("File Size"), 'type' => 'number'),
                'ExifImageWidth' => array('description' => Horde_Image_Translation::t("Width"), 'type' => 'number'),
                'ExifImageLength' => array('description' => Horde_Image_Translation::t("Height"), 'type' => 'number'),
                'XResolution' => array('description' => Horde_Image_Translation::t("X Resolution"), 'type' => 'number'),
                'YResolution' => array('description' => Horde_Image_Translation::t("Y Resolution"), 'type' => 'number'),
                'ResolutionUnit' => array('description' => Horde_Image_Translation::t("Resolution Unit"), 'type' => 'text'),
                'ShutterSpeedValue' => array('description' => Horde_Image_Translation::t("Shutter Speed"), 'type' => 'number'),
                'ExposureTime' => array('description' => Horde_Image_Translation::t("Exposure"), 'type' => 'number'),
                'FocalLength' => array('description' => Horde_Image_Translation::t("Focal Length"), 'type' => 'number'),
                'FocalLengthIn35mmFilm' => array('description' => Horde_Image_Translation::t("Focal Length (35mm equiv)"), 'type' => 'number'),
                'ApertureValue' => array('description' => Horde_Image_Translation::t("Aperture"), 'type' => 'number'),
                'FNumber' => array('description' => Horde_Image_Translation::t("F-Number"), 'type' => 'number'),
                'ISOSpeedRatings' => array('description' => Horde_Image_Translation::t("ISO Setting"), 'type' => 'number'),
                'ExposureBiasValue' => array('description' => Horde_Image_Translation::t("Exposure Bias"), 'type' => 'number'),
                'ExposureMode' => array('description' => Horde_Image_Translation::t("Exposure Mode"), 'type' => 'number'),
                'ExposureProgram' => array('description' => Horde_Image_Translation::t("Exposure Program"), 'type' => 'number'),
                'MeteringMode' => array('description' => Horde_Image_Translation::t("Metering Mode"), 'type' => 'number'),
                'Flash' => array('description' => Horde_Image_Translation::t("Flash Setting"), 'type' => 'number'),
                'UserComment' => array('description' => Horde_Image_Translation::t("User Comment"), 'type' => 'text'),
                'ColorSpace' => array('description' => Horde_Image_Translation::t("Color Space"), 'type' => 'number'),
                'SensingMethod' => array('description' => Horde_Image_Translation::t("Sensing Method"), 'type' => 'number'),
                'WhiteBalance' => array('description' => Horde_Image_Translation::t("White Balance"), 'type' => 'number'),
                'Orientation' => array('description' => Horde_Image_Translation::t("Camera Orientation"), 'type' => 'number'),
                'Copyright' => array('description' => Horde_Image_Translation::t("Copyright"), 'type' => 'text'),
                'Artist' => array('description' => Horde_Image_Translation::t("Artist"), 'type' => 'text'),
                'LightSource' => array('description' => Horde_Image_Translation::t("Light source"), 'type' => 'number'),
                'ImageStabalization' => array('description' => Horde_Image_Translation::t("Image Stabilization"), 'type' => 'text'),
                'SceneCaptureType' => array('description' => Horde_Image_Translation::t("Scene Type"), 'type' => 'number'),

            ),

            'COMPOSITE' => array(
                'LensID' => array('description' => Horde_Image_Translation::t("Lens"), 'type' => 'text'),
                'Aperture' => array('description' => Horde_Image_Translation::t("Aperture"), 'type' => 'text'),
                'DOF' => array('description' => Horde_Image_Translation::t("Depth of Field"), 'type' => 'text'),
                'FOV' => array('description' => Horde_Image_Translation::t("Field of View"), 'type' => 'text')
            )
        );
    }

    /**
     * Return a flattened array of supported metadata fields.
     *
     * @param $driver
     * @return unknown_type
     */
    static public function getFields($driver = null, $description_only = false)
    {
        if (!is_null($driver) && is_array($driver)) {
            $driver = self::factory($driver[0], $driver[1]);
        }

        if ($driver instanceof Horde_Image_Exif_Base) {
            $supported = $driver->supportedCategories();
        } else {
            $supported = array('XMP', 'IPTC', 'EXIF'    );
        }
        $categories = self::getCategories();
        $flattened = array();
        foreach ($supported as $category) {
            $flattened = array_merge($flattened, $categories[$category]);
        }

        if ($description_only) {
            foreach ($flattened as $key => $data) {
                $return[$key] = $data['description'];
            }
            return $return;
        }

        return $flattened;
    }

    /**
     * More human friendly exposure formatting.
     */
    static protected function _formatExposure($data)
    {
        if ($data > 0) {
            if ($data > 1) {
                return sprintf(Horde_Image_Translation::t("%d sec"), round($data, 2));
            } else {
                $n = $d = 0;
                self::_convertToFraction($data, $n, $d);
                if ($n <> 1) {
                    return sprintf(Horde_Image_Translation::t("%4f sec"), $n / $d);
                }
                return sprintf(Horde_Image_Translation::t("%s / %s sec"), $n, $d);
            }
        } else {
            return Horde_Image_Translation::t("Bulb");
        }
    }

    /**
     * Converts a floating point number into a fraction.
     * Many thanks to Matthieu Froment for this code.
     *
     * (Ported from the Exifer library).
     */
    static protected function _convertToFraction($v, &$n, &$d)
    {
        $MaxTerms = 15;         // Limit to prevent infinite loop
        $MinDivisor = 0.000001; // Limit to prevent divide by zero
        $MaxError = 0.00000001; // How close is enough

        // Initialize fraction being converted
        $f = $v;

        // Initialize fractions with 1/0, 0/1
        $n_un = 1;
        $d_un = 0;
        $n_deux = 0;
        $d_deux = 1;

        for ($i = 0; $i < $MaxTerms; $i++) {
            $a = floor($f); // Get next term
            $f = $f - $a; // Get new divisor
            $n = $n_un * $a + $n_deux; // Calculate new fraction
            $d = $d_un * $a + $d_deux;
            $n_deux = $n_un; // Save last two fractions
            $d_deux = $d_un;
            $n_un = $n;
            $d_un = $d;

            // Quit if dividing by zero
            if ($f < $MinDivisor) {
                break;
            }
            if (abs($v - $n / $d) < $MaxError) {
                break;
            }

            // reciprocal
            $f = 1 / $f;
        }
    }

    /**
     * Convert an exif field into human-readable form.
     * Some of these cases are ported from the Exifer library, others were
     * changed from their implementation where the EXIF standard dictated
     * different behaviour.
     *
     * @param string $field  The name of the field to translate.
     * @param string $data   The data value to translate.
     *
     * @return string  The converted data.
     */
    static public function getHumanReadable($field, $data)
    {
        switch ($field) {
        case 'ExposureMode':
            switch ($data) {
            case 0: return Horde_Image_Translation::t("Auto exposure");
            case 1: return Horde_Image_Translation::t("Manual exposure");
            case 2: return Horde_Image_Translation::t("Auto bracket");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'ExposureProgram':
            switch ($data) {
            case 1: return Horde_Image_Translation::t("Manual");
            case 2: return Horde_Image_Translation::t("Normal Program");
            case 3: return Horde_Image_Translation::t("Aperture Priority");
            case 4: return Horde_Image_Translation::t("Shutter Priority");
            case 5: return Horde_Image_Translation::t("Creative");
            case 6: return Horde_Image_Translation::t("Action");
            case 7: return Horde_Image_Translation::t("Portrait");
            case 8: return Horde_Image_Translation::t("Landscape");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'XResolution':
        case 'YResolution':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                return sprintf(Horde_Image_Translation::t("%d dots per unit"), $n);
            }
            return sprintf(Horde_Image_Translation::t("%d per unit"), $data);

        case 'ResolutionUnit':
            switch ($data) {
            case 1: return Horde_Image_Translation::t("Pixels");
            case 2: return Horde_Image_Translation::t("Inch");
            case 3: return Horde_Image_Translation::t("Centimeter");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'ExifImageWidth':
        case 'ExifImageLength':
            return sprintf(Horde_Image_Translation::t("%d pixels"), $data);

        case 'Orientation':
            switch ($data) {
            case 1:
                return sprintf(Horde_Image_Translation::t("Normal (O deg)"));
            case 2:
                return sprintf(Horde_Image_Translation::t("Mirrored"));
            case 3:
                return sprintf(Horde_Image_Translation::t("Upsidedown"));
            case 4:
                return sprintf(Horde_Image_Translation::t("Upsidedown Mirrored"));
            case 5:
                return sprintf(Horde_Image_Translation::t("90 deg CW Mirrored"));
            case 6:
                return sprintf(Horde_Image_Translation::t("90 deg CCW"));
            case 7:
                return sprintf(Horde_Image_Translation::t("90 deg CCW Mirrored"));
            case 8:
                return sprintf(Horde_Image_Translation::t("90 deg CW"));
            }
            break;

        case 'ExposureTime':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d == 0) {
                    return;
                }
                $data = $n / $d;
            }
            return self::_formatExposure($data);

        case 'ShutterSpeedValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d == 0) {
                    return;
                }
                $data = $n / $d;
            }
            $data = exp($data * log(2));
            if ($data > 0) {
                $data = 1 / $data;
            }
            return self::_formatExposure($data);

        case 'ApertureValue':
        case 'MaxApertureValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d == 0) {
                    return;
                }
                $data = $n / $d;
                $data = exp(($data * log(2)) / 2);

                // Precision is 1 digit.
                $data = round($data, 1);
            }
            return 'f/' . $data;

        case 'FocalLength':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d == 0) {
                    return;
                }
                return sprintf(Horde_Image_Translation::t("%d mm"), round($n / $d));
            }
            return sprintf(Horde_Image_Translation::t("%d mm"), $data);

        case 'FNumber':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($d != 0) {
                    return 'f/' . round($n / $d, 1);
                }
            }
            return 'f/' . $data;

        case 'ExposureBiasValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                if ($n == 0) {
                    return '0 EV';
                }
            }
            return $data . ' EV';

        case 'MeteringMode':
            switch ($data) {
            case 0: return Horde_Image_Translation::t("Unknown");
            case 1: return Horde_Image_Translation::t("Average");
            case 2: return Horde_Image_Translation::t("Center Weighted Average");
            case 3: return Horde_Image_Translation::t("Spot");
            case 4: return Horde_Image_Translation::t("Multi-Spot");
            case 5: return Horde_Image_Translation::t("Multi-Segment");
            case 6: return Horde_Image_Translation::t("Partial");
            case 255: return Horde_Image_Translation::t("Other");
            default: return sprintf(Horde_Image_Translation::t("Unknown: %s"), $data);
            }
            break;

        case 'LightSource':
            switch ($data) {;
            case 1: return Horde_Image_Translation::t("Daylight");
            case 2: return Horde_Image_Translation::t("Fluorescent");
            case 3: return Horde_Image_Translation::t("Tungsten");
            case 4: return Horde_Image_Translation::t("Flash");
            case 9: return Horde_Image_Translation::t("Fine weather");
            case 10: return Horde_Image_Translation::t("Cloudy weather");
            case 11: return Horde_Image_Translation::t("Shade");
            case 12: return Horde_Image_Translation::t("Daylight fluorescent");
            case 13: return Horde_Image_Translation::t("Day white fluorescent");
            case 14: return Horde_Image_Translation::t("Cool white fluorescent");
            case 15: return Horde_Image_Translation::t("White fluorescent");
            case 17: return Horde_Image_Translation::t("Standard light A");
            case 18: return Horde_Image_Translation::t("Standard light B");
            case 19: return Horde_Image_Translation::t("Standard light C");
            case 20: return 'D55';
            case 21: return 'D65';
            case 22: return 'D75';
            case 23: return 'D50';
            case 24: return Horde_Image_Translation::t("ISO studio tungsten");
            case 255: return Horde_Image_Translation::t("other light source");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'WhiteBalance':
            switch ($data) {
            case 0: return Horde_Image_Translation::t("Auto");
            case 1: return Horde_Image_Translation::t("Manual");
            default: Horde_Image_Translation::t("Unknown");
            }
            break;

        case 'FocalLengthIn35mmFilm':
            return $data . ' mm';

        case 'Flash':
            switch ($data) {
            case 0: return Horde_Image_Translation::t("No Flash");
            case 1: return Horde_Image_Translation::t("Flash");
            case 5: return Horde_Image_Translation::t("Flash, strobe return light not detected");
            case 7: return Horde_Image_Translation::t("Flash, strobe return light detected");
            case 9: return Horde_Image_Translation::t("Compulsory Flash");
            case 13: return Horde_Image_Translation::t("Compulsory Flash, Return light not detected");
            case 15: return Horde_Image_Translation::t("Compulsory Flash, Return light detected");
            case 16: return Horde_Image_Translation::t("No Flash");
            case 24: return Horde_Image_Translation::t("No Flash");
            case 25: return Horde_Image_Translation::t("Flash, Auto-Mode");
            case 29: return Horde_Image_Translation::t("Flash, Auto-Mode, Return light not detected");
            case 31: return Horde_Image_Translation::t("Flash, Auto-Mode, Return light detected");
            case 32: return Horde_Image_Translation::t("No Flash");
            case 65: return Horde_Image_Translation::t("Red Eye");
            case 69: return Horde_Image_Translation::t("Red Eye, Return light not detected");
            case 71: return Horde_Image_Translation::t("Red Eye, Return light detected");
            case 73: return Horde_Image_Translation::t("Red Eye, Compulsory Flash");
            case 77: return Horde_Image_Translation::t("Red Eye, Compulsory Flash, Return light not detected");
            case 79: return Horde_Image_Translation::t("Red Eye, Compulsory Flash, Return light detected");
            case 89: return Horde_Image_Translation::t("Red Eye, Auto-Mode");
            case 93: return Horde_Image_Translation::t("Red Eye, Auto-Mode, Return light not detected");
            case 95: return Horde_Image_Translation::t("Red Eye, Auto-Mode, Return light detected");
            }
            break;

        case 'FileSize':
           if ($data <= 0) {
              return '0 Bytes';
           }
           $s = array('B', 'kB', 'MB', 'GB');
           $e = floor(log($data, 1024));
           return round($data/pow(1024, $e), 2) . ' ' . $s[$e];

        case 'SensingMethod':
            switch ($data) {
            case 1: return Horde_Image_Translation::t("Not defined");
            case 2: return Horde_Image_Translation::t("One Chip Color Area Sensor");
            case 3: return Horde_Image_Translation::t("Two Chip Color Area Sensor");
            case 4: return Horde_Image_Translation::t("Three Chip Color Area Sensor");
            case 5: return Horde_Image_Translation::t("Color Sequential Area Sensor");
            case 7: return Horde_Image_Translation::t("Trilinear Sensor");
            case 8: return Horde_Image_Translation::t("Color Sequential Linear Sensor");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'ColorSpace':
            switch ($data) {
            case 1: return Horde_Image_Translation::t("sRGB");
            default: return Horde_Image_Translation::t("Uncalibrated");
            }

        case 'SceneCaptureType':
            switch ($data) {
            case 0: return Horde_Image_Translation::t("Standard");
            case 1: return Horde_Image_Translation::t("Landscape");
            case 2: return Horde_Image_Translation::t("Portrait");
            case 3: return Horde_Image_Translation::t("Night Scene");
            default: return Horde_Image_Translation::t("Unknown");
            }

        case 'DateTime':
        case 'DateTimeOriginal':
        case 'DateTimeDigitized':
            return date('m/d/Y H:i:s O', $data);

        case 'UserComment':
            //@TODO: the first 8 bytes of this field contain the charset used
            //       to encode the comment. Either ASCII, JIS, UNICODE, or
            //       UNDEFINED. Should probably either convert to a known charset
            //       here and let the calling code deal with it, or allow this
            //       method to take an optional charset to convert to (would
            //       introduce a dependency on Horde_String to do the conversion).
            $data = trim(substr($data, 7))  ;


        default:
            return !empty($data) ? $data : '---';
        }
    }

}
