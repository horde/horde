<?php
/**
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Image
 */

/**
 * Exifer
 * Extracts EXIF information from digital photos.
 *
 * Copyright © 2003 Jake Olefsky
 * http://www.offsky.com/software/exif/index.php
 * jake@olefsky.com
 *
 * ------------
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details. http://www.gnu.org/copyleft/gpl.html
 */
class Horde_Image_Exif_Parser_Gps extends Horde_Image_Exif_Parser_Base
{
    /**
     * Looks up the name of the tag
     *
     * @param unknown_type $tag
     * @return string
     */
    protected function _lookupTag($tag)
    {
        switch($tag) {
        case '0000': return 'Version';
        //north or south
        case '0001': return 'LatitudeRef';
        //dd mm.mm or dd mm ss
        case '0002': return 'Latitude';
        //east or west
        case '0003': return 'LongitudeRef';
        //dd mm.mm or dd mm ss
        case '0004': return 'Longitude';
        //sea level or below sea level
        case '0005': return 'AltitudeRef';
        //positive rational number
        case '0006': return 'Altitude';
        //three positive rational numbers
        case '0007': return 'Time';
        //text string up to 999 bytes long
        case '0008': return 'Satellite';
			//in progress or interop
        case '0009': return 'ReceiveStatus';
		//2D or 3D
        case '000a': return 'MeasurementMode';
        //positive rational number
        case '000b': return 'MeasurementPrecision';
        //KPH, MPH, knots
        case '000c': return 'SpeedUnit';
			//positive rational number
        case '000d': return 'ReceiverSpeed';
        //true or magnetic north
        case '000e': return 'MovementDirectionRef';
        //positive rational number
        case '000f': return 'MovementDirection';
        //true or magnetic north
        case '0010': return 'ImageDirectionRef';
			//positive rational number
        case '0011': return 'ImageDirection';
        //text string up to 999 bytes long
        case '0012': return 'GeodeticSurveyData';
		//north or south
        case '0013': return 'DestLatitudeRef';
        //three positive rational numbers
        case '0014': return 'DestinationLatitude';
		//east or west
        case '0015': return 'DestLongitudeRef';
        //three positive rational numbers
        case '0016': return 'DestinationLongitude';
			//true or magnetic north
        case '0017': return 'DestBearingRef';
        //positive rational number
        case '0018': return 'DestinationBearing';
		//km, miles, knots
        case '0019': return 'DestDistanceRef';
        //positive rational number
        case '001a': return 'DestinationDistance';
        case '001b': return 'ProcessingMethod';
        case '001c': return 'AreaInformation';
        //text string 10 bytes long
        case '001d': return 'Datestamp';
        //integer in range 0-65535
        case '001e': return 'DifferentialCorrection';
        default: return 'unknown: ' . $tag;
        }
    }

    /**
     * Formats a rational number
     */
    protected function _rational($data, $intel)
    {
        if ($intel == 1) {
            //intel stores them bottom-top
            $top = hexdec(substr($data, 8, 8));
        } else {
            //motorola stores them top-bottom
            $top = hexdec(substr($data, 0, 8));
        }

        if ($intel == 1) {
            $bottom = hexdec(substr($data, 0, 8));
        } else {
            $bottom = hexdec(substr($data, 8, 8));
        }

        if ($bottom != 0) {
            $data = $top / $bottom;
        } elseif ($top == 0) {
            $data = 0;
        } else {
            $data = $top . '/' . $bottom;
        }

        return $data;
    }

    /**
     * Formats Data for the data type
     */
    protected function _formatData($type, $tag, $intel, $data)
    {
        switch ($type) {
        case 'ASCII':
            // Latitude Reference, Longitude Reference
            if ($tag == '0001' || $tag == '0003') {
                $data = ($data{1} == $data{2} && $data{1} == $data{3}) ? $data{0} : $data;
            }
            break;

        case 'URATIONAL':
        case 'SRATIONAL':
            $data = bin2hex($data);
            if ($intel == 1) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            if ($intel == 1) {
                //intel stores them bottom-top
                $top = hexdec(substr($data, 8, 8));
            } else {
                //motorola stores them top-bottom
                $top = hexdec(substr($data, 0, 8));
            }

            if ($intel == 1) {
                $bottom = hexdec(substr($data, 0, 8));
            } else {
                $bottom = hexdec(substr($data, 8, 8));
            }

            if ($type == 'SRATIONAL' && $top > 2147483647) {
                // make the number signed instead of unsigned
                $top = $top - 4294967296;
            }

            switch ($tag) {
            case '0002':
            case '0004':
                //Latitude, Longitude
                if ($intel == 1) {
                    $seconds = $this->_rational(substr($data, 0, 16), $intel);
                    $hour = $this->_rational(substr($data, 32, 16), $intel);
                } else {
                    $hour = $this->_rational(substr($data, 0, 16), $intel);
                    $seconds = $this->_rational(substr($data, 32, 16), $intel);
                }
                $minutes = $this->_rational(substr($data, 16, 16), $intel);
                $data = array($hour, $minutes, $seconds);
                break;

            case '0007':
                //Time
                $seconds = $this->_rational(substr($data, 0, 16), $intel);
                $minutes = $this->_rational(substr($data, 16, 16), $intel);
                $hour = $this->_rational(substr($data, 32, 16), $intel);
                $data = $hour . ':' . $minutes . ':' . $seconds;
                break;

            default:
                if ($bottom != 0) {
                    $data = $top / $bottom;
                } elseif ($top == 0) {
                    $data = 0;
                } else {
                    $data = $top . '/' . $bottom;
                }
                if ($tag == '0006') {
                    $data .= 'm';
                }
                break;
            }
            break;

        case 'USHORT':
        case 'SSHORT':
        case 'ULONG':
        case 'SLONG':
        case 'FLOAT':
        case 'DOUBLE':
            $data = bin2hex($data);
            if ($intel == 1) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            $data = hexdec($data);
            break;

        case 'UNDEFINED':
            break;

        case 'UBYTE':
            $data = bin2hex($data);
            if ($intel == 1) {
                $num = Horde_Image_Exif::intel2Moto($data);
            }
            switch ($tag) {
            case '0000':
                // VersionID
                $data = hexdec(substr($data, 0, 2))
                    . '.' . hexdec(substr($data, 2, 2))
                    . '.' . hexdec(substr($data, 4, 2))
                    . '.'. hexdec(substr($data, 6, 2));
                break;
            case '0005':
                // Altitude Reference
                if ($data == '00000000') {
                    $data = 'Above Sea Level';
                } elseif ($data == '01000000') {
                    $data = 'Below Sea Level';
                }
                break;
            }
            break;

        default:
            $data = bin2hex($data);
            if ($intel == 1) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            break;
        }

        return $data;
    }

    /**
     * GPS Special data section
     *
     * @see http://drewnoakes.com/code/exif/sampleOutput.html
     * @see http://www.geosnapper.com
     */
    public function parse($block, &$result, $offset, $seek, $globalOffset)
    {
        if ($result['Endien'] == 'Intel') {
            $intel = 1;
        } else {
            $intel = 0;
        }

        //offsets are from TIFF header which is 12 bytes from the start of the
        //file
        $v = fseek($seek, $globalOffset + $offset);
        if ($v == -1) {
            $result['Errors'] = $result['Errors']++;
        }

        $num = bin2hex(fread($seek, 2));
        if ($intel == 1) {
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $num = hexdec($num);
        $result['GPS']['NumTags'] = $num;
        $block = fread($seek, $num * 12);
        $place = 0;

        //loop thru all tags  Each field is 12 bytes
        for ($i = 0; $i < $num; $i++) {
            //2 byte tag
            $tag = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel == 1) {
                $tag = Horde_Image_Exif::intel2Moto($tag);
            }
            $tag_name = $this->_lookupTag($tag);

            //2 byte datatype
            $type = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel == 1) {
                $type = Horde_Image_Exif::intel2Moto($type);
            }
            $this->_lookupType($type, $size);

            //4 byte number of elements
            $count = bin2hex(substr($block, $place, 4));
            $place += 4;
            if ($intel==1) {
                $count = Horde_Image_Exif::intel2Moto($count);
            }
            $bytesofdata = $size * hexdec($count);

            //4 byte value or pointer to value if larger than 4 bytes
            $value = substr($block, $place, 4);
            $place += 4;

            if ($bytesofdata <= 4) {
                $data = $value;
            } else {
                $value = bin2hex($value);
                if ($intel == 1) {
                    $value = Horde_Image_Exif::intel2Moto($value);
                }
                //offsets are from TIFF header which is 12 bytes from the start
                //of the file
                $v = fseek($seek, $globalOffset + hexdec($value));
                if ($v == 0) {
                    $data = fread($seek, $bytesofdata);
                } elseif ($v == -1) {
                    $result['Errors'] = $result['Errors']++;
                }
            }
            $result['GPS' . $tag_name] = $this->_formatData($type, $tag, $intel, $data);
        }
    }
}
