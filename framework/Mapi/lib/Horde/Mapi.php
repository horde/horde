<?php
/**
 * Horde_Mapi::
 *
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   Mapi_Utils
 */
/**
 * Utility functions for dealing with Microsoft MAPI structures.
 *
 * Copyright 2009-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @copyright 2009-2016 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   Mapi_Utils
 */
class Horde_Mapi
{
    /**
     * Determine if the current machine is little endian.
     *
     * @return boolean  True if endianness is little endian, otherwise false.
     */
    public static function isLittleEndian()
    {
        $testint = 0x00FF;
        $p = pack('S', $testint);

        return ($testint === current(unpack('v', $p)));
    }

    /**
     * Change the byte order of a number. Used to allow big endian machines to
     * decode the timezone blobs, which are encoded in little endian order.
     *
     * @param integer $num  The number to reverse.
     *
     * @return integer  The number, in the reverse byte order.
     */
    public static function chbo($num)
    {
        $u = unpack('l', strrev(pack('l', $num)));

        return $u[1];
    }

    /**
     * Obtain the UID from a MAPI GOID.
     *
     * See http://msdn.microsoft.com/en-us/library/hh338153%28v=exchg.80%29.aspx
     *
     * @param string $goid  Base64 encoded Global Object Identifier.
     *
     * @return string  The UID
     */
    public static function getUidFromGoid($goid)
    {
        $goid = base64_decode($goid);

        // First, see if it's an Outlook UID or not.
        if (substr($goid, 40, 8) == 'vCal-Uid') {
            // For vCal UID values:
            // Bytes 37 - 40 contain length of data and padding
            // Bytes 41 - 48 are == vCal-Uid
            // Bytes 53 until next to the last byte (/0) contain the UID.
            return trim(substr($goid, 52, strlen($goid) - 1));
        } else {
            // If it's not a vCal UID, then it is Outlook style UID:
            // The entire decoded goid is converted to hex representation with
            // bytes 17 - 20 converted to zero
            $hex = array();
            foreach (str_split($goid) as $chr) {
                $hex[] = sprintf('%02X', ord($chr));
            }
            array_splice($hex, 16, 4, array('00', '00', '00', '00'));
            return implode('', $hex);
        }
    }

    /**
     * Create a MAPI GOID from a UID
     * See http://msdn.microsoft.com/en-us/library/ee157690%28v=exchg.80%29
     *
     * @param string $uid  The UID value to encode.
     *
     * @return string  A Base64 encoded GOID
     */
    public static function createGoid($uid, $options = array())
    {
        // Bytes 1 - 16 MUST be equal to the GOID identifier:
        $arrayid = '040000008200E00074C5B7101A82E008';

        // Bytes 17 - 20 - Exception replace time (YH YL M D)
        $exception = '00000000';

        // Bytes 21 - 28 The 8 byte creation time (can be all zeros if not available).
        $creationtime = '0000000000000000';

        // Bytes 29 - 36 Reserved 8 bytes must be all zeros.
        $reserved = '0000000000000000';

        // Bytes 37 - 40 - A long value describing the size of the UID data.
        $size = strlen($uid);

        // Bytes 41 - 52 - MUST BE vCal-Uid 0x01 0x00 0x00 0x00
        $vCard = '7643616C2D55696401000000';

        // The UID Data:
        $hexuid = '';
        foreach (str_split($uid) as $chr) {
            $hexuid .= sprintf('%02X', ord($chr));
        }

        // Pack it
        $goid = pack('H*H*H*H*VH*H*x', $arrayid, $exception, $creationtime, $reserved, $size, $vCard, $hexuid);

        return base64_encode($goid);
    }

    /**
     * Converts a Windows FILETIME value to a unix timestamp.
     *
     * Adapted from:
     * http://stackoverflow.com/questions/610603/help-me-translate-long-value-expressed-in-hex-back-in-to-a-date-time
     *
     * @param string $ft  Binary representation of FILETIME from a pTypDate
     *                    MAPI property.
     *
     * @return integer  The unix timestamp.
     * @throws Horde_Mapi_Exception
     */
    public static function filetimeToUnixtime($ft)
    {
        $ft = bin2hex($ft);
        $dtval = substr($ft, 0, 16);        // clip overlength string
        $dtval = str_pad($dtval, 16, '0');  // pad underlength string
        $quad = self::_flipEndian($dtval);
        $win64_datetime = self::_hexToBcint($quad);
        return self::_win64ToUnix($win64_datetime);
    }

    // swap little-endian to big-endian
    protected static function _flipEndian($str)
    {
        // make sure #digits is even
        if ( strlen($str) & 1 )
            $str = '0' . $str;

        $t = '';
        for ($i = strlen($str)-2; $i >= 0; $i-=2)
            $t .= substr($str, $i, 2);

        return $t;
    }

    // convert hex string to BC-int
    protected static function _hexToBcint($str)
    {
        $hex = array(
            '0'=>'0',   '1'=>'1',   '2'=>'2',   '3'=>'3',   '4'=>'4',
            '5'=>'5',   '6'=>'6',   '7'=>'7',   '8'=>'8',   '9'=>'9',
            'a'=>'10',  'b'=>'11',  'c'=>'12',  'd'=>'13',  'e'=>'14',  'f'=>'15',
            'A'=>'10',  'B'=>'11',  'C'=>'12',  'D'=>'13',  'E'=>'14',  'F'=>'15'
        );

        $bci = new Math_BigInteger('0');
        $len = strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            $bci = $bci->multiply(new Math_BigInteger('16'));
            $ch = $str[$i];
            if (isset($hex[$ch])) {
                $bci = $bci->add(new Math_BigInteger($hex[$ch]));
            }
        }

        return $bci;
    }

    /**
     *
     * @param  Math_BigInteger $bci
     * @return string
     */
    protected static function _win64ToUnix($bci)
    {
        // Unix epoch as a Windows file date-time value
        $t = $bci->subtract(new Math_BigInteger('116444735995904000'));    // Cast to Unix epoch
        list($quotient, $remainder) = $t->divide(new Math_BigInteger('10000000'));

        return (string)$quotient;
    }


}
