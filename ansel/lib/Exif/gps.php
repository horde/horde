<?php
/**
    Exifer
    Extracts EXIF information from digital photos.

    Copyright © 2003 Jake Olefsky
    http://www.offsky.com/software/exif/index.php
    jake@olefsky.com

    Please see exif.php for the complete information about this software.

    ------------

    This program is free software; you can redistribute it and/or modify it under the terms of
    the GNU General Public License as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
    without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
    See the GNU General Public License for more details. http://www.gnu.org/copyleft/gpl.html
*/

/**
 * Looks up the name of the tag
 *
 * @param unknown_type $tag
 * @return string
 */
function lookup_GPS_tag($tag)
{

    switch($tag) {
        case "0000": return "Version";
        case "0001": return "LatitudeRef";              //north or south
        case "0002": return "Latitude";				    //dd mm.mm or dd mm ss
        case "0003": return "LongitudeRef";		        //east or west
        case "0004": return "Longitude";				//dd mm.mm or dd mm ss
        case "0005": return "AltitudeRef";			    //sea level or below sea level
        case "0006": return "Altitude";					//positive rational number
        case "0007": return "Time";						//three positive rational numbers
        case "0008": return "Satellite";				//text string up to 999 bytes long
        case "0009": return "ReceiveStatus";			//in progress or interop
        case "000a": return "MeasurementMode";		    //2D or 3D
        case "000b": return "MeasurementPrecision";	    //positive rational number
        case "000c": return "SpeedUnit";				//KPH, MPH, knots
        case "000d": return "ReceiverSpeed";			//positive rational number
        case "000e": return "MovementDirectionRef";	    //true or magnetic north
        case "000f": return "MovementDirection";		//positive rational number
        case "0010": return "ImageDirectionRef";		//true or magnetic north
        case "0011": return "ImageDirection";			//positive rational number
        case "0012": return "GeodeticSurveyData";		//text string up to 999 bytes long
        case "0013": return "DestLatitudeRef";		    //north or south
        case "0014": return "DestinationLatitude";		//three positive rational numbers
        case "0015": return "DestLongitudeRef";		    //east or west
        case "0016": return "DestinationLongitude";		//three positive rational numbers
        case "0017": return "DestBearingRef";			//true or magnetic north
        case "0018": return "DestinationBearing";		//positive rational number
        case "0019": return "DestDistanceRef";		    //km, miles, knots
        case "001a": return "DestinationDistance";	    //positive rational number
        case "001b": return "ProcessingMethod";
        case "001c": return "AreaInformation";
        case "001d": return "Datestamp";			    //text string 10 bytes long
        case "001e": return "DifferentialCorrection";  //integer in range 0-65535
        default: return "unknown:".$tag;
    }

}

/**
 * Formats a rational number
 */
function GPSRational($data, $intel)
{

    if ($intel == 1) {
        $top = hexdec(substr($data, 8, 8)); 	//intel stores them bottom-top
    } else {
        $top = hexdec(substr($data, 0, 8));		//motorola stores them top-bottom
    }

    if ($intel == 1) {
        $bottom = hexdec(substr($data, 0, 8));
    } else {
        $bottom = hexdec(substr($data, 8, 8));
    }

    if ($bottom!=0) {
        $data = $top / $bottom;
    } elseif ($top == 0) {
        $data = 0;
    } else {
        $data = $top . "/" . $bottom;
    }

    return $data;
}

/**
 * Formats Data for the data type
 */
function formatGPSData($type, $tag, $intel, $data)
{

    if($type == "ASCII") {
        // Latitude Reference, Longitude Reference
        if ($tag == "0001" || $tag == "0003") {
            $data = ($data{1} == $data{2} && $data{1} == $data{3}) ? $data{0} : $data;
        }
    } elseif ($type == "URATIONAL" || $type == "SRATIONAL") {
        $data = bin2hex($data);
        if ($intel ==1 ) {
            $data = intel2Moto($data);
        }
        if ($intel == 1) {
            $top = hexdec(substr($data, 8, 8)); //intel stores them bottom-top
        } else {
            $top = hexdec(substr($data, 0, 8));	//motorola stores them top-bottom
        }

        if ($intel == 1) {
            $bottom = hexdec(substr($data, 0, 8));
        } else {
            $bottom = hexdec(substr($data, 8, 8));
        }

        if ($type == "SRATIONAL" && $top > 2147483647) {
             // make the number signed instead of unsigned
            $top = $top - 4294967296;
        }

        //Latitude, Longitude
        if ($tag=="0002" || $tag=="0004") {
            if ($intel == 1) {
                $seconds = GPSRational(substr($data, 0, 16), $intel);
                $hour = GPSRational(substr($data, 32, 16), $intel);
            } else {
                $hour = GPSRational(substr($data, 0, 16), $intel);
                $seconds = GPSRational(substr($data, 32, 16), $intel);
            }
            $minutes = GPSRational(substr($data, 16, 16), $intel);
            $data = array($hour, $minutes, $seconds);
        } elseif ($tag == "0007") { //Time
            $seconds = GPSRational(substr($data, 0, 16), $intel);
            $minutes = GPSRational(substr($data, 16, 16), $intel);
            $hour = GPSRational(substr($data, 32, 16), $intel);
            $data = $hour . ":" . $minutes . ":" . $seconds;
        } else {
            if ($bottom != 0) {
                $data = $top / $bottom;
            } elseif ($top == 0) {
                $data = 0;
            } else {
                $data = $top . "/" . $bottom;
            }
            if ($tag == "0006") {
                $data .= 'm';
            }
        }
    } elseif ($type == "USHORT" || $type == "SSHORT" || $type == "ULONG" ||
              $type == "SLONG" || $type == "FLOAT" || $type == "DOUBLE") {

        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
        $data = hexdec($data);
    } elseif ($type == "UNDEFINED") {
    } elseif ($type == "UBYTE") {
        $data = bin2hex($data);
        if ($intel == 1) {
            $num = intel2Moto($data);
        }
        if ($tag == "0000") { // VersionID
            $data =  hexdec(substr($data, 0, 2))
                     . '.' . hexdec(substr($data, 2, 2))
                     . '.' . hexdec(substr($data, 4, 2))
                     . '.'. hexdec(substr($data, 6, 2));
        } elseif ($tag == "0005") { // Altitude Reference
            if ($data == "00000000") {
                $data = 'Above Sea Level';
            } elseif ($data == "01000000") {
                $data = 'Below Sea Level';
            }
        }
    } else {
        $data = bin2hex($data);
        if ($intel == 1) {
            $data = intel2Moto($data);
        }
    }

    return $data;
}
/**
 * GPS Special data section
 * Useful websites
 * http://drewnoakes.com/code/exif/sampleOutput.html
 * http://www.geosnapper.com
 */
function parseGPS($block,&$result,$offset,$seek, $globalOffset)
{
    if ($result['Endien'] == "Intel") {
        $intel = 1;
    } else {
        $intel = 0;
    }

    //offsets are from TIFF header which is 12 bytes from the start of the file
    $v = fseek($seek, $globalOffset + $offset);
    if ($v == -1) {
        $result['Errors'] = $result['Errors']++;
    }

    $num = bin2hex(fread($seek, 2));
    if ($intel == 1) {
        $num = intel2Moto($num);
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
            $tag = intel2Moto($tag);
        }
        $tag_name = lookup_GPS_tag($tag);

        //2 byte datatype
        $type = bin2hex(substr($block, $place, 2));
        $place += 2;
        if ($intel == 1) {
            $type = intel2Moto($type);
        }
        lookup_type($type, $size);

        //4 byte number of elements
        $count = bin2hex(substr($block, $place, 4));
        $place += 4;
        if ($intel==1) {
            $count = intel2Moto($count);
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
                $value = intel2Moto($value);
            }
            //offsets are from TIFF header which is 12 bytes from the start of the file
            $v = fseek($seek, $globalOffset + hexdec($value));
            if ($v == 0) {
                $data = fread($seek, $bytesofdata);
            } elseif ($v == -1) {
                $result['Errors'] = $result['Errors']++;
            }
        }
        $result['GPS' . $tag_name] = formatGPSData($type, $tag, $intel, $data);
    }

}