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
class Horde_Image_Exif_Parser_Panasonic extends Horde_Image_Exif_Parser_Base
{
    /**
     * Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
     */
    protected function _lookupTag($tag)
    {
        switch ($tag) {
        case '0001': $tag = 'Quality'; break;
        case '0002': $tag = 'FirmwareVersion'; break;
        case '0003': $tag = 'WhiteBalance'; break;
        case '0007': $tag = 'FocusMode'; break;
        case '000f': $tag = 'AFMode'; break;
        case '001a': $tag = 'ImageStabilizer'; break;
        case '001c': $tag = 'MacroMode'; break;
        case '001f': $tag = 'ShootingMode'; break;
        case '0020': $tag = 'Audio'; break;
        case '0021': $tag = 'DataDump'; break;
        case '0023': $tag = 'WhiteBalanceBias'; break;
        case '0024': $tag = 'FlashBias'; break;
        case '0025': $tag = 'SerialNumber'; break;
        case '0028': $tag = 'ColourEffect'; break;
        case '002a': $tag = 'BurstMode'; break;
        case '002b': $tag = 'SequenceNumber'; break;
        case '002c': $tag = 'Contrast'; break;
        case '002d': $tag = 'NoiseReduction'; break;
        case '002e': $tag = 'SelfTimer'; break;
        case '0030': $tag = 'Rotation'; break;
        case '0032': $tag = 'ColorMode'; break;
        case '0036': $tag = 'TravelDay'; break;
        default: $tag = 'unknown:' . $tag; break;
        }

        return $tag;
    }

    /**
     * Formats Data for the data type
     */
    protected function _formatData($type, $tag, $intel, $data)
    {
        switch ($type) {
        case 'UBYTE':
        case 'SBYTE':
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            $data = hexdec($data);
            if ($tag == '000f') {
                //AFMode
                switch ($data) {
                case 256:
                    $data = Horde_Image_Translation::t("9-area-focusing");
                    break;
                case 16:
                    $data = Horde_Image_Translation::t("1-area-focusing");
                    break;
                case 4096:
                    $data = Horde_Image_Translation::t("3-area-focusing (High speed)");
                    break;
                case 4112:
                    $data = Horde_Image_Translation::t("1-area-focusing (High speed)");
                    break;
                case 16:
                    $data = Horde_Image_Translation::t("1-area-focusing");
                    break;
                case 1:
                    $data = Horde_Image_Translation::t("Spot-focusing");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
            }
            break;

        case 'URATIONAL':
        case 'SRATIONAL':
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            $top = hexdec(substr($data, 8, 8));
            $bottom = hexdec(substr($data, 0, 8));
            if ($bottom != 0) {
                $data = $top / $bottom;
            } elseif ($top == 0) {
                $data = 0;
            } else {
                $data = $top . '/' . $bottom;
            }
            break;

        case 'USHORT':
        case  'SSHORT':
        case 'ULONG':
        case 'SLONG':
        case 'FLOAT':
        case 'DOUBLE':
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }

            $data = hexdec($data);
            switch ($tag) {
            case '0001':
                //Image Quality
                switch ($data) {
                case 2:
                    $data = Horde_Image_Translation::t("High");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("Standard");
                    break;
                case 6:
                    $data = Horde_Image_Translation::t("Very High");
                    break;
                case 7:
                    $data = Horde_Image_Translation::t("RAW");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '0003':
                //White Balance
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Auto");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Daylight");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("Cloudy");
                    break;
                case 4:
                    $data = Horde_Image_Translation::t("Halogen");
                    break;
                case 5:
                    $data = Horde_Image_Translation::t("Manual");
                    break;
                case 8:
                    $data = Horde_Image_Translation::t("Flash");
                    break;
                case 10:
                    $data = Horde_Image_Translation::t("Black and White");
                    break;
                case 11:
                    $data = Horde_Image_Translation::t("Manual");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown(%s)"), $data);
                    break;
                }
                break;

            case '0007':
                //Focus Mode
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Auto");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Manual");
                    break;
                case 4:
                    $data = Horde_Image_Translation::t("Auto, Focus button");
                    break;
                case 5:
                    $data = Horde_Image_Translation::t("Auto, Continuous");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown(%s)"), $data);
                    break;
                }
                break;

            case '001a':
                //Image Stabilizer
                switch ($data) {
                case 2:
                    $data = Horde_Image_Translation::t("Mode 1");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("Off");
                    break;
                case 4:
                    $data = Horde_Image_Translation::t("Mode 2");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown(%s)"), $data);
                    break;
                }
                break;

            case '001c':
                //Macro mode
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("On");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Off");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown(%s)"), $data);
                    break;
                }
                break;

            case '001f':
                //Shooting Mode
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Normal");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Portrait");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("Scenery");
                    break;
                case 4:
                    $data = Horde_Image_Translation::t("Sports");
                    break;
                case 5:
                    $data = Horde_Image_Translation::t("Night Portrait");
                    break;
                case 6:
                    $data = Horde_Image_Translation::t("Program");
                    break;
                case 7:
                    $data = Horde_Image_Translation::t("Aperture Priority");
                    break;
                case 8:
                    $data = Horde_Image_Translation::t("Shutter Priority");
                    break;
                case 9:
                    $data = Horde_Image_Translation::t("Macro");
                    break;
                case 11:
                    $data = Horde_Image_Translation::t("Manual");
                    break;
                case 13:
                    $data = Horde_Image_Translation::t("Panning");
                    break;
                case 14:
                    $data = Horde_Image_Translation::t("Simple");
                    break;
                case 18:
                    $data = Horde_Image_Translation::t("Fireworks");
                    break;
                case 19:
                    $data = Horde_Image_Translation::t("Party");
                    break;
                case 20:
                    $data = Horde_Image_Translation::t("Snow");
                    break;
                case 21:
                    $data = Horde_Image_Translation::t("Night Scenery");
                    break;
                case 22:
                    $data = Horde_Image_Translation::t("Food");
                    break;
                case 23:
                    $data = Horde_Image_Translation::t("Baby");
                    break;
                case 27:
                    $data = Horde_Image_Translation::t("High Sensitivity");
                    break;
                case 29:
                    $data = Horde_Image_Translation::t("Underwater");
                    break;
                case 33:
                    $data = Horde_Image_Translation::t("Pet");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown(%s)"), $data);
                    break;
                }
                break;

            case '0020':
                //Audio
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Yes");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("No");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '0023':
                //White Balance Bias
                $data = $data . ' EV';
                break;

            case '0024':
                //Flash Bias
                $data = $data;
                break;

            case '0028':
                //Colour Effect
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Off");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Warm");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("Cool");
                    break;
                case 4:
                    $data = Horde_Image_Translation::t("Black and White");
                    break;
                case 5:
                    $data = Horde_Image_Translation::t("Sepia");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '002a':
                //Burst Mode
                switch ($data) {
                case 0:
                    $data = Horde_Image_Translation::t("Off");
                    break;
                case 1:
                    $data = Horde_Image_Translation::t("Low/High Quality");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("Infinite");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '002c':
                //Contrast
                switch ($data) {
                case 0:
                    $data = Horde_Image_Translation::t("Standard");
                    break;
                case 1:
                    $data = Horde_Image_Translation::t("Low");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("High");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '002d':
                //Noise Reduction
                switch ($data) {
                case 0:
                    $data = Horde_Image_Translation::t("Standard");
                    break;
                case 1:
                    $data = Horde_Image_Translation::t("Low");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("High");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '002e':
                //Self Timer
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Off");
                    break;
                case 2:
                    $data = Horde_Image_Translation::t("10s");
                    break;
                case 3:
                    $data = Horde_Image_Translation::t("2s");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '0030':
                //Rotation
                switch ($data) {
                case 1:
                    $data = Horde_Image_Translation::t("Horizontal (normal)");
                    break;
                case 6:
                    $data = Horde_Image_Translation::t("Rotate 90 CW");
                    break;
                case 8:
                    $data = Horde_Image_Translation::t("Rotate 270 CW");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '0032':
                //Color Mode
                switch ($data) {
                case 0:
                    $data = Horde_Image_Translation::t("Normal");
                    break;
                case 1:
                    $data = Horde_Image_Translation::t("Natural");
                    break;
                default:
                    $data = sprintf(Horde_Image_Translation::t("Unknown (%s)"), $data);
                    break;
                }
                break;

            case '0036':
                //Travel Day
                $data = $data;
                break;
            }
            break;

        case 'UNDEFINED':
            break;

        default:
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            break;
        }

        return $data;
    }

    /**
     * Panasonic Special data section
     */
     public function parse($block, &$result)
     {
        $intel = true;
        $model = $result['IFD0']['Model'];
        //current place
        $place = 8;
        $offset = 8;

        $num = bin2hex(substr($block, $place, 4));
        $place += 4;

        if ($intel) {
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $result['SubIFD']['MakerNote']['Offset'] = hexdec($num);

        //Get number of tags (2 bytes)
        $num = bin2hex(substr($block, $place, 2));
        $place += 2;

        if ($intel) {
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);

        //loop thru all tags  Each field is 12 bytes
        for ($i = 0; $i < hexdec($num); $i++) {
            //2 byte tag
            $tag = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel) {
                $tag = Horde_Image_Exif::intel2Moto($tag);
            }
            $tag_name = $this->_lookupTag($tag);

            //2 byte type
            $type = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel) {
                $type = Horde_Image_Exif::intel2Moto($type);
            }
            $this->_lookupType($type, $size);

            //4 byte count of number of data units
            $count = bin2hex(substr($block, $place, 4));
            $place += 4;
            if ($intel) {
                $count = Horde_Image_Exif::intel2Moto($count);
            }
            $bytesofdata = $size * hexdec($count);

            //4 byte value of data or pointer to data
            $value = substr($block, $place, 4);
            $place += 4;

            if ($bytesofdata <= 4) {
                $data = $value;
            } else {
                $value = bin2hex($value);
                if ($intel) {
                    $value = Horde_Image_Exif::intel2Moto($value);
                }
                $data = substr($block, hexdec($value) - $offset, $bytesofdata * 2);
            }
            $formated_data = $this->_formatData($type, $tag, $intel, $data);
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
        }
    }
}