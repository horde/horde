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
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License
 * for more details. http://www.gnu.org/copyleft/gpl.html
 */
class Horde_Image_Exif_Parser_Canon extends Horde_Image_Exif_Parser_Base
{
    /**
     * Looks up the name of the tag for the MakerNote (Depends on Manufacturer)
     */
    protected function _lookupTag($tag)
    {
        switch($tag) {
        case '0001': $tag = 'Settings 1'; break;
        case '0004': $tag = 'Settings 4'; break;
        case '0006': $tag = 'ImageType'; break;
        case '0007': $tag = 'FirmwareVersion'; break;
        case '0008': $tag = 'ImageNumber'; break;
        case '0009': $tag = 'OwnerName'; break;
        case '000c': $tag = 'CameraSerialNumber'; break;
        case '000f': $tag = 'CustomFunctions'; break;
        default:     $tag = sprintf(_("Unknown: (%s)"), $tag); break;
        }

        return $tag;
    }

    /**
     * Formats Data for the data type
     */
    protected function _formatData($type, $tag, $intel, $data, $exif, &$result)
    {
        $place = 0;

        switch ($type) {
        case 'ASCII':
            $result = $data = str_replace('\0', '', $data);
            break;

        case 'URATIONAL':
        case 'SRATIONAL':
            $data = bin2hex($data);
            if ($intel == 1) {
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

            if ($tag == '0204') {
                //DigitalZoom
                $data = $data . 'x';
            }
        case 'USHORT':
        case 'SSHORT':
        case 'ULONG':
        case 'SLONG':
        case 'FLOAT':
        case 'DOUBLE':
            $data = bin2hex($data);
            $result['RAWDATA'] = $data;

            // TODO: split this code up
            switch ($tag) {
            case '0001':
                //first chunk
                $result['Bytes'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;
                if ($result['Bytes'] != strlen($data) / 2) {
                    //Bad chunk
                    return $result;
                }
                $result['Macro'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//1
                switch($result['Macro']) {
                case 1: $result['Macro'] = _("Macro"); break;
                case 2: $result['Macro'] = _("Normal"); break;
                default: $result['Macro'] = _("Unknown");
                }
                $result['SelfTimer'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//2
                switch($result['SelfTimer']) {
                case 0: $result['SelfTimer'] = _("Off"); break;
                default: $result['SelfTimer'] .= _("/10s");
                }
                $result['Quality'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//3
                switch($result['Quality']) {
                case 2: $result['Quality'] = _("Normal"); break;
                case 3: $result['Quality'] = _("Fine"); break;
                case 5: $result['Quality'] = _("Superfine"); break;
                default: $result['Quality'] = _("Unknown");
                }
                $result['Flash'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//4
                switch($result['Flash']) {
                case 0: $result['Flash'] = _("Off"); break;
                case 1: $result['Flash'] = _("Auto"); break;
                case 2: $result['Flash'] = _("On"); break;
                case 3: $result['Flash'] = _("Red Eye Reduction"); break;
                case 4: $result['Flash'] = _("Slow Synchro"); break;
                case 5: $result['Flash'] = _("Auto + Red Eye Reduction"); break;
                case 6: $result['Flash'] = _("On + Red Eye Reduction"); break;
                case 16: $result['Flash'] = _("External Flash"); break;
                default: $result['Flash'] = _("Unknown");
                }
                $result['DriveMode'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//5
                switch($result['DriveMode']) {
                case 0: $result['DriveMode'] = _("Single/Timer"); break;
                case 1: $result['DriveMode'] = _("Continuous"); break;
                default: $result['DriveMode'] = _("Unknown");
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//6
                $result['FocusMode'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//7
                switch($result['FocusMode']) {
                case 0: $result['FocusMode'] = _("One-Shot"); break;
                case 1: $result['FocusMode'] = _("AI Servo"); break;
                case 2: $result['FocusMode'] = _("AI Focus"); break;
                case 3: $result['FocusMode'] = _("Manual Focus"); break;
                case 4: $result['FocusMode'] = _("Single"); break;
                case 5: $result['FocusMode'] = _("Continuous"); break;
                case 6: $result['FocusMode'] = _("Manual Focus"); break;
                default: $result['FocusMode'] = _("Unknown");
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//8
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place,4 )));
                $place+=4;//9
                $result['ImageSize'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//10
                switch($result['ImageSize']) {
                case 0: $result['ImageSize'] = _("Large"); break;
                case 1: $result['ImageSize'] = _("Medium"); break;
                case 2: $result['ImageSize'] = _("Small"); break;
                default: $result['ImageSize'] = _("Unknown");
                }
                $result['EasyShooting'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//11
                switch($result['EasyShooting']) {
                case 0: $result['EasyShooting'] = _("Full Auto"); break;
                case 1: $result['EasyShooting'] = _("Manual"); break;
                case 2: $result['EasyShooting'] = _("Landscape"); break;
                case 3: $result['EasyShooting'] = _("Fast Shutter"); break;
                case 4: $result['EasyShooting'] = _("Slow Shutter"); break;
                case 5: $result['EasyShooting'] = _("Night"); break;
                case 6: $result['EasyShooting'] = _("Black & White"); break;
                case 7: $result['EasyShooting'] = _("Sepia"); break;
                case 8: $result['EasyShooting'] = _("Portrait"); break;
                case 9: $result['EasyShooting'] = _("Sport"); break;
                case 10: $result['EasyShooting'] = _("Macro/Close-Up"); break;
                case 11: $result['EasyShooting'] = _("Pan Focus"); break;
                default: $result['EasyShooting'] = _("Unknown");
                }
                $result['DigitalZoom'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//12
                switch($result['DigitalZoom']) {
                case 0:
                case 65535: $result['DigitalZoom'] = _("None"); break;
                case 1: $result['DigitalZoom'] = _("2x"); break;
                case 2: $result['DigitalZoom'] = _("4x"); break;
                default: $result['DigitalZoom'] = _("Unknown");
                }
                $result['Contrast'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//13
                switch($result['Contrast']) {
                case 0: $result['Contrast'] = _("Normal"); break;
                case 1: $result['Contrast'] = _("High"); break;
                case 65535: $result['Contrast'] = _("Low"); break;
                default: $result['Contrast'] = _("Unknown");
                }
                $result['Saturation'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//14
                switch($result['Saturation']) {
                   case 0: $result['Saturation'] = _("Normal"); break;
                    case 1: $result['Saturation'] = _("High"); break;
                    case 65535: $result['Saturation'] = _("Low"); break;
                    default: $result['Saturation'] = _("Unknown");
                }
                $result['Sharpness'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//15
                switch($result['Sharpness']) {
                case 0: $result['Sharpness'] = _("Normal"); break;
                case 1: $result['Sharpness'] = _("High"); break;
                case 65535: $result['Sharpness'] = _("Low"); break;
                default: $result['Sharpness'] = _("Unknown");
                }
                $result['ISO'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//16
                switch($result['ISO']) {
                case 32767:
                case 0:
                    $result['ISO'] = isset($exif['SubIFD']['ISOSpeedRatings']) ?
                        $exif['SubIFD']['ISOSpeedRatings'] :
                        'Unknown';
                     break;
                case 15:
                    $result['ISO'] = _("Auto");
                    break;
                case 16:
                    $result['ISO'] = 50;
                     break;
                case 17:
                    $result['ISO'] = 100;
                     break;
                case 18:
                    $result['ISO'] = 200;
                     break;
                case 19:
                    $result['ISO'] = 400;
                     break;
                default:
                    $result['ISO'] = _("Unknown");
                }
                $result['MeteringMode'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//17
                switch($result['MeteringMode']) {
                case 3: $result['MeteringMode'] = _("Evaluative"); break;
                case 4: $result['MeteringMode'] = _("Partial"); break;
                case 5: $result['MeteringMode'] = _("Center-weighted"); break;
                default: $result['MeteringMode'] = _("Unknown");
                }
                $result['FocusType'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//18
                switch($result['FocusType']) {
                case 0: $result['FocusType'] = _("Manual"); break;
                case 1: $result['FocusType'] = _("Auto"); break;
                case 3: $result['FocusType'] = _("Close-up (Macro)"); break;
                case 8: $result['FocusType'] = _("Locked (Pan Mode)"); break;
                default: $result['FocusType'] = _("Unknown");
                }
                $result['AFPointSelected'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//19
                switch($result['AFPointSelected']) {
                case 12288: $result['AFPointSelected'] = _("Manual Focus"); break;
                case 12289: $result['AFPointSelected'] = _("Auto Selected"); break;
                case 12290: $result['AFPointSelected'] = _("Right"); break;
                case 12291: $result['AFPointSelected'] = _("Center"); break;
                case 12292: $result['AFPointSelected'] = _("Left"); break;
                default: $result['AFPointSelected'] = _("Unknown");
                }
                $result['ExposureMode'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//20
                switch($result['ExposureMode']) {
                case 0: $result['ExposureMode'] = _("EasyShoot"); break;
                case 1: $result['ExposureMode'] = _("Program"); break;
                case 2: $result['ExposureMode'] = _("Tv"); break;
                case 3: $result['ExposureMode'] = _("Av"); break;
                case 4: $result['ExposureMode'] = _("Manual"); break;
                case 5: $result['ExposureMode'] = _("Auto-DEP"); break;
                default: $result['ExposureMode'] = _("Unknown");
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//21
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//22
                $result['LongFocalLength'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//23
                $result['LongFocalLength'] .=  'focal units';
                $result['ShortFocalLength'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//24
                $result['ShortFocalLength'] .= ' focal units';
                $result['FocalUnits'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//25
                 $result['FocalUnits'] .= ' per mm';
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//26
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//27
                $result['FlashActivity'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//28
                switch($result['FlashActivity']) {
                case 0: $result['FlashActivity'] = _("Flash Did Not Fire"); break;
                case 1: $result['FlashActivity'] = _("Flash Fired"); break;
                default: $result['FlashActivity'] = _("Unknown");
                }
                $result['FlashDetails'] = str_pad(base_convert(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)), 16, 2), 16, '0', STR_PAD_LEFT);
                $place += 4;//29
                $flashDetails = array();
                if (substr($result['FlashDetails'], 1, 1) == 1) {
                    $flashDetails[] = _("External E-TTL");
                }
                if (substr($result['FlashDetails'], 2, 1) == 1) {
                    $flashDetails[] = _("Internal Flash");
                }
                if (substr($result['FlashDetails'], 4, 1) == 1) {
                    $flashDetails[] = _("FP sync used");
                }
                if (substr($result['FlashDetails'], 8, 1) == 1) {
                    $flashDetails[] = _("2nd(rear)-curtain sync used");
                 }
                if (substr($result['FlashDetails'], 12, 1) == 1) {
                    $flashDetails[] = _("1st curtain sync");
                }
                $result['FlashDetails'] = implode(',', $flashDetails);
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//30
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//31
                $anotherFocusMode = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//32
                if (strpos(strtoupper($exif['IFD0']['Model']), 'G1') !== false) {
                    switch($anotherFocusMode) {
                    case 0: $result['FocusMode'] = _("Single"); break;
                    case 1: $result['FocusMode'] = _("Continuous"); break;
                    default: $result['FocusMode'] = _("Unknown");
                    }
                }
                break;

            case '0004':
                //second chunk
                $result['Bytes']=hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//0
                if ($result['Bytes'] != strlen($data) / 2) {
                    return $result; //Bad chunk
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//1
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//2
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//3
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//4
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//5
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//6
                $result['WhiteBalance'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//7
                switch($result['WhiteBalance']) {
                case 0: $result['WhiteBalance'] = _("Auto"); break;
                case 1: $result['WhiteBalance'] = _("Sunny"); break;
                case 2: $result['WhiteBalance'] = _("Cloudy"); break;
                case 3: $result['WhiteBalance'] = _("Tungsten"); break;
                case 4: $result['WhiteBalance'] = _("Fluorescent"); break;
                case 5: $result['WhiteBalance'] = _("Flash"); break;
                case 6: $result['WhiteBalance'] = _("Custom"); break;
                default: $result['WhiteBalance'] = _("Unknown");
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//8
                $result['SequenceNumber'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//9
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//10
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//11
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data ,$place, 4)));
                $place += 4;//12
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//13
                $result['AFPointUsed']=hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//14
                $afPointUsed = array();
                if ($result['AFPointUsed'] & 0x0001) {
                    $afPointUsed[] = _("Right"); //bit 0
                }
                if ($result['AFPointUsed'] & 0x0002) {
                    $afPointUsed[] = _("Center"); //bit 1
                }
                if ($result['AFPointUsed'] & 0x0004) {
                    $afPointUsed[] = _("Left"); //bit 2
                }
                if ($result['AFPointUsed'] & 0x0800) {
                    $afPointUsed[] = 12; //bit 12
                }
                if ($result['AFPointUsed'] & 0x1000) {
                    $afPointUsed[] = 13; //bit 13
                }
                if ($result['AFPointUsed'] & 0x2000) {
                    $afPointUsed[] = 14; //bit 14
                }
                if ($result['AFPointUsed'] & 0x4000) {
                    $afPointUsed[] = 15; //bit 15
                }
                $result['AFPointUsed'] = implode(',', $afPointUsed);
                $result['FlashBias'] = Horde_Image_Exif::intel2Moto(substr($data, $place, 4));
                $place += 4;//15
                switch($result['FlashBias']) {
                case 'ffc0': $result['FlashBias'] = '-2 EV'; break;
                case 'ffcc': $result['FlashBias'] = '-1.67 EV'; break;
                case 'ffd0': $result['FlashBias'] = '-1.5 EV'; break;
                case 'ffd4': $result['FlashBias'] = '-1.33 EV'; break;
                case 'ffe0': $result['FlashBias'] = '-1 EV'; break;
                case 'ffec': $result['FlashBias'] = '-0.67 EV'; break;
                case 'fff0': $result['FlashBias'] = '-0.5 EV'; break;
                case 'fff4': $result['FlashBias'] = '-0.33 EV'; break;
                case '0000': $result['FlashBias'] = '0 EV'; break;
                case '000c': $result['FlashBias'] = '0.33 EV'; break;
                case '0010': $result['FlashBias'] = '0.5 EV'; break;
                case '0014': $result['FlashBias'] = '0.67 EV'; break;
                case '0020': $result['FlashBias'] = '1 EV'; break;
                case '002c': $result['FlashBias'] = '1.33 EV'; break;
                case '0030': $result['FlashBias'] = '1.5 EV'; break;
                case '0034': $result['FlashBias'] = '1.67 EV'; break;
                case '0040': $result['FlashBias'] = '2 EV'; break;
                default: $result['FlashBias'] = _("Unknown");
                }
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//16
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//17
                $result['Unknown'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//18
                $result['SubjectDistance'] = hexdec(Horde_Image_Exif::intel2Moto(substr($data, $place, 4)));
                $place += 4;//19
                $result['SubjectDistance'] .= '/100 m';
                break;

            case '0008':
                //image number
                if ($intel == 1) {
                    $data = Horde_Image_Exif::intel2Moto($data);
                }
                $data = hexdec($data);
                $result = round($data / 10000) . '-' . $data % 10000;
                break;

            case '000c':
                //camera serial number
                if ($intel == 1) {
                    $data = Horde_Image_Exif::intel2Moto($data);
                }
                $data = hexdec($data);
                $result = '#' . bin2hex(substr($data, 0, 16)) . substr($data, 16, 16);
                break;
            }
            break;

        default:
            if ($type != 'UNDEFINED') {
                $data = bin2hex($data);
                if ($intel == 1) {
                    $data = Horde_Image_Exif::intel2Moto($data);
                }
            }
            break;
        }

        return $data;
    }

    /**
     * Canon Special data section.
     *
     * @see http://www.burren.cx/david/canon.html
     * @see http://www.burren.cx/david/canon.html
     * @see http://www.ozhiker.com/electronics/pjmt/jpeg_info/canon_mn.html
     */
    public function parse($block, &$result, $seek, $globalOffset)
    {
        $place = 0; //current place
        if ($result['Endien'] == 'Intel') {
            $intel = 1;
        } else {
            $intel = 0;
        }

        $model = $result['IFD0']['Model'];

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

            if ($bytesofdata <= 0) {
                return; //if this value is 0 or less then we have read all the tags we can
            }

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
                //offsets are from TIFF header which is 12 bytes from the start
                //of the file
                $v = fseek($seek, $globalOffset + hexdec($value));
                $exiferFileSize = 0;
                if ($v == 0 && $bytesofdata < $exiferFileSize) {
                    $data = fread($seek, $bytesofdata);
                } elseif ($v == -1) {
                    $result['Errors'] = $result['Errors']++;
                    $data = '';
                } else {
                    $data = '';
                }
            }
            // Ensure the index exists.
            $result['SubIFD']['MakerNote'][$tag_name] = '';
            $formated_data = $this->_formatData($type, $tag, $intel, $data, $result, $result['SubIFD']['MakerNote'][$tag_name]);
            $result['SubIFD']['MakerNote'][$tag_name] = $formated_data;
        }
    }
}
