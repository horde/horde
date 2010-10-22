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
class Horde_Image_Exif_Parser_Nikon extends Horde_Image_Exif_Parser_Base
{
    /**
     *
     * @param $tag
     * @param $model
     * @return unknown_type
     */
    protected function _lookupTag($tag, $model)
    {
        switch ($model) {
        case 0:
            switch($tag) {
            case '0003': $tag = 'Quality'; break;
            case '0004': $tag = 'ColorMode'; break;
            case '0005': $tag = 'ImageAdjustment'; break;
            case '0006': $tag = 'CCDSensitivity'; break;
            case '0007': $tag = 'WhiteBalance'; break;
            case '0008': $tag = 'Focus'; break;
            case '0009': $tag = 'Unknown2'; break;
            case '000a': $tag = 'DigitalZoom'; break;
            case '000b': $tag = 'Converter'; break;
            default:     $tag = 'unknown: ' . $tag; break;
            }
            break;

        case 1:
            switch($tag) {
            case '0002': $tag = 'ISOSetting'; break;
            case '0003': $tag = 'ColorMode'; break;
            case '0004': $tag = 'Quality'; break;
            case '0005': $tag = 'Whitebalance'; break;
            case '0006': $tag = 'ImageSharpening'; break;
            case '0007': $tag = 'FocusMode'; break;
            case '0008': $tag = 'FlashSetting'; break;
            case '0009': $tag = 'FlashMode'; break;
            case '000b': $tag = 'WhiteBalanceFine'; break;
            case '000f': $tag = 'ISOSelection'; break;
            case '0013': $tag = 'ISOSelection2'; break;
            case '0080': $tag = 'ImageAdjustment'; break;
            case '0081': $tag = 'ToneCompensation'; break;
            case '0082': $tag = 'Adapter'; break;
            case '0083': $tag = 'LensType'; break;
            case '0084': $tag = 'LensInfo'; break;
            case '0085': $tag = 'ManualFocusDistance'; break;
            case '0086': $tag = 'DigitalZoom'; break;
            case '0087': $tag = 'FlashUsed'; break;
            case '0088': $tag = 'AFFocusPosition'; break;
            case '008d': $tag = 'ColorMode'; break;
            case '0090': $tag = 'LightType'; break;
            case '0094': $tag = 'Saturation'; break;
            case '0095': $tag = 'NoiseReduction'; break;
            case '0010': $tag = 'DataDump'; break;
            default:     $tag = 'unknown: ' . $tag; break;
            }
            break;
        }

        return $tag;
    }

    /**
     *
     * @param $type
     * @param $tag
     * @param $intel
     * @param $model
     * @param $data
     * @return unknown_type
     */
    protected function _formatData($type, $tag, $intel, $model, $data)
    {
        switch ($type) {
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

            if ($tag == '0085' && $model == 1) {
                //ManualFocusDistance
                $data = $data . ' m';
            }
            if ($tag == '0086' && $model == 1) {
                //DigitalZoom
                $data = $data . 'x';
            }
            if ($tag == '000a' && $model == 0) {
                //DigitalZoom
                $data = $data . 'x';
            }
            break;

        case 'USHORT':
        case 'SSHORT':
        case 'ULONG':
        case 'SLONG':
        case 'FLOAT':
        case 'DOUBLE':
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            $data = hexdec($data);
            if ($model != 0) {
                break;
            }

            switch ($tag) {
            case '0003':
                //Quality
                switch ($data) {
                case 1:  $data = Horde_Image_Translation::t("VGA Basic"); break;
                case 2:  $data = Horde_Image_Translation::t("VGA Normal"); break;
                case 3:  $data = Horde_Image_Translation::t("VGA Fine"); break;
                case 4:  $data = Horde_Image_Translation::t("SXGA Basic"); break;
                case 5:  $data = Horde_Image_Translation::t("SXGA Normal"); break;
                case 6:  $data = Horde_Image_Translation::t("SXGA Fine"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '0004':
                //Color
                switch ($data) {
                case 1:  $data = Horde_Image_Translation::t("Color"); break;
                case 2:  $data = Horde_Image_Translation::t("Monochrome"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '0005':
                //Image Adjustment
                switch ($data) {
                case 0:  $data = Horde_Image_Translation::t("Normal"); break;
                case 1:  $data = Horde_Image_Translation::t("Bright+"); break;
                case 2:  $data = Horde_Image_Translation::t("Bright-"); break;
                case 3:  $data = Horde_Image_Translation::t("Contrast+"); break;
                case 4:  $data = Horde_Image_Translation::t("Contrast-"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '0006':
                //CCD Sensitivity
                switch ($data) {
                case 0:  $data = 'ISO-80'; break;
                case 2:  $data = 'ISO-160'; break;
                case 4:  $data = 'ISO-320'; break;
                case 5:  $data = 'ISO-100'; break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '0007':
                //White Balance
                switch ($data) {
                case 0:  $data = Horde_Image_Translation::t("Auto"); break;
                case 1:  $data = Horde_Image_Translation::t("Preset"); break;
                case 2:  $data = Horde_Image_Translation::t("Daylight"); break;
                case 3:  $data = Horde_Image_Translation::t("Incandescense"); break;
                case 4:  $data = Horde_Image_Translation::t("Flourescence"); break;
                case 5:  $data = Horde_Image_Translation::t("Cloudy"); break;
                case 6:  $data = Horde_Image_Translation::t("SpeedLight"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '000b':
                //Converter
                switch ($data) {
                case 0:  $data = Horde_Image_Translation::t("None"); break;
                case 1:  $data = Horde_Image_Translation::t("Fisheye"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;
            }

        case 'UNDEFINED':
            if ($model != 1) {
                break;
            }

            switch ($tag) {
            case '0001':
                $data = $data/100;
                break;
            case '0088':
                //AF Focus Position
                $temp = Horde_Image_Translation::t("Center");
                $data = bin2hex($data);
                $data = str_replace('01', 'Top', $data);
                $data = str_replace('02', 'Bottom', $data);
                $data = str_replace('03', 'Left', $data);
                $data = str_replace('04', 'Right', $data);
                $data = str_replace('00', '', $data);
                if (!strlen($data)) {
                    $data = $temp;
                }
                break;
            }
            break;

        default:
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            if ($model != 1) {
                break;
            }

            switch ($tag) {
            case '0083':
                //Lens Type
                $data = hexdec(substr($data, 0, 2));
                switch ($data) {
                case 0:  $data = Horde_Image_Translation::t("AF non D"); break;
                case 1:  $data = Horde_Image_Translation::t("Manual"); break;
                case 2:  $data = 'AF-D or AF-S'; break;
                case 6:  $data = 'AF-D G'; break;
                case 10:  $data = 'AF-D VR'; break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;

            case '0087':
                //Flash type
                $data = hexdec(substr($data,0,2));
                switch ($data) {
                case 0:  $data = Horde_Image_Translation::t("Did Not Fire"); break;
                case 4:  $data = Horde_Image_Translation::t("Unknown"); break;
                case 7:  $data = Horde_Image_Translation::t("External"); break;
                case 9:  $data = Horde_Image_Translation::t("On Camera"); break;
                default: $data = Horde_Image_Translation::t("Unknown") . ': ' . $data; break;
                }
                break;
            }

            break;
        }

        return $data;
    }

    /**
     *
     * @param $block
     * @param $result
     * @return unknown_type
     */
    public function parse($block, &$result)
    {
        $intel = $result['Endien'] == 'Intel';
        $model = $result['IFD0']['Model'];

        //these 6 models start with "Nikon".  Other models dont.
        if ($model == "E700\0" ||
            $model == "E800\0" ||
            $model == "E900\0" ||
            $model == "E900S\0" ||
            $model == "E910\0" ||
            $model == "E950\0") {
            //current place
            $place = 8;
            $model = 0;

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
                $tag_name = $this->_lookupTag($tag, $model);

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

                //if tag is 0002 then its the ASCII value which we know is at 140 so calc offset
                //THIS HACK ONLY WORKS WITH EARLY NIKON MODELS
                if ($tag == '0002') {
                    $offset = hexdec($value) - 140;
                }
                if ($bytesofdata <= 4) {
                    $data = $value;
                } else {
                    $value = bin2hex($value);
                    if ($intel) {
                        $value = Horde_Image_Exif::intel2Moto($value);
                    }
                    $data = substr($block, hexdec($value) - $offset, $bytesofdata * 2);
                }
                $formated_data = $this->_formatData($type, $tag, $intel, $model, $data);
                $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
            }
        } else {
            //current place
            $place = 0;
            $model = 1;

            $nikon = substr($block, $place, 8);
            $place += 8;
            $endien = substr($block, $place, 4);
            $place += 4;

            //2 bytes of 0x002a
            $tag = bin2hex(substr($block, $place, 2));
            $place += 2;

            //Then 4 bytes of offset to IFD0 (usually 8 which includes all 8
            //bytes of TIFF header)
            $offset = bin2hex(substr($block, $place, 4));
            $place += 4;
            if ($intel) {
                $offset = Horde_Image_Exif::intel2Moto($offset);
            }
            if (hexdec($offset) > 8) {
                $place += $offset - 8;
            }

            //Get number of tags (2 bytes)
            $num = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel) {
                $num = Horde_Image_Exif::intel2Moto($num);
            }

            //loop thru all tags  Each field is 12 bytes
            for ($i = 0; $i < hexdec($num); $i++) {
                //2 byte tag
                $tag = bin2hex(substr($block, $place, 2));
                $place += 2;
                if ($intel) {
                    $tag = Horde_Image_Exif::intel2Moto($tag);
                }
                $tag_name = $this->_lookupTag($tag, $model);

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
                    $data = substr($block, hexdec($value) + hexdec($offset) + 2, $bytesofdata);
                }
                $formated_data = $this->_formatData($type, $tag, $intel, $model, $data);
                $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
            }
        }
    }
}