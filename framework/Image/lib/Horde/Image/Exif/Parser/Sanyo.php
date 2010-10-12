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
class Horde_Image_Exif_Parser_Sanyo extends Horde_Image_Exif_Parser_Base
{
    /**
     *
     * @param $tag
     * @return unknown_type
     */
    protected function _lookupTag($tag)
    {
        switch($tag) {
        case '0200': $tag = 'SpecialMode'; break;
        case '0201': $tag = 'Quality'; break;
        case '0202': $tag = 'Macro'; break;
        case '0203': $tag = 'Unknown'; break;
        case '0204': $tag = 'DigiZoom'; break;
        case '0f00': $tag = 'DataDump'; break;
        default:     $tag = 'unknown:' . $tag; break;
        }

        return $tag;
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
            if ($intel) {
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

            switch ($tag) {
            case '0200':
                //SpecialMode
                $data = $data == 0 ? $this->_dict->t("Normal") : $this->_dict->t("Unknown") . ': ' . $data;
                break;
            case '0201':
                //Quality
                $data = $data == 2 ? $this->_dict->t("High") : $this->_dict->t("Unknown") . ': ' . $data;
                break;
            case '0202':
                //Macro
                $data = $data == 0 ? $this->_dict->t("Normal") : $this->_dict->t("Unknown") . ': ' . $data;
                break;
            }
            break;

        default:
            $data = bin2hex($data);
            if ($intel) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }
        }

        return $data;
    }

    /**
     *
     * @param $block
     * @param $result
     * @param $seek
     * @param $globalOffset
     * @return unknown_type
     */
    public function parse($block, &$result, $seek, $globalOffset)
    {
        $intel = $result['Endien']=='Intel';
        $model = $result['IFD0']['Model'];
        //current place
        $place = 8;
        $offset = 8;

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
                //offsets are from TIFF header which is 12 bytes from the start
                //of the file
                $v = fseek($seek, $globalOffset + hexdec($value));
                if ($v == 0) {
                    $data = fread($seek, $bytesofdata);
                } elseif ($v == -1) {
                    $result['Errors'] = $result['Errors']++;
                }
            }
            $formated_data = $this->_formatData($type, $tag, $intel, $data);
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
        }
    }
}
