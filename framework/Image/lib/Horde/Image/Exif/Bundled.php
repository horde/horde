<?php
/**
 * Class for dealing with Exif data using a bundled PHP library based on
 * Exifer.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
        $raw = $this->_readData($image);
        $exif = array();
        foreach ($raw as $key => $value) {
            if ($key == 'IFD0' || $key == 'SubIFD') {
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
     *
     * @return array
     */
    protected function _readData($path)
    {
        // There may be an elegant way to do this with one file handle.
        $in = @fopen($path, 'rb');
        $seek = @fopen($path, 'rb');
        $globalOffset = 0;
        $result = array('Errors' => 0);

        // if the path was invalid, this error will catch it
        if (!$in || !$seek) {
            $result['Errors'] = 1;
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("The file could not be opened.");
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

        // Next 2 bytes are marker tag (0xFFE#)
        $data = bin2hex(fread($in, 2));
        $size = bin2hex(fread($in, 2));

        // Loop through markers till you get to FFE1 (Exif marker)
        while(!feof($in) && $data != 'ffe1' && $data != 'ffc0' && $data != 'ffd9') {
            switch ($data) {
            case 'ffe0':
                // JFIF Marker
                $result['ValidJFIFData'] = 1;
                $result['JFIF']['Size'] = hexdec($size);
                if (hexdec($size) - 2 > 0) {
                    $data = fread($in, hexdec($size) - 2);
                    $result['JFIF']['Data'] = $data;
                }
                $result['JFIF']['Identifier'] = substr($data, 0, 5);
                $result['JFIF']['ExtensionCode'] = bin2hex(substr($data, 6, 1));
                $globalOffset += hexdec($size) + 2;
                break;

            case 'ffed':
                // IPTC Marker
                $result['ValidIPTCData'] = 1;
                $result['IPTC']['Size'] = hexdec($size);
                if (hexdec($size) - 2 > 0) {
                    $data = fread($in, hexdec($size) - 2);
                    $result['IPTC']['Data'] = $data ;
                }
                $globalOffset += hexdec($size) + 2;
                break;

            case 'ffe2':
                // EXIF extension Marker
                $result['ValidAPP2Data'] = 1;
                $result['APP2']['Size'] = hexdec($size);
                if (hexdec($size) - 2 > 0) {
                    $data = fread($in, hexdec($size) - 2);
                    $result['APP2']['Data'] = $data ;
                }
                $globalOffset += hexdec($size) + 2;
                break;

            case 'fffe':
                // COM extension Marker
                $result['ValidCOMData'] = 1;
                $result['COM']['Size'] = hexdec($size);
                if (hexdec($size) - 2 > 0) {
                    $data = fread($in, hexdec($size) - 2);
                    $result['COM']['Data'] = $data ;
                }
                $globalOffset += hexdec($size) + 2;
                break;

            case 'ffe1':
                $result['ValidEXIFData'] = 1;
                break;
            }

            $data = bin2hex(fread($in, 2));
            $size = bin2hex(fread($in, 2));
        }

        if ($data != 'ffe1') {
            fclose($in);
            fclose($seek);
            return $result;
        }

        $result['ValidEXIFData'] = 1;

        // Size of APP1
        $result['APP1Size'] = hexdec($size);

        // Start of APP1 block starts with 'Exif' header (6 bytes)
        $header = fread($in, 6);

        // Then theres a TIFF header with 2 bytes of endieness (II or MM)
        $header = fread($in, 2);
        switch ($header) {
        case 'II':
            $intel = 1;
            $result['Endien'] = 'Intel';
            break;
        case 'MM':
            $intel = 0;
            $result['Endien'] = 'Motorola';
            break;
        default:
            // not sure what the default should be, but this seems reasonable
            $intel = 1;
            $result['Endien'] = 'Unknown';
            break;
        }

        // 2 bytes of 0x002a
        $tag = bin2hex(fread( $in, 2 ));

        // Then 4 bytes of offset to IFD0 (usually 8 which includes all 8 bytes
        // of TIFF header)
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
            for ($i = 0; $i < $num; $i++) {
                $this->_readEntry($result, $in, $seek, $intel, 'IFD0', $globalOffset);
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
        if (!isset($result['IFD0']['ExifOffset']) ||
            $result['IFD0']['ExifOffset'] == 0) {
            fclose($in);
            fclose($seek);
            return $result;
        }

        // seek to SubIFD (Value of ExifOffset tag) above.
        $ExitOffset = $result['IFD0']['ExifOffset'];
        $v = fseek($in, $globalOffset + $ExitOffset);
        if ($v == -1) {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Couldnt Find SubIFD");
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
            for ($i = 0; $i < $num; $i++) {
                $this->_readEntry($result, $in, $seek, $intel, 'SubIFD', $globalOffset);
            }
        } else {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Illegal size for SubIFD");
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
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Couldnt Find IFD1");
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
            for ($i = 0; $i < $num; $i++) {
                $this->_readEntry($result, $in, $seek, $intel, 'IFD1', $globalOffset);
            }
        } else {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Illegal size for IFD1");
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
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Couldnt Find InteroperabilityIFD");
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
            for ($i = 0; $i < $num; $i++) {
                $this->_readEntry($result, $in, $seek, $intel, 'InteroperabilityIFD', $globalOffset);
            }
        } else {
            $result['Errors'] = $result['Errors'] + 1;
            $result['Error'][$result['Errors']] = Horde_Image_Translation::t("Illegal size for InteroperabilityIFD");
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
    protected function _readEntry(&$result, $in, $seek, $intel, $ifd_name,
                                  $globalOffset)
    {
        // Still ok to read?
        if (feof($in)) {
            $result['Errors'] = $result['Errors'] + 1;
            return;
        }

        // 2 byte tag
        $tag = bin2hex(fread($in, 2));
        if ($intel == 1) {
            $tag = Horde_Image_Exif::intel2Moto($tag);
        }
        $tag_name = $this->_lookupTag($tag);

        // 2 byte datatype
        $type = bin2hex(fread($in, 2));
        if ($intel == 1) {
            $type = Horde_Image_Exif::intel2Moto($type);
        }
        $this->_lookupType($type, $size);

        // 4 byte number of elements
        $count = bin2hex(fread($in, 4));
        if ($intel == 1) {
            $count = Horde_Image_Exif::intel2Moto($count);
        }
        $bytesofdata = $size * hexdec($count);

        // 4 byte value or pointer to value if larger than 4 bytes
        $value = fread($in, 4);

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
        switch ($tag_name) {
        case 'MakerNote':
            $make = Horde_String::lower($result['IFD0']['Make']);
            $parser = null;
            if (strpos($make, 'nikon') !== false) {
                $parser = new Horde_Image_Exif_Parser_Nikon();
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos($make, 'olympus') !== false) {
                $parser = new Horde_Image_Exif_Parser_Olympus();
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos($make, 'canon') !== false) {
                $parser = new Horde_Image_Exif_Parser_Canon();
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos($make, 'fujifilm') !== false) {
                $parser = new Horde_Image_Exif_Parser_Fujifilm();
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos($make, 'sanyo') !== false) {
                $parser = new Horde_Image_Exif_Parser_Sanyo();
                $result[$ifd_name]['KnownMaker'] = 1;
            } elseif (strpos($make, 'panasonic') !== false) {
                $parser = new Horde_Image_Exif_Parser_Panasonic();
                $result[$ifd_name]['KnownMaker'] = 1;
            } else {
                $result[$ifd_name]['KnownMaker'] = 0;
            }
            if ($parser) {
                $parser->parse($data, $result, $seek, $globalOffset);
            }
            break;

        case 'GPSInfoOffset':
            $formated_data = $this->_formatData($type, $tag, $intel, $data);
            $result[$ifd_name]['GPSInfo'] = $formated_data;
            $parser = new Horde_Image_Exif_Parser_Gps();
            $parser->parse($data, $result, $formated_data, $seek, $globalOffset);
            break;

        default:
            // Format the data depending on the type and tag
            $formated_data = $this->_formatData($type, $tag, $intel, $data);
            $result[$ifd_name][$tag_name] = $formated_data;
        }
    }

    /**
     *
     * @param $tag
     * @return unknown_type
     */
    protected function _lookupTag($tag)
    {
        switch($tag)
        {
            // used by IFD0 'Camera Tags'
            // text string up to 999 bytes long
            case '000b': $tag = 'ACDComment'; break;
            // integer -2147483648 to 2147483647
            case '00fe': $tag = 'ImageType'; break;
            // ?? Please send sample image with this tag
            case '0106': $tag = 'PhotometricInterpret'; break;
            // text string up to 999 bytes long
            case '010e': $tag = 'ImageDescription'; break;
            // text string up to 999 bytes long
            case '010f': $tag = 'Make'; break;
            // text string up to 999 bytes long
            case '0110': $tag = 'Model'; break;
            // integer values 1-9
            case '0112': $tag = 'Orientation'; break;
            // integer 0-65535
            case '0115': $tag = 'SamplePerPixel'; break;
            // positive rational number
            case '011a': $tag = 'xResolution'; break;
            // positive rational number
            case '011b': $tag = 'yResolution'; break;
            // integer values 1-2
            case '011c': $tag = 'PlanarConfig'; break;
            // integer values 1-3
            case '0128': $tag = 'ResolutionUnit'; break;
            // text string up to 999 bytes long
            case '0131': $tag = 'Software'; break;
            // YYYY:MM:DD HH:MM:SS
            case '0132': $tag = 'DateTime'; break;
            // text string up to 999 bytes long
            case '013b': $tag = 'Artist'; break;
            // text string
            case '013c': $tag = 'HostComputer'; break;
            // two positive rational numbers
            case '013e': $tag = 'WhitePoint'; break;
            // six positive rational numbers
            case '013f': $tag = 'PrimaryChromaticities'; break;
            // three positive rational numbers
            case '0211': $tag = 'YCbCrCoefficients'; break;
            // integer values 1-2
            case '0213': $tag = 'YCbCrPositioning'; break;
            // six positive rational numbers
            case '0214': $tag = 'ReferenceBlackWhite'; break;
            // text string up to 999 bytes long
            case '8298': $tag = 'Copyright'; break;
            // ??
            case '8649': $tag = 'PhotoshopSettings'; break;
            case '8825': $tag = 'GPSInfoOffset'; break;
            // positive integer
            case '8769': $tag = 'ExifOffset'; break;

            // used by Exif SubIFD 'Image Tags'
            // seconds or fraction of seconds 1/x
            case '829a': $tag = 'ExposureTime'; break;
            // positive rational number
            case '829d': $tag = 'FNumber'; break;
            // integer value 1-9
            case '8822': $tag = 'ExposureProgram'; break;
            // ??
            case '8824': $tag = 'SpectralSensitivity'; break;
            // integer 0-65535
            case '8827': $tag = 'ISOSpeedRatings'; break;
            // ??
            case '9000': $tag = 'ExifVersion'; break;
            // YYYY:MM:DD HH:MM:SS
            case '9003': $tag = 'DateTimeOriginal'; break;
            // YYYY:MM:DD HH:MM:SS
            case '9004': $tag = 'DateTimedigitized'; break;
            // ??
            case '9101': $tag = 'ComponentsConfiguration'; break;
            // positive rational number
            case '9102': $tag = 'CompressedBitsPerPixel'; break;
            // seconds or fraction of seconds 1/x
            case '9201': $tag = 'ShutterSpeedValue'; break;
            // positive rational number
            case '9202': $tag = 'ApertureValue'; break;
            // positive rational number
            case '9203': $tag = 'BrightnessValue'; break;
            // positive rational number (EV)
            case '9204': $tag = 'ExposureBiasValue'; break;
            // positive rational number
            case '9205': $tag = 'MaxApertureValue'; break;
            // positive rational number (meters)
            case '9206': $tag = 'SubjectDistance'; break;
            // integer 1-6 and 255
            case '9207': $tag = 'MeteringMode'; break;
            // integer 1-255
            case '9208': $tag = 'LightSource'; break;
            // integer 1-255
            case '9209': $tag = 'Flash'; break;
            // positive rational number (mm)
            case '920a': $tag = 'FocalLength'; break;
            // text string up to 999 bytes long
            case '9213': $tag = 'ImageHistory'; break;
            // a bunch of data
            case '927c': $tag = 'MakerNote'; break;
            // text string
            case '9286': $tag = 'UserComment'; break;
            // text string up to 999 bytes long
            case '9290': $tag = 'SubsecTime'; break;
            // text string up to 999 bytes long
            case '9291': $tag = 'SubsecTimeOriginal'; break;
            // text string up to 999 bytes long
            case '9292': $tag = 'SubsecTimeDigitized'; break;
            // ??
            case 'a000': $tag = 'FlashPixVersion'; break;
            // values 1 or 65535
            case 'a001': $tag = 'ColorSpace'; break;
            // ingeter 1-65535
            case 'a002': $tag = 'ExifImageWidth'; break;
            // ingeter 1-65535
            case 'a003': $tag = 'ExifImageHeight'; break;
            // text string 12 bytes long
            case 'a004': $tag = 'RelatedSoundFile'; break;
            // positive integer
            case 'a005': $tag = 'ExifInteroperabilityOffset'; break;
            // ??
            case 'a20c': $tag = 'SpacialFreqResponse'; break;
            // positive rational number
            case 'a20b': $tag = 'FlashEnergy'; break;
            // positive rational number
            case 'a20e': $tag = 'FocalPlaneXResolution'; break;
            // positive rational number
            case 'a20f': $tag = 'FocalPlaneYResolution'; break;
            // values 1-3
            case 'a210': $tag = 'FocalPlaneResolutionUnit'; break;
            // two integers 0-65535
            case 'a214': $tag = 'SubjectLocation'; break;
            // positive rational number
            case 'a215': $tag = 'ExposureIndex'; break;
            // values 1-8
            case 'a217': $tag = 'SensingMethod'; break;
            // integer
            case 'a300': $tag = 'FileSource'; break;
            // integer
            case 'a301': $tag = 'SceneType'; break;
            // undefined data type
            case 'a302': $tag = 'CFAPattern'; break;
            // values 0 or 1
            case 'a401': $tag = 'CustomerRender'; break;
            // values 0-2
            case 'a402': $tag = 'ExposureMode'; break;
            // values 0 or 1
            case 'a403': $tag = 'WhiteBalance'; break;
            // positive rational number
            case 'a404': $tag = 'DigitalZoomRatio'; break;
            case 'a405': $tag = 'FocalLengthIn35mmFilm';break;
            // values 0-3
            case 'a406': $tag = 'SceneCaptureMode'; break;
            // values 0-4
            case 'a407': $tag = 'GainControl'; break;
            // values 0-2
            case 'a408': $tag = 'Contrast'; break;
            // values 0-2
            case 'a409': $tag = 'Saturation'; break;
            // values 0-2
            case 'a40a': $tag = 'Sharpness'; break;

            // used by Interoperability IFD
            // text string 3 bytes long
            case '0001': $tag = 'InteroperabilityIndex'; break;
            // datatype undefined
            case '0002': $tag = 'InteroperabilityVersion'; break;
            // text string up to 999 bytes long
            case '1000': $tag = 'RelatedImageFileFormat'; break;
            // integer in range 0-65535
            case '1001': $tag = 'RelatedImageWidth'; break;
            // integer in range 0-65535

            case '1002': $tag = 'RelatedImageLength'; break;
            // used by IFD1 'Thumbnail'
            // integer in range 0-65535
            case '0100': $tag = 'ImageWidth'; break;
            // integer in range 0-65535
            case '0101': $tag = 'ImageLength'; break;
            // integers in range 0-65535
            case '0102': $tag = 'BitsPerSample'; break;
            // values 1 or 6
            case '0103': $tag = 'Compression'; break;
            // values 0-4
            case '0106': $tag = 'PhotometricInterpretation'; break;
            // text string up to 999 bytes long
            case '010e': $tag = 'ThumbnailDescription'; break;
            // text string up to 999 bytes long
            case '010f': $tag = 'ThumbnailMake'; break;
            // text string up to 999 bytes long
            case '0110': $tag = 'ThumbnailModel'; break;
            // ??
            case '0111': $tag = 'StripOffsets'; break;
            // integer 1-9
            case '0112': $tag = 'ThumbnailOrientation'; break;
            // ??
            case '0115': $tag = 'SamplesPerPixel'; break;
            // ??
            case '0116': $tag = 'RowsPerStrip'; break;
            // ??
            case '0117': $tag = 'StripByteCounts'; break;
            // positive rational number
            case '011a': $tag = 'ThumbnailXResolution'; break;
            // positive rational number
            case '011b': $tag = 'ThumbnailYResolution'; break;
            // values 1 or 2
            case '011c': $tag = 'PlanarConfiguration'; break;
            // values 1-3
            case '0128': $tag = 'ThumbnailResolutionUnit'; break;
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
    protected function _formatData($type, $tag, $intel, $data)
    {
        switch ($type) {
        case 'ASCII':
            // Search for a null byte and stop there.
            if (($pos = strpos($data, chr(0))) !== false) {
                $data = substr($data, 0, $pos);
            }
            // Format certain kinds of strings nicely (Camera make etc.)
            if ($tag == '010f') {
                $data = ucwords(strtolower(trim($data)));
            }
            break;

        case 'URATIONAL':
        case 'SRATIONAL':
            $data = bin2hex($data);
            if ($intel == 1) {
                $data = Horde_Image_Exif::intel2Moto($data);
            }

            if ($intel == 1) {
                // intel stores them bottom-top
                $top = hexdec(substr($data, 8, 8));
            } else {
                // motorola stores them top-bottom
                $top = hexdec(substr($data, 0, 8));
            }

            if ($intel == 1) {
                // intel stores them bottom-top
                $bottom = hexdec(substr($data, 0, 8));
            } else {
                // motorola stores them top-bottom
                $bottom = hexdec(substr($data, 8, 8));
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
            break;

        case 'UNDEFINED':
            // ExifVersion,FlashPixVersion,InteroperabilityVersion
            if ($tag == '9000' || $tag == 'a000' || $tag == '0002') {
                $data = sprintf(Horde_Image_Translation::t("version %d"), $data / 100);
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
     * @param $type
     * @param $size
     * @return unknown_type
     */
    protected function _lookupType(&$type, &$size)
    {
        switch ($type) {
        case '0001': $type = 'UBYTE'; $size = 1; break;
        case '0002': $type = 'ASCII'; $size = 1; break;
        case '0003': $type = 'USHORT'; $size = 2; break;
        case '0004': $type = 'ULONG'; $size = 4; break;
        case '0005': $type = 'URATIONAL'; $size = 8; break;
        case '0006': $type = 'SBYTE'; $size = 1; break;
        case '0007': $type = 'UNDEFINED'; $size = 1; break;
        case '0008': $type = 'SSHORT'; $size = 2; break;
        case '0009': $type = 'SLONG'; $size = 4; break;
        case '000a': $type = 'SRATIONAL'; $size = 8; break;
        case '000b': $type = 'FLOAT'; $size = 4; break;
        case '000c': $type = 'DOUBLE'; $size = 8; break;
        default: $type = 'error:'.$type; $size = 0; break;
        }

        return $type;
    }

    public function supportedCategories()
    {
        return array('EXIF');
    }

}