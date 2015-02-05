<?php
/**
 * Horde_ActiveSync_Utils::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
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
 * @copyright 2010-2015 Horde LLC (http://www.horde.org)
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
    public static function decodeBase64($uri)
    {
        $commandMap = array(
                0  => 'Sync',
                1  => 'SendMail',
                2  => 'SmartForward',
                3  => 'SmartReply',
                4  => 'GetAttachment',
                9  => 'FolderSync',
                10 => 'FolderCreate',
                11 => 'FolderDelete',
                12 => 'FolderUpdate',
                13 => 'MoveItems',
                14 => 'GetItemEstimate',
                15 => 'MeetingResponse',
                16 => 'Search',
                17 => 'Settings',
                18 => 'Ping',
                19 => 'ItemOperations',
                20 => 'Provision',
                21 => 'ResolveRecipients',
                22 => 'ValidateCert'
            );
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, base64_decode($uri));
        rewind($stream);
        $results = array();
        // Version, command, locale
        $data = unpack('CprotocolVersion/Ccommand/vlocale', fread($stream, 4));
        $results['ProtVer'] = substr($data['protocolVersion'], 0, -1) . '.' . substr($data['protocolVersion'], -1);
        $results['Cmd'] = $commandMap[$data['command']];
        $results['Locale'] = $data['locale'];

        // deviceId
        $length = ord(fread($stream, 1));
        if ($length > 0) {
            $data = fread($stream, $length);
            $data = unpack('H' . ($length * 2) . 'DevID', $data);
            $results['DeviceId'] = $data['DevID'];
        }

        // policyKey
        $length = ord(fread($stream, 1));
        if ($length > 0) {
            $data  = unpack('VpolicyKey', fread($stream, $length));
            $results['PolicyKey'] = $data['policyKey'];
        }

        // deviceType
        $length = ord(fread($stream, 1));
        if ($length > 0) {
            $data  = unpack('A' . $length . 'devType', fread($stream, $length));
            $results['DeviceType'] = $data['devType'];
        }

        // Remaining properties
        while (!feof($stream)) {
            $tag = ord(fread($stream, 1));
            $length = ord(fread($stream, 1));
            switch ($tag) {
            case 0:
                $data = unpack('A' . $length . 'AttName', fread($stream, $length));
                $results['AttachmentName'] = $data['AttName'];
                break;
            case 1:
                $data = unpack('A' . $length . 'CollId', fread($stream, $length));
                $results['CollectionId'] = $data['CollId'];
                break;
            case 3:
                $data = unpack('A' . $length . 'ItemId', fread($stream, $length));
                $results['ItemId'] = $data['ItemId'];
                break;
            case 4:
                $data = unpack('A' . $length . 'Lid', fread($stream, $length));
                $results['LongId'] = $data['Lid'];
                break;
            case 5:
                $data = unpack('A' . $length . 'Pid', fread($stream, $length));
                $results['ParentId'] = $data['Pid'];
                break;
            case 6:
                $data = unpack('A' . $length . 'Oc', fread($stream, $length));
                $results['Occurrence'] = $data['Oc'];
                break;
            case 7:
                $options = ord(fread($stream, 1));
                $results['SaveInSent'] = !!($options & 0x01);
                $results['AcceptMultiPart'] = !!($options & 0x02);
                break;
            case 8:
                $data = unpack('A' . $length . 'User', fread($stream, $length));
                $results['User'] = $data['User'];
                break;
            }
        }

        return $results;
    }

    /**
     * Obtain the UID from a MAPI GOID.
     *
     * See http://msdn.microsoft.com/en-us/library/hh338153%28v=exchg.80%29.aspx
     *
     * @param string $goid  Base64 encoded Global Object Identifier.
     *
     * @return string  The UID
     * @deprecated  Will be removed in H6. Use Horde_Mapi::getUidFromGoid
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
     * @deprecated  Will be removed in H6. Use Horde_Mapi::createGoid
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
     * Ensure $data is converted to valid UTF-8 data. Works as follows:
     * Converts to UTF-8, assuming data is in $from_charset encoding. If
     * that produces invalid UTF-8, attempt to convert to most common mulitibyte
     * encodings. If that *still* fails, strip out non 7-Bit characters...and
     * force encoding to UTF-8 from $from_charset as a last resort.
     *
     * @param string $data          The string data to convert to UTF-8.
     * @param string $from_charset  The character set to assume $data is encoded
     *                              in.
     *
     * @return string  A valid UTF-8 encoded string.
     */
    public static function ensureUtf8($data, $from_charset)
    {
        $text = Horde_String::convertCharset($data, $from_charset, 'UTF-8');
        if (!Horde_String::validUtf8($text)) {
            $test_charsets = array(
                'windows-1252',
                'UTF-8'
            );
            foreach ($test_charsets as $charset) {
                if ($charset != $from_charset) {
                    $text = Horde_String::convertCharset($data, $charset, 'UTF-8');
                    if (Horde_String::validUtf8($text)) {
                        return $text;
                    }
                }
            }
            // Invalid UTF-8 still found. Strip out non 7-bit characters, or if
            // that fails, force a conversion to UTF-8 as a last resort. Need
            // to break string into smaller chunks to avoid hitting
            // https://bugs.php.net/bug.php?id=37793
            $chunk_size = 4000;
            $text = '';
            while ($data !== false && strlen($data)) {
                $test = self::_stripNon7BitChars(substr($data, 0, $chunk_size));
                if ($test !== false) {
                    $text .= $test;
                } else {
                    return Horde_String::convertCharset($data, $from_charset, 'UTF-8', true);
                }
                $data = substr($data, $chunk_size);
            }
        }

        return $text;
    }

    /**
     * Strip out non 7Bit characters from a text string.
     *
     * @param string $text  The string to strip.
     *
     * @return string|boolean  The stripped string, or false if failed.
     */
    protected static function _stripNon7BitChars($text)
    {
        return preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $text);
    }

}