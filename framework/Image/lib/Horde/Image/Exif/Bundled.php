<?php
/**
 * Class for dealing with Exif data using a bundled PHP library based on Exifer.
 *
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_Image
 */
class Horde_Image_Exif_Bundled extends Horde_Image_Exif_Base
{
    public function getData($image)
    {
        $raw = self::_exif_read_data($image);
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

        return $this->_processData($exif);
    }


    /**
     *
     * @param $path
     * @return unknown_type
     */
    static protected function _exif_read_data($path)
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
            $offset = Horde_Image_Exif::intel2Moto($offset);
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
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $num = hexdec($num);
        $result['IFD0NumTags'] = $num;

        // 1000 entries is too much and is probably an error.
        if ($num < 1000) {
            for($i = 0; $i < $num; $i++) {
                self::_readEntry($result, $in, $seek, $intel, 'IFD0', $globalOffset);
            }
        } else {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = 'Illegal size for IFD0';
        }

        // store offset to IFD1
        $offset = bin2hex(fread($in, 4));
        if ($intel == 1) {
            $offset = Horde_Image_Exif::intel2Moto($offset);
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
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $num = hexdec($num);
        $result['SubIFDNumTags'] = $num;

        // 1000 entries is too much and is probably an error.
        if ($num < 1000) {
            for($i = 0; $i < $num; $i++) {
                self::_readEntry($result, $in, $seek, $intel, 'SubIFD', $globalOffset);
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
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $num = hexdec($num);
        $result['IFD1NumTags'] = $num;

        // 1000 entries is too much and is probably an error.
        if ($num < 1000) {
            for($i = 0; $i < $num; $i++) {
                self::_readEntry($result, $in, $seek, $intel, 'IFD1', $globalOffset);
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
            $num = Horde_Image_Exif::intel2Moto($num);
        }
        $num = hexdec($num);
        $result['InteroperabilityIFDNumTags'] = $num;

        // 1000 entries is too much and is probably an error.
        if ($num < 1000) {
            for($i = 0; $i < $num; $i++) {
                self::_readEntry($result, $in, $seek, $intel, 'InteroperabilityIFD', $globalOffset);
            }
        } else {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = _("Illegal size for InteroperabilityIFD");
        }
        fclose($in);
        fclose($seek);
        return $result;
    }

    /**
     *
     * @param $result
     * @param $in
     * @param $seek
     * @param $intel
     * @param $ifd_name
     * @param $globalOffset
     * @return unknown_type
     */
    static protected function _readEntry(&$result, $in, $seek, $intel, $ifd_name, $globalOffset)
    {
        // Still ok to read?
        if (feof($in)) {
            $result['Errors'] = $result['Errors'] + 1;
            return;
        }

        // 2 byte tag
        $tag = bin2hex(fread($in, 2));
        if ($intel == 1) $tag = Horde_Image_Exif::intel2Moto($tag);
        $tag_name = self::_lookupTag($tag);

        // 2 byte datatype
        $type = bin2hex(fread($in, 2));
        if ($intel == 1) $type = Horde_Image_Exif::intel2Moto($type);
        self::_lookupType($type, $size);

        // 4 byte number of elements
        $count = bin2hex(fread($in, 4));
        if ($intel == 1) $count = Horde_Image_Exif::intel2Moto($count);
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
                $value = Horde_Image_Exif::intel2Moto($value);
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
                Horde_Image_Exif_Parser_Nikon::parse($data, $result);
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos(strtolower($make), 'olympus') !== false) {
                Horde_Image_Exif_Parser_Olympus::parse($data, $result, $seek, $globalOffset);
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos(strtolower($make), 'canon') !== false) {
                Horde_Image_Exif_Parser_Canon::parse($data, $result, $seek, $globalOffset);
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos(strtolower($make), 'fujifilm') !== false) {
                Horde_Image_Exif_Parser_Fujifilm::parse($data, $result);
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos(strtolower($make), 'sanyo') !== false) {
                Horde_Image_Exif_Parser_Sanyo::parse($data, $result, $seek, $globalOffset);
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos(strtolower($make), 'panasonic') !== false) {
                Horde_Image_Exif_Parser_Panasonic::parse($data, $result, $seek, $globalOffset);
                $result[$ifd_name]['KnownMaker'] = 1;
            } else {
                $result[$ifd_name]['KnownMaker'] = 0;
            }
        } elseif ($tag_name == 'GPSInfoOffset') {
            $formated_data = self::_formatData($type, $tag, $intel, $data);
            $result[$ifd_name]['GPSInfo'] = $formated_data;
            Horde_Image_Exif_Parser_Gps::parse($data, $result, $formated_data, $seek, $globalOffset);
        } else {
            // Format the data depending on the type and tag
            $formated_data = self::_formatData($type, $tag, $intel, $data);
            $result[$ifd_name][$tag_name] = $formated_data;
        }
    }

    /**
     *
     * @param $tag
     * @return unknown_type
     */
    static protected function _lookupTag($tag)
    {
        switch($tag)
        {
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

    /**
     *
     * @param $type
     * @param $tag
     * @param $intel
     * @param $data
     * @return unknown_type
     */
    static protected function _formatData($type, $tag, $intel, $data)
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
                $data = Horde_Image_Exif::intel2Moto($data);
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
                $data = Horde_Image_Exif::intel2Moto($data);
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
            if ($intel == 1) $data = Horde_Image_Exif::intel2Moto($data);
        }

        return $data;
    }

    /**
     *
     * @param $type
     * @param $size
     * @return unknown_type
     */
    static protected function _lookupType(&$type, &$size) {
        switch ($type) {
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

    public function supportedCategories()
    {
        return array('EXIF');
    }

}