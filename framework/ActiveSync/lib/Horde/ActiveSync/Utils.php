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

}