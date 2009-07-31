<?php
/**
 *   Exifer
 *   Extracts EXIF information from digital photos.
 *
 *   Copyright 2003 Jake Olefsky
 *   http://www.offsky.com/software/exif/index.php
 *   jake@olefsky.com
 *
 *   Please see exif.php for the complete information about this software.
 *
 *   ------------
 *
 *   This program is free software; you can redistribute it and/or modify it under the terms of
 *   the GNU General Public License as published by the Free Software Foundation; either version 2
 *   of the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 *   without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *   See the GNU General Public License for more details. http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
 */
function lookup_Panasonic_tag($tag)
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
function formatPanasonicData($type,$tag,$intel,$data)
{
    if ($type == 'UBYTE' || $type == 'SBYTE') {
        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
        $data = hexdec($data);
        if ($tag == '000f') { //AFMode
            if ($data == 256) {
                $data = _("9-area-focusing");
            } elseif ($data == 16) {
                $data = _("1-area-focusing");
            } elseif ($data == 4096) {
                $data = _("3-area-focusing (High speed)");
            } elseif ($data == 4112) {
                $data = _("1-area-focusing (High speed)");
            } elseif ($data == 16) {
                $data = _("1-area-focusing");
            } elseif ($data == 1) {
                $data = _("Spot-focusing");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }

    } elseif ($type == 'URATIONAL' || $type == 'SRATIONAL') {
        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
        $top = hexdec(substr($data, 8, 8));
        $bottom = hexdec(substr($data, 0, 8));
        if ($bottom!=0) {
            $data = $top / $bottom;
        } elseif ($top == 0) {
            $data = 0;
        } else {
            $data = $top . '/' . $bottom;
        }

    } elseif ($type == 'USHORT' || $type == 'SSHORT' || $type == 'ULONG' ||
              $type == 'SLONG' || $type == 'FLOAT' || $type == 'DOUBLE') {

        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }

        $data = hexdec($data);
        if ($tag == '0001') { //Image Quality
            if ($data == 2) {
                $data = _("High");
            } elseif ($data == 3) {
                $data = _("Standard");
            } elseif ($data == 6) {
                $data = _("Very High");
            } elseif ($data == 7) {
                $data = _("RAW");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '0003') { //White Balance
            if ($data == 1) {
                $data = _("Auto");
            } elseif ($data == 2) {
                $data = _("Daylight");
            } elseif ($data == 3) {
                $data = _("Cloudy");
            } elseif ($data == 4) {
                $data = _("Halogen");
            } elseif ($data == 5) {
                $data = _("Manual");
            } elseif ($data == 8) {
                $data = _("Flash");
            } elseif ($data == 10) {
                $data = _("Black and White");
            } elseif ($data == 11) {
                $data = _("Manual");
            } else {
                $data = sprintf(_("Unknown(%s)"), $data);
            }
        }
        if ($tag=='0007') { //Focus Mode
            if ($data == 1) {
                $data = _("Auto");
            } elseif ($data == 2) {
                $data = _("Manual");
            } elseif ($data == 4) {
                $data = _("Auto, Focus button");
            } elseif ($data == 5) {
                $data = _("Auto, Continuous");
            } else {
                $data = sprintf(_("Unknown(%s)"), $data);
            }
        }
        if ($tag == '001a') { //Image Stabilizer
            if ($data == 2) {
                $data = _("Mode 1");
            } elseif ($data == 3) {
                $data = _("Off");
            } elseif ($data == 4) {
                $data = _("Mode 2");
            } else {
                $data = sprintf(_("Unknown(%s)"), $data);
            }
        }
        if ($tag == '001c') { //Macro mode
            if ($data == 1) {
                $data = _("On");
            } elseif ($data == 2) {
                $data = _("Off");
            } else {
                $data = sprintf(_("Unknown(%s)"), $data);
            }
        }
        if ($tag == '001f') { //Shooting Mode
            if ($data == 1) {
                $data = _("Normal");
            } elseif ($data == 2) {
                $data = _("Portrait");
            } elseif ($data == 3) {
                $data = _("Scenery");
            } elseif ($data == 4) {
                $data = _("Sports");
            } elseif ($data == 5) {
                $data = _("Night Portrait");
            } elseif ($data == 6) {
                $data = _("Program");
            } elseif ($data == 7) {
                $data = _("Aperture Priority");
            } elseif ($data == 8) {
                $data = _("Shutter Priority");
            } elseif ($data == 9) {
                $data = _("Macro");
            } elseif ($data == 11) {
                $data = _("Manual");
            } elseif ($data == 13) {
                $data = _("Panning");
            } elseif ($data == 14) {
                $data = _("Simple");
            } elseif ($data == 18) {
                $data = _("Fireworks");
            } elseif ($data == 19) {
                $data = _("Party");
            } elseif ($data == 20) {
                $data = _("Snow");
            } elseif ($data == 21) {
                $data = _("Night Scenery");
            } elseif ($data == 22) {
                $data = _("Food");
            } elseif ($data == 23) {
                $data = _("Baby");
            } elseif ($data == 27) {
                $data = _("High Sensitivity");
            } elseif ($data == 29) {
                $data = _("Underwater");
            } elseif ($data == 33) {
                $data = _("Pet");
            } else {
                $data = sprintf(_("Unknown(%s)"), $data);
            }
        }
        if ($tag == '0020') { //Audio
            if ($data == 1) {
                $data = _("Yes");
            } elseif ($data == 2) {
                $data = _("No");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '0023') { //White Balance Bias
            $data = $data . ' EV';
        }
        if ($tag == '0024') { //Flash Bias
            $data = $data;
        }
        if ($tag == '0028') { //Colour Effect
            if ($data == 1) {
                $data = _("Off");
            } elseif ($data == 2) {
                $data = _("Warm");
            } elseif ($data == 3) {
                $data = _("Cool");
            } elseif ($data == 4) {
                $data = _("Black and White");
            } elseif ($data == 5) {
                $data = _("Sepia");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '002a') { //Burst Mode
            if ($data == 0) {
                $data = _("Off");
            } elseif ($data == 1) {
                $data = _("Low/High Quality");
            } elseif ($data == 2) {
                $data = _("Infinite");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '002c') { //Contrast
            if ($data == 0) {
                $data = _("Standard");
            } elseif ($data == 1) {
                $data = _("Low");
            } elseif ($data == 2) {
                $data = _("High");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '002d') { //Noise Reduction
            if ($data == 0) {
                $data = _("Standard");
            } elseif ($data == 1) {
                $data = _("Low");
            } elseif ($data == 2) {
                $data = _("High");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '002e') { //Self Timer
            if ($data == 1) {
                $data = _("Off");
            } elseif ($data == 2) {
                $data = _("10s");
            } elseif ($data == 3) {
                $data = _("2s");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '0030') { //Rotation
            if ($data == 1) {
                $data = _("Horizontal (normal)");
            } elseif ($data == 6) {
                $data = _("Rotate 90 CW");
            } elseif ($data == 8) {
                $data = _("Rotate 270 CW");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '0032') { //Color Mode
            if ($data == 0) {
                $data = _("Normal");
            } elseif ($data == 1) {
                $data = _("Natural");
            } else {
                $data = sprintf(_("Unknown (%s)"), $data);
            }
        }
        if ($tag == '0036') { //Travel Day
            $data = $data;
        }
    } elseif ($type != "UNDEFINED") {
        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
    }

    return $data;
}

/**
 * Panasonic Special data section
 */
function parsePanasonic($block, &$result)
 {
    $intel = 1;
    $model = $result['IFD0']['Model'];
    $place = 8; //current place
    $offset = 8;

    $num = bin2hex(substr($block, $place, 4));
    $place += 4;

    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['Offset'] = hexdec($num);

    //Get number of tags (2 bytes)
    $num = bin2hex(substr($block, $place, 2));
    $place+=2;

    if ($intel == 1) {
        $num = intel2Moto($num);
    }
    $result['SubIFD']['MakerNote']['MakerNoteNumTags'] = hexdec($num);

    //loop thru all tags  Each field is 12 bytes
    for($i = 0; $i < hexdec($num); $i++) {
        //2 byte tag
        $tag = bin2hex(substr($block, $place, 2));
        $place += 2;
        if ($intel == 1) {
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_Panasonic_tag($tag);

        //2 byte type
        $type = bin2hex(substr($block, $place, 2));
        $place += 2;
        if ($intel == 1) {
            $type = intel2Moto($type);
        }
        lookup_type($type, $size);

        //4 byte count of number of data units
        $count = bin2hex(substr($block, $place, 4));
        $place += 4;
        if ($intel == 1) {
            $count = intel2Moto($count);
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
                $value = intel2Moto($value);
            }
            $data = substr($block, hexdec($value) - $offset, $bytesofdata * 2);
        }
        $formated_data = formatPanasonicData($type, $tag, $intel, $data);
        $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
    }

}
