<?php
/**
 * Horde_ActiveSync_Utils::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Utils:: contains general utilities.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Utils
{

    /**
     * Decode a base64 encoded URI
     *
     * @param string $url  The Base64 encoded string.
     *
     * @return array  The decoded request
     */
    static public function decodeBase64($uri)
    {
        $uri = base64_decode($uri);
        $lenDevID = ord($uri{4});
        $lenPolKey = ord($uri{4 + (1 + $lenDevID)});
        $lenDevType = ord($uri{4 + (1 + $lenDevID) + (1 + $lenPolKey)});
        $arr_ret = unpack(
            'CProtVer/CCommand/vLocale/CDevIDLen/H' . ($lenDevID * 2)
                . 'DevID/CPolKeyLen' . ($lenPolKey == 4 ? '/VPolKey' : '')
                . '/CDevTypeLen/A' . $lenDevType . 'DevType', $uri);
        $pos = (7 + $lenDevType + $lenPolKey + $lenDevID);
        $uri = substr($uri, $pos);
        while (strlen($uri) > 0) {
            $lenToken = ord($uri{1});
            switch (ord($uri{0})) {
            case 0:
                $type = 'AttachmentName';
                break;
            case 1:
                $type = 'CollectionId';
                break;
            case 2:
                $type = 'CollectionName';
                break;
            case 3:
                $type = 'ItemId';
                break;
            case 4:
                $type = 'LongId';
                break;
            case 5:
                $type = 'ParentId';
                break;
            case 6:
                $type = 'Occurrence';
                break;
            case 7:
                $type = 'Options';
                break;
            case 8:
                $type = 'User';
                break;
            default:
                $type = 'unknown' . ord($uri{0});
                break;
            }
           $value = unpack('CType/CLength/A' . $lenToken . 'Value', $uri);
           $arr_ret[$type] = $value['Value'];
           $pos = 2 + $lenToken;
           $uri = substr($uri, $pos);
        }
        return $arr_ret;
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
    static public function getUidFromGoid($goid)
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
                $hex[] = sprintf("%02X", ord($chr));
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
    static public function createGoid($uid, $options = array())
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
            $hexuid .= sprintf("%02X", ord($chr));
        }

        // Pack it
        $goid = pack('H*H*H*H*VH*H*x', $arrayid, $exception, $creationtime, $reserved, $size, $vCard, $hexuid);

        return base64_encode($goid);
    }

}