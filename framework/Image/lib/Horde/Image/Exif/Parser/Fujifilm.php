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
class Horde_Image_Exif_Parser_Fujifilm extends Horde_Image_Exif_Parser_Base
{
    /**
     * Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
     */
    protected function _lookupTag($tag)
    {
        switch($tag) {
        case '0000': return 'Version';
        case '1000': return 'Quality';
        case '1001': return 'Sharpness';
        case '1002': return 'WhiteBalance';
        case '1003': return 'Color';
        case '1004': return 'Tone';
        case '1010': return 'FlashMode';
        case '1011': return 'FlashStrength';
        case '1020': return 'Macro';
        case '1021': return 'FocusMode';
        case '1030': return 'SlowSync';
        case '1031': return 'PictureMode';
        case '1032': return 'Unknown';
        case '1100': return 'ContinuousTakingBracket';
        case '1200': return 'Unknown';
        case '1300': return 'BlurWarning';
        case '1301': return 'FocusWarning';
        case '1302': return 'AEWarning';
        default:     return 'unknown:' . $tag;
        }
    }

    /**
     *
     * @param $type
     * @param $tag
     * @param $intel
     * @param $data
     * @return unknown_type
     */
    protected function _formatData($type, $tag, $intel, $data)
    {
        switch ($type) {
        case 'ASCII':
        case 'UNDEFINED':
            break;

        case 'URATIONAL':
        case 'SRATIONAL':
            $data = bin2hex($data);
            if ($intel == 1) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
            $top = hexdec(substr($data, 8, 8));
            $bottom = hexdec(substr($data, 0, 8));
            if ($bottom) {
                $data = $top / $bottom;
            } elseif (!$top) {
                $data = 0;
            } else {
                $data = $top . '/' . $bottom;
            }

            if ($tag == '1011') {
                //FlashStrength
                $data = $data . ' EV';
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

            switch ($tag) {
            case '1001':
                //Sharpness
                switch ($data) {
                case 1:  $data = _("Soft"); break;
                case 2:  $data = _("Soft"); break;
                case 3:  $data = _("Normal"); break;
                case 4:  $data = _("Hard"); break;
                case 5:  $data = _("Hard"); break;
                default: $data = _("Unknown") . ': ' . $data; break;
                }
                break;

            case '1002':
                //WhiteBalance
                switch ($data) {
                case 0:    $data = _("Auto"); break;
                case 256:  $data = _("Daylight"); break;
                case 512:  $data = _("Cloudy"); break;
                case 768:  $data = _("DaylightColor-fluorescence"); break;
                case 769:  $data = _("DaywhiteColor-fluorescence"); break;
                case 770:  $data = _("White-fluorescence"); break;
                case 1024: $data = _("Incandescense"); break;
                case 3840: $data = _("Custom"); break;
                default:   $data = _("Unknown") . ': ' . $data; break;
                }
                break;

            case '1003':
                //Color
                switch ($data) {
                case 0:   $data = _("Chroma Saturation Normal(STD)"); break;
                case 256: $data = _("Chroma Saturation High"); break;
                case 512: $data = _("Chroma Saturation Low(ORG)"); break;
                default:  $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1004':
                //Tone
                switch ($data) {
                case 0: $data = _("Contrast Normal(STD)"); break;
                case 256: $data = _("Contrast High(HARD)"); break;
                case 512: $data = _("Contrast Low(ORG)"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1010':
                //FlashMode
                switch ($data) {
                case 0:  $data = _("Auto"); break;
                case 1:  $data = _("On"); break;
                case 2:  $data = _("Off"); break;
                case 3:  $data = _("Red-Eye Reduction"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1020':
                //Macro
                switch ($data) {
                case 0:  $data = _("Off"); break;
                case 1:  $data = _("On"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1021':
                //FocusMode
                switch ($data) {
                case 0:  $data = _("Auto"); break;
                case 1:  $data = _("Manual"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1030':
                //SlowSync
                switch ($data) {
                case 0:  $data = _("Off"); break;
                case 1:  $data = _("On"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1031':
                //PictureMode
                switch ($data) {
                case 0:  $data = _("Auto"); break;
                case 1:  $data = _("Portrait"); break;
                case 2:  $data = _("Landscape"); break;
                case 4:  $data = _("Sports"); break;
                case 5:  $data = _("Night"); break;
                case 6:  $data = _("Program AE"); break;
                case 256:  $data = _("Aperture Prority AE"); break;
                case 512:  $data = _("Shutter Priority"); break;
                case 768:  $data = _("Manual Exposure"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1100':
                //ContinuousTakingBracket
                switch ($data) {
                case 0:  $data = _("Off"); break;
                case 1:  $data = _("On"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1300':
                //BlurWarning
                switch ($data) {
                case 0:  $data = _("No Warning"); break;
                case 1:  $data = _("Warning"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1301':
                //FocusWarning
                switch ($data) {
                case 0:  $data = _("Auto Focus Good"); break;
                case 1:  $data = _("Out of Focus"); break;
                default: $data = _("Unknown: ") . $data; break;
                }
                break;

            case '1302':
                //AEWarning
                switch ($data) {
                case 0:  $data = _("AE Good"); break;
                case 1:  $data = _("Over Exposure"); break;
                default: $data = _("Unknown: ") . $data; break;
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
     *
     * @param $block
     * @param $result
     * @return unknown_type
     */
    public function parse($block, &$result)
    {
        $intel = 1;
        $model = $result['IFD0']['Model'];

        //current place
        $place = 8;
        $offset = 8;

        $num = bin2hex(substr($block, $place, 4));
        $place += 4;
        if ($intel == 1) {
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $result['SubIFD']['MakerNote']['Offset'] = hexdec($num);

        //Get number of tags (2 bytes)
        $num = bin2hex(substr($block, $place, 2));
        $place += 2;
        if ($intel == 1) {
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);

        //loop thru all tags  Each field is 12 bytes
        for ($i = 0; $i < hexdec($num); $i++) {
            //2 byte tag
            $tag = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel == 1) {
                $tag = Horde_Image_Exif::intel2Moto($tag);
            }
            $tag_name = $this->_lookupTag($tag);

            //2 byte type
            $type = bin2hex(substr($block, $place, 2));
            $place += 2;
            if ($intel == 1) {
                $type = Horde_Image_Exif::intel2Moto($type);
            }
            $this->_lookupType($type, $size);

            //4 byte count of number of data units
            $count = bin2hex(substr($block, $place, 4));
            $place += 4;
            if ($intel == 1) {
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
                if ($intel == 1) {
                    $value = Horde_Image_Exif::intel2Moto($value);
                }
                $data = substr($block, hexdec($value) - $offset, $bytesofdata * 2);
            }
            $formated_data = $this->_formatData($type, $tag, $intel, $data);
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
        }
    }
}
