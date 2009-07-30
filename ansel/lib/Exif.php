<?php
/**
 * General class for fetching and parsing EXIF information from images.
 *
 * Works equally well with either the built in php exif functions (if PHP
 * compiled with exif support) or the (slower) bundled exif library.
 *
 * $Horde: ansel/lib/Exif.php,v 1.58 2009/07/13 14:29:04 mrubinsk Exp $
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author Chuck Hagenbuch <chuck@horde.org>
 */
class Ansel_ImageData {

    /**
     * Get the image attributes from the backend.
     *
     * @param Ansel_Image $image  The image to retrieve attributes for.
     *                            attributes for.
     * @param boolean $format     Format the EXIF data. If false, the raw data
     *                            is returned.
     *
     * @return array  The EXIF data.
     * @static
     */
    function getAttributes($image, $format = false)
    {
        $attributes = $GLOBALS['ansel_db']->queryAll('SELECT attr_name, attr_value FROM ansel_image_attributes WHERE image_id = ' . (int)$image->id, null, MDB2_FETCHMODE_ASSOC, true);
        $fields = Ansel_ImageData::getFields();
        $output = array();

        foreach ($fields as $field => $data) {
            if (!isset($attributes[$field])) {
                continue;
            }
            $value = Ansel_ImageData::getHumanReadable($field, Horde_String::convertCharset($attributes[$field], $GLOBALS['conf']['sql']['charset']));
            if (!$format) {
                $output[$field] = $value;
            } else {
                $description = isset($data['description']) ? $data['description'] : $field;
                $output[] = '<td><strong>' . $description . '</strong></td><td>' . htmlspecialchars($value, ENT_COMPAT, Horde_Nls::getCharset()) . '</td>';
            }
        }

        return $output;
    }

    /**
     * Get the EXIF data from an image, process it, and return it.
     *
     * @see getFields()
     *
     * @param Ansel_Image $image  The image to read exif data from.
     * @return array Array of EXIF attributes.
     */
    function getExifData($image)
    {
        // Unfortunately, the PHP function requires a file, not a stream.
        $imageFile = $GLOBALS['ansel_vfs']->readFile($image->getVFSPath('full'),
                                                     $image->getVFSName('full'));
        if (is_a($imageFile, 'PEAR_Error')) {
            return $imageFile;
        }

        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($imageFile, 0, false);
        } else {
            $raw = read_exif_data_raw($imageFile);
            $exif = array();
            foreach ($raw as $key => $value) {
                if (($key == 'IFD0') || ($key == 'SubIFD')) {
                    foreach ($value as $subkey => $subvalue) {
                        $exif[$subkey] = $subvalue;
                    }
                } else {
                    $exif[$key] = $value;
                }
            }
            // Not really an EXIF property, but an attribute nonetheless...
            // PHP's exif functions return it, so add it here to be consistent.
            $exif['FileSize'] = @filesize($imageFile);
        }

        // See if we got any attributes back.
        $results = array();
        if ($exif) {
            $fields = Ansel_ImageData::getFields();
            foreach ($fields as $field => $data) {
                $value = isset($exif[$field]) ? $exif[$field] : '';

                // Don't store empty fields.
                if ($value === '') {
                    continue;
                }

                if ($data['type'] == 'gps') {
                    $value = Ansel_ImageData::_parseGPSData($exif[$field]); // . (!empty($exif[$field . 'Ref']) ? $exif[$field . 'Ref'] : '');
                    if (!empty($exif[$field . 'Ref']) && ($exif[$field . 'Ref'] == 'S' || $exif[$field . 'Ref'] == 'W')) {
                        $value = '-' . $value;
                    }
                }
                // If the field is a date field, convert the value to a
                // timestamp.
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
    function _parseGPSData($data)
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

        return Ansel_ImageData::_degToDecimal($data[0], $min, $sec);
    }

    function _degToDecimal($degrees, $minutes, $seconds)
    {
        $degs = (double)($degrees + ($minutes / 60) + ($seconds/3600));
        return round($degs, 6);
    }

    /**
     * More human friendly exposure formatting.
     */
    function _formatExposure($data) {
        if ($data > 0) {
            if ($data > 1) {
                return sprintf(_("%d sec"), round($data, 2));
            } else {
                $n = $d = 0;
                Ansel_ImageData::_convertToFraction($data, $n, $d);
                if ($n <> 1) {
                    return sprintf(_("%4f sec"), $n / $d);
                }
                return sprintf(_("%s / %s sec"), $n, $d);
            }
        } else {
            return _("Bulb");
        }
    }

    /**
     * Converts a floating point number into a fraction.
     * Many thanks to Matthieu Froment for this code.
     *
     * (Ported from the Exifer library).
     */
    function _convertToFraction($v, &$n, &$d)
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
    function getHumanReadable($field, $data)
    {
        switch ($field) {
        case 'ExposureMode':
            switch ($data) {
            case 0: return _("Auto exposure");
            case 1: return _("Manual exposure");
            case 2: return _("Auto bracket");
            default: return _("Unknown");
            }

        case 'ExposureProgram':
            switch ($data) {
            case 1: return _("Manual");
            case 2: return _("Normal Program");
            case 3: return _("Aperture Priority");
            case 4: return _("Shutter Priority");
            case 5: return _("Creative");
            case 6: return _("Action");
            case 7: return _("Portrait");
            case 8: return _("Landscape");
            default: return _("Unknown");
            }

        case 'XResolution':
        case 'YResolution':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                return sprintf(_("%d dots per unit"), $n);
            }
            return sprintf(_("%d per unit"), $data);

        case 'ResolutionUnit':
            switch ($data) {
            case 1: return _("Pixels");
            case 2: return _("Inch");
            case 3: return _("Centimeter");
            default: return _("Unknown");
            }

        case 'ExifImageWidth':
        case 'ExifImageLength':
            return sprintf(_("%d pixels"), $data);

        case 'Orientation':
            switch ($data) {
            case 1:
                return sprintf(_("Normal (O deg)"));
            case 2:
                return sprintf(_("Mirrored"));
            case 3:
                return sprintf(_("Upsidedown"));
            case 4:
                return sprintf(_("Upsidedown Mirrored"));
            case 5:
                return sprintf(_("90 deg CW Mirrored"));
            case 6:
                return sprintf(_("90 deg CCW"));
            case 7:
                return sprintf(_("90 deg CCW Mirrored"));
            case 8:
                return sprintf(_("90 deg CW"));
            }
            break;

        //@TODO: normalize these values...
        case 'ExposureTime':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                $data = $n / $d;
            }
            return Ansel_ImageData::_formatExposure($data);

        case 'ShutterSpeedValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                $data = $n / $d;
            }
            $data = exp($data * log(2));
            if ($data > 0) {
                $data = 1 / $data;
            }
            return Ansel_ImageData::_formatExposure($data);

        case 'ApertureValue':
        case 'MaxApertureValue':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                $data = $n / $d;
                $data = exp(($data * log(2)) / 2);

                // Precision is 1 digit.
                $data = round($data, 1);
            }
            return 'f/' . $data;

        case 'FocalLength':
            if (strpos($data, '/') !== false) {
                list($n, $d) = explode('/', $data, 2);
                return sprintf(_("%d mm"), round($n / $d));
            }
            return sprintf(_("%d mm"), $data);

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
            case 0: return _("Unknown");
            case 1: return _("Average");
            case 2: return _("Center Weighted Average");
            case 3: return _("Spot");
            case 4: return _("Multi-Spot");
            case 5: return _("Multi-Segment");
            case 6: return _("Partial");
            case 255: return _("Other");
            default: return sprintf(_("Unknown: %s"), $data);
            }
            break;

        case 'LightSource':
            switch ($data) {;
            case 1: return _("Daylight");
            case 2: return _("Fluorescent");
            case 3: return _("Tungsten");
            case 4: return _("Flash");
            case 9: return _("Fine weather");
            case 10: return _("Cloudy weather");
            case 11: return _("Shade");
            case 12: return _("Daylight fluorescent");
            case 13: return _("Day white fluorescent");
            case 14: return _("Cool white fluorescent");
            case 15: return _("White fluorescent");
            case 17: return _("Standard light A");
            case 18: return _("Standard light B");
            case 19: return _("Standard light C");
            case 20: return 'D55';
            case 21: return 'D65';
            case 22: return 'D75';
            case 23: return 'D50';
            case 24: return _("ISO studio tungsten");
            case 255: return _("other light source");
            default: return _("Unknown");
            }

        case 'WhiteBalance':
            switch ($data) {
            case 0: return _("Auto");
            case 1: return _("Manual");
            default: _("Unknown");
            }
            break;

        case 'FocalLengthIn35mmFilm':
            return $data . ' mm';

        case 'Flash':
            switch ($data) {
            case 0: return _("No Flash");
            case 1: return _("Flash");
            case 5: return _("Flash, strobe return light not detected");
            case 7: return _("Flash, strobe return light detected");
            case 9: return _("Compulsory Flash");
            case 13: return _("Compulsory Flash, Return light not detected");
            case 15: return _("Compulsory Flash, Return light detected");
            case 16: return _("No Flash");
            case 24: return _("No Flash");
            case 25: return _("Flash, Auto-Mode");
            case 29: return _("Flash, Auto-Mode, Return light not detected");
            case 31: return _("Flash, Auto-Mode, Return light detected");
            case 32: return _("No Flash");
            case 65: return _("Red Eye");
            case 69: return _("Red Eye, Return light not detected");
            case 71: return _("Red Eye, Return light detected");
            case 73: return _("Red Eye, Compulsory Flash");
            case 77: return _("Red Eye, Compulsory Flash, Return light not detected");
            case 79: return _("Red Eye, Compulsory Flash, Return light detected");
            case 89: return _("Red Eye, Auto-Mode");
            case 93: return _("Red Eye, Auto-Mode, Return light not detected");
            case 95: return _("Red Eye, Auto-Mode, Return light detected");
            }
            break;

        case 'FileSize':
           if ($data <= 0) {
              return '0 Bytes';
           }
           $s = array('B', 'kB', 'MB', 'GB');
           $e = floor(log($data, 1024));
           return round($data/pow(1024, $e), 2) . ' ' . $s[$e];

        case 'FileSource':
            $data = bin2hex($data);
            $data = str_replace('00', '', $data);
            $data = str_replace('03', _("Digital Still Camera"), $data);
            return $data;

        case 'SensingMethod':
            switch ($data) {
            case 1: return _("Not defined");
            case 2: return _("One Chip Color Area Sensor");
            case 3: return _("Two Chip Color Area Sensor");
            case 4: return _("Three Chip Color Area Sensor");
            case 5: return _("Color Sequential Area Sensor");
            case 7: return _("Trilinear Sensor");
            case 8: return _("Color Sequential Linear Sensor");
            default: return _("Unknown");
            }

        case 'ColorSpace':
            switch ($data) {
            case 1: return _("sRGB");
            default: return _("Uncalibrated");
            }

        case 'DateTime':
        case 'DateTimeOriginal':
        case 'DateTimeDigitized':
            return date('m/d/Y H:i:s O', $data);

        default:
            return $data;
        }
    }

    /**
     * Get the list of Exif fields we support, and their descriptions.
     *
     * @static
     *
     * @return array  Hash of fieldname => description.
     */
    function getFields()
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

}

/**
 * Greatly modified Exifer library based on Exifer 1.6 -
 * Modified to make it more of a drop-in replacement for php's
 * exif functions to allow either to function with our exif code.
 *
 * Also added a number of exif properties, including a proper 35mm
 * focal length tag as well as tweaks to the calculations of some of
 * the values.
 *
 * Modifications Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 * and done by Michael J. Rubinsky <mrubinsk@horde.org>
 */

/*
    Exifer 1.6
    Extracts EXIF information from digital photos.

    Originally created by:
    Copyright © 2005 Jake Olefsky
    http:// www.offsky.com/software/exif/index.php
    jake@olefsky.com

    This program is free software; you can redistribute it and/or modify it under the terms of
    the GNU General Public License as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU General Public License for more details. http:// www.gnu.org/copyleft/gpl.html

    SUMMARY:
                This script will correctly parse all of the EXIF data included in images taken
                with digital cameras.  It will read the IDF0, IDF1, SubIDF and InteroperabilityIFD
                fields as well as parsing some of the MakerNote fields that vary depending on
                camera make and model.  This script parses more tags than the internal PHP exif
                implementation and it will correctly identify and decode what all the values mean.

                This version will correctly parse the MakerNote field for Nikon, Olympus, and Canon
                digital cameras.  Others will follow.

    TESTED WITH:
                Nikon CoolPix 700
                Nikon CoolPix E3200
                Nikon CoolPix 4500
                Nikon CoolPix 950
                Nikon Coolpix 5700
                Canon PowerShot S200
                Canon PowerShot S110
                Olympus C2040Z
                Olympus C960
                Olumpus E-300
                Olympus E-410
                Olympus E-500
                Olympus E-510
                Olympus E-3
                Canon Ixus
                Canon EOS 300D
                Canon Digital Rebel
                Canon EOS 10D
                Canon PowerShot G2
                FujiFilm DX 10
                FujiFilm MX-1200
                FujiFilm FinePix2400
                FujiFilm FinePix2600
                FujiFilm FinePix S602
                FujiFilm FinePix40i
                Sony D700
                Sony Cybershot
                Kodak DC210
                Kodak DC240
                Kodak DC4800
                Kodak DX3215
                Ricoh RDC-5300
                Sanyo VPC-G250
                Sanyo VPC-SX550
                Epson 3100z


    VERSION HISTORY:

    1.0   September 23, 2002

        + First Public Release

    1.1    January 25, 2003

        + Gracefully handled the error case where you pass an empty string to this library
        + Fixed an inconsistency in the Olympus Camera parsing module
        + Added support for parsing the MakerNote of Canon images.
        + Modified how the imagefile is opened so it works for windows machines.
        + Correctly parses the FocalPlaneResolutionUnit and PhotometricInterpretation fields
        + Negative rational numbers are properly displayed
        + Strange old cameras that use Motorola endineness are now properly supported
        + Tested with several more cameras

        Potential Problem: Negative Shorts and Negative Longs may not be correctly displayed, but I
            have not yet found an example of negative shorts or longs being used.

    1.2    March 30, 2003

        + Fixed an error that was displayed if you edited your image with WinXP's image viewer
        + Fixed a bug that caused some images saved from 3rd party software to not parse correctly
        + Changed the ExposureTime tag to display in fractional seconds rather than decimal
        + Updated the ShutterSpeedValue tag to have the units of 'sec'
        + Added support for parsing the MakeNote of FujiFilm images
        + Added support for parsing the MakeNote of Sanyo images
        + Fixed a bug with parsing some Olympus MakerNote tags
        + Tested with several more cameras

    1.3    June 15, 2003

        + Fixed Canon MakerNote support for some models
             (Canon has very difficult and inconsistent MakerNote syntax)
        + Negative signed shorts and negative signed longs are properly displayed
        + Several more tags are defined
        + More information in my comments about what each tag is
        + Parses and Displays GPS information if available
        + Tested with several more cameras

    1.4    September 14, 2003

        + This software is now licensed under the GNU General Public License
        + Exposure time is now correctly displayed when the numerator is 10
        + Fixed the calculation and display of ShutterSpeedValue, ApertureValue and MaxApertureValue
        + Fixed a bug with the GPS code
        + Tested with several more cameras

    1.5    February 18, 2005

        + It now gracefully deals with a passed in file that cannot be found.
        + Fixed a GPS bug for the parsing of Altitude and other signed rational numbers
        + Defined more values for Canon cameras.
        + Added 'bulb' detection for ShutterSpeed
        + Made script loading a little faster and less memory intensive.
        + Bug fixes
        + Better error reporting
        + Graceful failure for files with corrupt exif info.
        + QuickTime (including iPhoto) messes up the Makernote tag for certain photos (no workaround yet)
        + Now reads exif information when the jpeg markers are out of order
        + Gives raw data output for IPTC, COM and APP2 fields which are sometimes set by other applications
        + Improvements to Nikon Makernote parsing

    1.6    March 25th, 2007 [Zenphoto]

        + Adopted into the Zenphoto gallery project, at http://www.zenphoto.org
        + Fixed a bug where strings had trailing null bytes.
        + Formatted selected strings better.
        + Added calculation of 35mm-equivalent focal length when possible.
        + Cleaned up code for readability and efficiency.

    1.7    April 11th, 2008 [Zenphoto]

      + Fixed bug with newer Olympus cameras where number of fields was miscalculated leading to bad performance.
        + More logical fraction calculation for shutter speed.

*/






//================================================================================================
// Converts from Intel to Motorola endien.  Just reverses the bytes (assumes hex is passed in)
//================================================================================================

function intel2Moto($intel) {
    $len  = strlen($intel);
    $moto = '';
    for($i = 0; $i <= $len; $i += 2) {
        $moto .= substr($intel, $len-$i, 2);
    }
    return $moto;
}


//================================================================================================
// Looks up the name of the tag
//================================================================================================
function lookup_tag($tag) {
    switch($tag) {
        // used by IFD0 'Camera Tags'
        case '000b': $tag = 'ACDComment'; break;               // text string up to 999 bytes long
        case '00fe': $tag = 'ImageType'; break;                // integer -2147483648 to 2147483647
        case '0106': $tag = 'PhotometricInterpret'; break;     // ?? Please send sample image with this tag
        case '010e': $tag = 'ImageDescription'; break;         // text string up to 999 bytes long
        case '010f': $tag = 'Make'; break;                     // text string up to 999 bytes long
        case '0110': $tag = 'Model'; break;                    // text string up to 999 bytes long
        case '0112': $tag = 'Orientation'; break;              // integer values 1-9
        case '0115': $tag = 'SamplePerPixel'; break;           // integer 0-65535
        case '011a': $tag = 'xResolution'; break;              // positive rational number
        case '011b': $tag = 'yResolution'; break;              // positive rational number
        case '011c': $tag = 'PlanarConfig'; break;             // integer values 1-2
        case '0128': $tag = 'ResolutionUnit'; break;           // integer values 1-3
        case '0131': $tag = 'Software'; break;                 // text string up to 999 bytes long
        case '0132': $tag = 'DateTime'; break;                 // YYYY:MM:DD HH:MM:SS
        case '013b': $tag = 'Artist'; break;                   // text string up to 999 bytes long
        case '013c': $tag = 'HostComputer'; break;             // text string
        case '013e': $tag = 'WhitePoint'; break;               // two positive rational numbers
        case '013f': $tag = 'PrimaryChromaticities'; break;    // six positive rational numbers
        case '0211': $tag = 'YCbCrCoefficients'; break;        // three positive rational numbers
        case '0213': $tag = 'YCbCrPositioning'; break;         // integer values 1-2
        case '0214': $tag = 'ReferenceBlackWhite'; break;      // six positive rational numbers
        case '8298': $tag = 'Copyright'; break;                // text string up to 999 bytes long
        case '8649': $tag = 'PhotoshopSettings'; break;        // ??
        case '8825': $tag = 'GPSInfoOffset'; break;
        case '9286': $tag = 'UserCommentOld'; break;           // ??
        case '8769': $tag = 'ExifOffset'; break;               // positive integer
        // used by Exif SubIFD 'Image Tags'
        case '829a': $tag = 'ExposureTime'; break;             // seconds or fraction of seconds 1/x
        case '829d': $tag = 'FNumber'; break;                  // positive rational number
        case '8822': $tag = 'ExposureProgram'; break;          // integer value 1-9
        case '8824': $tag = 'SpectralSensitivity'; break;      // ??
        case '8827': $tag = 'ISOSpeedRatings'; break;          // integer 0-65535
        case '9000': $tag = 'ExifVersion'; break;              // ??
        case '9003': $tag = 'DateTimeOriginal'; break;         // YYYY:MM:DD HH:MM:SS
        case '9004': $tag = 'DateTimedigitized'; break;        // YYYY:MM:DD HH:MM:SS
        case '9101': $tag = 'ComponentsConfiguration'; break;  // ??
        case '9102': $tag = 'CompressedBitsPerPixel'; break;   // positive rational number
        case '9201': $tag = 'ShutterSpeedValue'; break;        // seconds or fraction of seconds 1/x
        case '9202': $tag = 'ApertureValue'; break;            // positive rational number
        case '9203': $tag = 'BrightnessValue'; break;          // positive rational number
        case '9204': $tag = 'ExposureBiasValue'; break;        // positive rational number (EV)
        case '9205': $tag = 'MaxApertureValue'; break;         // positive rational number
        case '9206': $tag = 'SubjectDistance'; break;          // positive rational number (meters)
        case '9207': $tag = 'MeteringMode'; break;             // integer 1-6 and 255
        case '9208': $tag = 'LightSource'; break;              // integer 1-255
        case '9209': $tag = 'Flash'; break;                    // integer 1-255
        case '920a': $tag = 'FocalLength'; break;              // positive rational number (mm)
        case '9213': $tag = 'ImageHistory'; break;             // text string up to 999 bytes long
        case '927c': $tag = 'MakerNote'; break;                // a bunch of data
        case '9286': $tag = 'UserComment'; break;              // text string
        case '9290': $tag = 'SubsecTime'; break;               // text string up to 999 bytes long
        case '9291': $tag = 'SubsecTimeOriginal'; break;       // text string up to 999 bytes long
        case '9292': $tag = 'SubsecTimeDigitized'; break;      // text string up to 999 bytes long
        case 'a000': $tag = 'FlashPixVersion'; break;          // ??
        case 'a001': $tag = 'ColorSpace'; break;               // values 1 or 65535
        case 'a002': $tag = 'ExifImageWidth'; break;           // ingeter 1-65535
        case 'a003': $tag = 'ExifImageHeight'; break;          // ingeter 1-65535
        case 'a004': $tag = 'RelatedSoundFile'; break;         // text string 12 bytes long
        case 'a005': $tag = 'ExifInteroperabilityOffset'; break;    // positive integer
        case 'a20c': $tag = 'SpacialFreqResponse'; break;      // ??
        case 'a20b': $tag = 'FlashEnergy'; break;              // positive rational number
        case 'a20e': $tag = 'FocalPlaneXResolution'; break;    // positive rational number
        case 'a20f': $tag = 'FocalPlaneYResolution'; break;    // positive rational number
        case 'a210': $tag = 'FocalPlaneResolutionUnit'; break; // values 1-3
        case 'a214': $tag = 'SubjectLocation'; break;          // two integers 0-65535
        case 'a215': $tag = 'ExposureIndex'; break;            // positive rational number
        case 'a217': $tag = 'SensingMethod'; break;            // values 1-8
        case 'a300': $tag = 'FileSource'; break;               // integer
        case 'a301': $tag = 'SceneType'; break;                // integer
        case 'a302': $tag = 'CFAPattern'; break;               // undefined data type
        case 'a401': $tag = 'CustomerRender'; break;           // values 0 or 1
        case 'a402': $tag = 'ExposureMode'; break;             // values 0-2
        case 'a403': $tag = 'WhiteBalance'; break;             // values 0 or 1
        case 'a404': $tag = 'DigitalZoomRatio'; break;         // positive rational number
        case 'a405': $tag = 'FocalLengthIn35mmFilm';break;
        case 'a406': $tag = 'SceneCaptureMode'; break;         // values 0-3
        case 'a407': $tag = 'GainControl'; break;              // values 0-4
        case 'a408': $tag = 'Contrast'; break;                 // values 0-2
        case 'a409': $tag = 'Saturation'; break;               // values 0-2
        case 'a40a': $tag = 'Sharpness'; break;                // values 0-2

        // used by Interoperability IFD
        case '0001': $tag = 'InteroperabilityIndex'; break;    // text string 3 bytes long
        case '0002': $tag = 'InteroperabilityVersion'; break;  // datatype undefined
        case '1000': $tag = 'RelatedImageFileFormat'; break;   // text string up to 999 bytes long
        case '1001': $tag = 'RelatedImageWidth'; break;        // integer in range 0-65535
        case '1002': $tag = 'RelatedImageLength'; break;       // integer in range 0-65535

        // used by IFD1 'Thumbnail'
        case '0100': $tag = 'ImageWidth'; break;               // integer in range 0-65535
        case '0101': $tag = 'ImageLength'; break;              // integer in range 0-65535
        case '0102': $tag = 'BitsPerSample'; break;            // integers in range 0-65535
        case '0103': $tag = 'Compression'; break;              // values 1 or 6
        case '0106': $tag = 'PhotometricInterpretation'; break;// values 0-4
        case '010e': $tag = 'ThumbnailDescription'; break;     // text string up to 999 bytes long
        case '010f': $tag = 'ThumbnailMake'; break;            // text string up to 999 bytes long
        case '0110': $tag = 'ThumbnailModel'; break;           // text string up to 999 bytes long
        case '0111': $tag = 'StripOffsets'; break;             // ??
        case '0112': $tag = 'ThumbnailOrientation'; break;     // integer 1-9
        case '0115': $tag = 'SamplesPerPixel'; break;          // ??
        case '0116': $tag = 'RowsPerStrip'; break;             // ??
        case '0117': $tag = 'StripByteCounts'; break;          // ??
        case '011a': $tag = 'ThumbnailXResolution'; break;     // positive rational number
        case '011b': $tag = 'ThumbnailYResolution'; break;     // positive rational number
        case '011c': $tag = 'PlanarConfiguration'; break;      // values 1 or 2
        case '0128': $tag = 'ThumbnailResolutionUnit'; break;  // values 1-3
        case '0201': $tag = 'JpegIFOffset'; break;
        case '0202': $tag = 'JpegIFByteCount'; break;
        case '0212': $tag = 'YCbCrSubSampling'; break;

        // misc
        case '00ff': $tag = 'SubfileType'; break;
        case '012d': $tag = 'TransferFunction'; break;
        case '013d': $tag = 'Predictor'; break;
        case '0142': $tag = 'TileWidth'; break;
        case '0143': $tag = 'TileLength'; break;
        case '0144': $tag = 'TileOffsets'; break;
        case '0145': $tag = 'TileByteCounts'; break;
        case '014a': $tag = 'SubIFDs'; break;
        case '015b': $tag = 'JPEGTables'; break;
        case '828d': $tag = 'CFARepeatPatternDim'; break;
        case '828e': $tag = 'CFAPattern'; break;
        case '828f': $tag = 'BatteryLevel'; break;
        case '83bb': $tag = 'IPTC/NAA'; break;
        case '8773': $tag = 'InterColorProfile'; break;

        case '8828': $tag = 'OECF'; break;
        case '8829': $tag = 'Interlace'; break;
        case '882a': $tag = 'TimeZoneOffset'; break;
        case '882b': $tag = 'SelfTimerMode'; break;
        case '920b': $tag = 'FlashEnergy'; break;
        case '920c': $tag = 'SpatialFrequencyResponse'; break;
        case '920d': $tag = 'Noise'; break;
        case '9211': $tag = 'ImageNumber'; break;
        case '9212': $tag = 'SecurityClassification'; break;
        case '9214': $tag = 'SubjectLocation'; break;
        case '9215': $tag = 'ExposureIndex'; break;
        case '9216': $tag = 'TIFF/EPStandardID'; break;
        case 'a20b': $tag = 'FlashEnergy'; break;

        default: $tag = 'unknown:'.$tag; break;
    }
    return $tag;

}


//==============================================================================
// Looks up the datatype
//==============================================================================
function lookup_type(&$type,&$size) {
    switch($type) {
        case '0001': $type = 'UBYTE'; $size=1; break;
        case '0002': $type = 'ASCII'; $size=1; break;
        case '0003': $type = 'USHORT'; $size=2; break;
        case '0004': $type = 'ULONG'; $size=4; break;
        case '0005': $type = 'URATIONAL'; $size=8; break;
        case '0006': $type = 'SBYTE'; $size=1; break;
        case '0007': $type = 'UNDEFINED'; $size=1; break;
        case '0008': $type = 'SSHORT'; $size=2; break;
        case '0009': $type = 'SLONG'; $size=4; break;
        case '000a': $type = 'SRATIONAL'; $size=8; break;
        case '000b': $type = 'FLOAT'; $size=4; break;
        case '000c': $type = 'DOUBLE'; $size=8; break;
        default: $type = 'error:'.$type; $size=0; break;
    }
    return $type;
}

//==============================================================================
// Formats Data for the data type
//==============================================================================
function formatData($type, $tag, $intel, $data)
{
    if ($type == 'ASCII') {
        // Search for a null byte and stop there.
        if (($pos = strpos($data, chr(0))) !== false) {
            $data = substr($data, 0, $pos);
        }
        // Format certain kinds of strings nicely (Camera make etc.)
        if ($tag == '010f') {
            $data = ucwords(strtolower(trim($data)));
        }

    } elseif ($type == 'URATIONAL' || $type == 'SRATIONAL') {
        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }

        if ($intel == 1) {
            $top = hexdec(substr($data,8,8)); // intel stores them bottom-top
        } else {
            $top = hexdec(substr($data,0,8)); // motorola stores them top-bottom
        }

        if ($intel == 1) {
            $bottom = hexdec(substr($data,0,8));  // intel stores them bottom-top
        } else {
            $bottom = hexdec(substr($data,8,8));  // motorola stores them top-bottom
        }

        if ($type == 'SRATIONAL' && $top > 2147483647) {
            // this makes the number signed instead of unsigned
            $top = $top - 4294967296;
        }
        if ($bottom != 0) {
            $data = $top / $bottom;
        } elseif ($top == 0) {
            $data = 0;
        } else {
            $data = $top . '/' . $bottom;
        }

        // Exposure Time
        if ($tag == '829a') {
            if ($bottom != 0) {
                $data = $top . '/' . $bottom;
            } else {
                $data = 0;
            }
        }

    } elseif ($type == 'USHORT' || $type == 'SSHORT' || $type == 'ULONG' ||
              $type == 'SLONG' || $type == 'FLOAT' || $type == 'DOUBLE') {

        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
        if ($intel == 0 && ($type == 'USHORT' || $type == 'SSHORT')) {
            $data = substr($data, 0, 4);
        }
        $data = hexdec($data);
        if ($type == 'SSHORT' && $data > 32767) {
            // this makes the number signed instead of unsigned
            $data = $data - 65536;
        }
        if ($type == 'SLONG' && $data > 2147483647) {
            // this makes the number signed instead of unsigned
            $data = $data - 4294967296;
        }
    } elseif ($type == 'UNDEFINED') {
        // ExifVersion,FlashPixVersion,InteroperabilityVersion
        if ($tag == '9000' || $tag == 'a000' || $tag == '0002') {
            $data = sprintf(_("version %d"), $data / 100);
        }
    } else {
        $data = bin2hex($data);
        if ($intel == 1) $data = intel2Moto($data);
    }

    return $data;
}

//==============================================================================
// Reads one standard IFD entry
//==============================================================================
function read_entry(&$result, $in, $seek, $intel, $ifd_name, $globalOffset) {

    // Still ok to read?
    if (feof($in)) {
        $result['Errors'] = $result['Errors'] + 1;
        return;
    }

    // 2 byte tag
    $tag = bin2hex(fread($in, 2));
    if ($intel == 1) $tag = intel2Moto($tag);
    $tag_name = lookup_tag($tag);

    // 2 byte datatype
    $type = bin2hex(fread($in, 2));
    if ($intel == 1) $type = intel2Moto($type);
    lookup_type($type, $size);

    // 4 byte number of elements
    $count = bin2hex(fread($in, 4));
    if ($intel == 1) $count = intel2Moto($count);
    $bytesofdata = $size * hexdec($count);

    // 4 byte value or pointer to value if larger than 4 bytes
    $value = fread($in, 4 );

    // if datatype is 4 bytes or less, its the value
    if ($bytesofdata <= 4) {
        $data = $value;
    } elseif ($bytesofdata < 100000) {
        // otherwise its a pointer to the value, so lets go get it
        $value = bin2hex($value);
        if ($intel == 1) {
            $value = intel2Moto($value);
        }
        // offsets are from TIFF header which is 12 bytes from the start of file
        $v = fseek($seek, $globalOffset+hexdec($value));
        if ($v == 0) {
            $data = fread($seek, $bytesofdata);
        } elseif ($v == -1) {
            $result['Errors'] = $result['Errors'] + 1;
        }
    } else {
        // bytesofdata was too big, so the exif had an error
        $result['Errors'] = $result['Errors'] + 1;
        return;
    }

    // if its a maker tag, we need to parse this specially
    if ($tag_name == 'MakerNote') {
        $make = $result['IFD0']['Make'];
        if (strpos(strtolower($make), 'nikon') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/nikon.php';
            parseNikon($data, $result);
            $result[$ifd_name]['KnownMaker'] = 1;
        } elseif (strpos(strtolower($make), 'olympus') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/olympus.php';
            parseOlympus($data, $result, $seek, $globalOffset);
            $result[$ifd_name]['KnownMaker'] = 1;
        } elseif (strpos(strtolower($make), 'canon') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/canon.php';
            parseCanon($data, $result, $seek, $globalOffset);
            $result[$ifd_name]['KnownMaker'] = 1;
        } elseif (strpos(strtolower($make), 'fujifilm') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/fujifilm.php';
            parseFujifilm($data, $result);
            $result[$ifd_name]['KnownMaker'] = 1;
        } elseif (strpos(strtolower($make), 'sanyo') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/sanyo.php';
            parseSanyo($data, $result, $seek, $globalOffset);
            $result[$ifd_name]['KnownMaker'] = 1;
        } elseif (strpos(strtolower($make), 'panasonic') !== false) {
            include_once ANSEL_BASE . '/lib/Exif/panasonic.php';
            parsePanasonic($data, $result, $seek, $globalOffset);
            $result[$ifd_name]['KnownMaker'] = 1;
        } else {
            $result[$ifd_name]['KnownMaker'] = 0;
        }
    } elseif ($tag_name == 'GPSInfoOffset') {
        include_once ANSEL_BASE . '/lib/Exif/gps.php';
        $formated_data = formatData($type, $tag, $intel, $data);
        $result[$ifd_name]['GPSInfo'] = $formated_data;
        parseGPS($data, $result, $formated_data, $seek, $globalOffset);
    } else {
        // Format the data depending on the type and tag
        $formated_data = formatData($type, $tag, $intel, $data);
        $result[$ifd_name][$tag_name] = $formated_data;
    }
}


//================================================================================================
// Pass in a file and this reads the EXIF data
//
// Usefull resources
// http:// www.ba.wakwak.com/~tsuruzoh/Computer/Digicams/exif-e.html
// http:// www.w3.org/Graphics/JPEG/jfif.txt
// http:// exif.org/
// http:// www.ozhiker.com/electronics/pjmt/library/list_contents.php4
// http:// www.ozhiker.com/electronics/pjmt/jpeg_info/makernotes.html
// http:// pel.sourceforge.net/
// http:// us2.php.net/manual/en/function.exif-read-data.php
//================================================================================================
function read_exif_data_raw($path)
{

    if ($path == '' || $path == 'none') {
        return;
    }

    // the b is for windows machines to open in binary mode
    $in = @fopen($path, 'rb');

    // There may be an elegant way to do this with one file handle.
    $seek = @fopen($path, 'rb');
    $globalOffset = 0;
    $result['Errors'] = 0;

    // if the path was invalid, this error will catch it
    if (!$in || !$seek) {
        $result['Errors'] = 1;
        $result['Error'][$result['Errors']] = _("The file could not be found.");
        return $result;
    }

    // First 2 bytes of JPEG are 0xFFD8
    $data = bin2hex(fread($in, 2));
    if ($data == 'ffd8') {
        $result['ValidJpeg'] = 1;
    } else {
        $result['ValidJpeg'] = 0;
        fclose($in);
        fclose($seek);
        return $result;
    }

    $result['ValidIPTCData'] = 0;
    $result['ValidJFIFData'] = 0;
    $result['ValidEXIFData'] = 0;
    $result['ValidAPP2Data'] = 0;
    $result['ValidCOMData'] = 0;

    // Next 2 bytes are MARKER tag (0xFFE#)
    $data = bin2hex(fread($in, 2));
    $size = bin2hex(fread($in, 2));

    // LOOP THROUGH MARKERS TILL YOU GET TO FFE1  (exif marker)
    while(!feof($in) && $data != 'ffe1' && $data != 'ffc0' && $data != 'ffd9') {
        if ($data == 'ffe0') { // JFIF Marker
            $result['ValidJFIFData'] = 1;
            $result['JFIF']['Size'] = hexdec($size);

            if (hexdec($size) - 2 > 0) {
                $data = fread($in, hexdec($size) - 2);
                $result['JFIF']['Data'] = $data;
            }

            $result['JFIF']['Identifier'] = substr($data, 0, 5);;
            $result['JFIF']['ExtensionCode'] =  bin2hex(substr($data, 6, 1));

            $globalOffset+=hexdec($size) + 2;

        } elseif ($data == 'ffed') {  // IPTC Marker
            $result['ValidIPTCData'] = 1;
            $result['IPTC']['Size'] = hexdec($size);

            if (hexdec($size) - 2 > 0) {
                $data = fread($in, hexdec($size)-2);
                $result['IPTC']['Data'] = $data ;
            }
            $globalOffset += hexdec($size) + 2;

        } elseif ($data == 'ffe2') {  // EXIF extension Marker
            $result['ValidAPP2Data'] = 1;
            $result['APP2']['Size'] = hexdec($size);

            if (hexdec($size)-2 > 0) {
                $data = fread($in, hexdec($size) - 2);
                $result['APP2']['Data'] = $data ;
            }
            $globalOffset+=hexdec($size) + 2;

        } elseif ($data == 'fffe') {  // COM extension Marker
            $result['ValidCOMData'] = 1;
            $result['COM']['Size'] = hexdec($size);

            if (hexdec($size)-2 > 0) {
                $data = fread($in, hexdec($size) - 2);
                $result['COM']['Data'] = $data ;
            }
            $globalOffset += hexdec($size) + 2;

        } else if ($data == 'ffe1') {
            $result['ValidEXIFData'] = 1;
        }

        $data = bin2hex(fread($in, 2));
        $size = bin2hex(fread($in, 2));
    }
    // END MARKER LOOP

    if ($data == 'ffe1') {
        $result['ValidEXIFData'] = 1;
    } else {
        fclose($in);
        fclose($seek);
        return $result;
    }

    // Size of APP1
    $result['APP1Size'] = hexdec($size);

    // Start of APP1 block starts with 'Exif' header (6 bytes)
    $header = fread($in, 6);

    // Then theres a TIFF header with 2 bytes of endieness (II or MM)
    $header = fread($in, 2);
    if ($header==='II') {
        $intel = 1;
        $result['Endien'] = 'Intel';
    } elseif ($header==='MM') {
        $intel = 0;
        $result['Endien'] = 'Motorola';
    } else {
        $intel = 1; // not sure what the default should be, but this seems reasonable
        $result['Endien'] = 'Unknown';
    }

    // 2 bytes of 0x002a
    $tag = bin2hex(fread( $in, 2 ));

    // Then 4 bytes of offset to IFD0 (usually 8 which includes all 8 bytes of TIFF header)
    $offset = bin2hex(fread($in, 4));
    if ($intel == 1) {
        $offset = intel2Moto($offset);
    }

    // Check for extremely large values here
    if (hexdec($offset) > 100000) {
        $result['ValidEXIFData'] = 0;
        fclose($in);
        fclose($seek);
        return $result;
    }

    if (hexdec($offset) > 8) {
        $unknown = fread($in, hexdec($offset) - 8);
    }

    // add 12 to the offset to account for TIFF header
    $globalOffset += 12;

    //===========================================================
    // Start of IFD0
    $num = bin2hex(fread($in, 2));
    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $num = hexdec($num);
    $result['IFD0NumTags'] = $num;

    // 1000 entries is too much and is probably an error.
    if ($num < 1000) {
        for($i = 0; $i < $num; $i++) {
            read_entry($result, $in, $seek, $intel, 'IFD0', $globalOffset);
        }
    } else {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = 'Illegal size for IFD0';
    }

    // store offset to IFD1
    $offset = bin2hex(fread($in, 4));
    if ($intel == 1) {
        $offset = intel2Moto($offset);
    }
    $result['IFD1Offset'] = hexdec($offset);

    // Check for SubIFD
    if (!isset($result['IFD0']['ExifOffset']) || $result['IFD0']['ExifOffset'] == 0) {
        fclose($in);
        fclose($seek);
        return $result;
    }

    // seek to SubIFD (Value of ExifOffset tag) above.
    $ExitOffset = $result['IFD0']['ExifOffset'];
    $v = fseek($in, $globalOffset + $ExitOffset);
    if ($v == -1) {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Couldnt Find SubIFD");
    }

    //===========================================================
    // Start of SubIFD
    $num = bin2hex(fread($in, 2));
    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $num = hexdec($num);
    $result['SubIFDNumTags'] = $num;

    // 1000 entries is too much and is probably an error.
    if ($num < 1000) {
        for($i = 0; $i < $num; $i++) {
            read_entry($result, $in, $seek, $intel, 'SubIFD', $globalOffset);
        }
    } else {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Illegal size for SubIFD");
    }

    // Add the 35mm equivalent focal length:
    // Now properly get this using the FocalLength35mmFilm tag
    //$result['SubIFD']['FocalLength35mmEquiv'] = get35mmEquivFocalLength($result);

    // Check for IFD1
    if (!isset($result['IFD1Offset']) || $result['IFD1Offset'] == 0) {
        fclose($in);
        fclose($seek);
        return $result;
    }

    // seek to IFD1
    $v = fseek($in, $globalOffset + $result['IFD1Offset']);
    if ($v == -1) {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Couldnt Find IFD1");
    }

    //===========================================================
    // Start of IFD1
    $num = bin2hex(fread($in, 2));
    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $num = hexdec($num);
    $result['IFD1NumTags'] = $num;

    // 1000 entries is too much and is probably an error.
    if ($num < 1000) {
        for($i = 0; $i < $num; $i++) {
            read_entry($result, $in, $seek, $intel, 'IFD1', $globalOffset);
        }
    } else {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Illegal size for IFD1");
    }
    // include the thumbnail raw data...
    if ($result['IFD1']['JpegIFOffset'] > 0 &&
        $result['IFD1']['JpegIFByteCount'] > 0) {

        $v = fseek($seek, $globalOffset + $result['IFD1']['JpegIFOffset']);
        if ($v == 0) {
            $data = fread($seek, $result['IFD1']['JpegIFByteCount']);
        } else if ($v == -1) {
            $result['Errors'] = $result['Errors'] + 1;
        }
        $result['IFD1']['ThumbnailData'] = $data;
    }

    // Check for Interoperability IFD
    if (!isset($result['SubIFD']['ExifInteroperabilityOffset']) ||
        $result['SubIFD']['ExifInteroperabilityOffset'] == 0) {

        fclose($in);
        fclose($seek);
        return $result;
    }

    // Seek to InteroperabilityIFD
    $v = fseek($in, $globalOffset + $result['SubIFD']['ExifInteroperabilityOffset']);
    if ($v == -1) {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Couldnt Find InteroperabilityIFD");
    }

    //===========================================================
    // Start of InteroperabilityIFD
    $num = bin2hex(fread($in, 2));
    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $num = hexdec($num);
    $result['InteroperabilityIFDNumTags'] = $num;

    // 1000 entries is too much and is probably an error.
    if ($num < 1000) {
        for($i = 0; $i < $num; $i++) {
            read_entry($result, $in, $seek, $intel, 'InteroperabilityIFD', $globalOffset);
        }
    } else {
        $result['Errors'] = $result['Errors'] + 1;
        $result['Error'][$result['Errors']] = _("Illegal size for InteroperabilityIFD");
    }
    fclose($in);
    fclose($seek);
    return $result;
}
