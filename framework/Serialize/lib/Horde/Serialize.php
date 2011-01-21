<?php
/**
 * The Serialize:: class provides various methods of encapsulating data.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Stephane Huther <shuther1@free.fr>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @package  Horde_Serialize
 * @category Horde
 */
class Horde_Serialize
{
    /* Type constants */
    const UNKNOWN = -1;
    const NONE = 0;
    const WDDX = 1;
    const BZIP = 2;
    const IMAP8 = 3;
    const IMAPUTF7 = 4;
    const IMAPUTF8 = 5;
    const BASIC = 6;
    const GZ_DEFLATE = 7;
    const GZ_COMPRESS = 8;
    const GZ_ENCODE = 9;
    const BASE64 = 10;
    const SQLXML = 11;
    const RAW = 12;
    const URL = 13;
    const UTF7 = 14;
    const UTF7_BASIC = 15;
    const JSON = 16;
    const LZF = 17;

    /**
     * Serialize a value.
     *
     * See the list of constants at the top of the file for the serializing
     * techniques that can be used.
     *
     * @param mixed $data    The data to be serialized.
     * @param mixed $mode    The mode of serialization. Can be either a single
     *                       mode or array of modes.  If array, will be
     *                       serialized in the order provided.
     * @param mixed $params  Any additional parameters the serialization method
     *                       requires.
     *
     * @return string  The serialized data.
     * @throws Horde_Serialize_Exception
     */
    static public function serialize($data, $mode = array(self::BASIC),
                                     $params = null)
    {
        if (!is_array($mode)) {
            $mode = array($mode);
        }

        /* Parse through the list of serializing modes. */
        foreach ($mode as $val) {
            /* Check to make sure the mode is supported. */
            if (!self::hasCapability($val)) {
                throw new Horde_Serialize_Exception('Unsupported serialization type');
            }
            $data = self::_serialize($data, $val, $params);
        }

        return $data;
    }

    /**
     * Unserialize a value.
     *
     * See the list of constants at the top of the file for the serializing
     * techniques that can be used.
     *
     * @param mixed $data    The data to be unserialized.
     * @param mixed $mode    The mode of unserialization.  Can be either a
     *                       single mode or array of modes.  If array, will be
     *                       unserialized in the order provided.
     * @param mixed $params  Any additional parameters the unserialization
     *                       method requires.
     *
     * @return string  The unserialized data.
     * @throws Horde_Serialize_Exception
     */
    static public function unserialize($data, $mode = self::BASIC,
                                       $params = null)
    {
        if (!is_array($mode)) {
            $mode = array($mode);
        }

        /* Parse through the list of unserializing modes. */
        foreach ($mode as $val) {
            /* Check to make sure the mode is supported. */
            if (!self::hasCapability($val)) {
                throw new Horde_Serialize_Exception('Unsupported unserialization type');
            }
            $data = self::_unserialize($data, $val, $params);
        }

        return $data;
    }

    /**
     * Check whether or not a serialization method is supported.
     *
     * @param integer $mode  The serialization method.
     *
     * @return boolean  True if supported, false if not.
     */
    static public function hasCapability($mode)
    {
        switch ($mode) {
        case self::BZIP:
            return Horde_Util::extensionExists('bz2');

        case self::WDDX:
            return Horde_Util::extensionExists('wddx');

        case self::IMAPUTF7:
            return class_exists('Horde_Imap_Client');

        case self::IMAP8:
        case self::IMAPUTF8:
            return class_exists('Horde_Mime');

        case self::GZ_DEFLATE:
        case self::GZ_COMPRESS:
        case self::GZ_ENCODE:
            return Horde_Util::extensionExists('zlib');

        case self::SQLXML:
            return @include_once 'XML/sql2xml.php';

        case self::LZF:
            return Horde_Util::extensionExists('lzf');

        case self::NONE:
        case self::BASIC:
        case self::BASE64:
        case self::RAW:
        case self::URL:
        case self::UTF7:
        case self::UTF7_BASIC:
        case self::JSON:
            return true;

        default:
            return false;
        }
    }

    /**
     * Serialize data.
     *
     * @param mixed $data    The data to be serialized.
     * @param mixed $mode    The mode of serialization. Can be
     *                       either a single mode or array of modes.
     *                       If array, will be serialized in the
     *                       order provided.
     * @param mixed $params  Any additional parameters the serialization method
     *                       requires.
     *
     * @return string  A serialized string.
     * @throws Horde_Serialize_Exception
     */
    static protected function _serialize($data, $mode, $params = null)
    {
        switch ($mode) {
        case self::NONE:
            break;

        // $params['level'] = Level of compression (default: 3)
        // $params['workfactor'] = How does compression phase behave when given
        //                         worst case, highly repetitive, input data
        //                         (default: 30)
        case self::BZIP:
            $data = bzcompress($data, isset($params['level']) ? $params['level'] : 3, isset($params['workfactor']) ? $params['workfactor'] : 30);
            if (is_integer($data)) {
                $data = false;
            }
            break;

        case self::WDDX:
            $data = wddx_serialize_value($data);
            break;

        case self::IMAP8:
            $data = Horde_Mime::quotedPrintableEncode($data);
            break;

        case self::IMAPUTF7:
            $data = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap(Horde_String::convertCharset($data, 'ISO-8859-1', 'UTF-8'));
            break;

        case self::IMAPUTF8:
            $data = Horde_Mime::decode($data, 'UTF-8');
            break;

        // $params['level'] = Level of compression (default: 3)
        case self::GZ_DEFLATE:
            $data = gzdeflate($data, isset($params['level']) ? $params['level'] : 3);
            break;

        case self::BASIC:
            $data = serialize($data);
            break;

        // $params['level'] = Level of compression (default: 3)
        case self::GZ_COMPRESS:
            $data = gzcompress($data, isset($params['level']) ? $params['level'] : 3);
            break;

        case self::BASE64:
            $data = base64_encode($data);
            break;

        // $params['level'] = Level of compression (default: 3)
        case self::GZ_ENCODE:
            $data = gzencode($data, isset($params['level']) ? $params['level'] : 3);
            break;

        case self::RAW:
            $data = rawurlencode($data);
            break;

        case self::URL:
            $data = urlencode($data);
            break;

        case self::SQLXML:
            require_once 'DB.php';
            $sql2xml = new xml_sql2xml();
            $data = $sql2xml->getXML($data);
            break;

        // $params = Source character set
        case self::UTF7:
            $data = Horde_String::convertCharset($data, $params, 'UTF-7');
            break;

        // $params = Source character set
        case self::UTF7_BASIC:
            $data = self::serialize($data, array(self::UTF7, self::BASIC), $params);
            break;

        case self::JSON:
            $tmp = json_encode($data);

            /* Basic error handling attempts. Requires PHP 5.3+.
             * Error code 5, although not documented, indicates non UTF-8
             * data. */
            if (function_exists('json_last_error') &&
                (json_last_error() == 5)) {
                $data = json_encode(Horde_String::convertCharset($data, $params, 'UTF-8', true));
            } else {
                $data = $tmp;
            }
            break;

        case self::LZF:
            $data = lzf_compress($data);
            break;
        }

        if ($data === false) {
            throw new Horde_Serialize_Exception('Serialization failed.');
        }
        return $data;
    }

    /**
     * Unserialize data.
     *
     * @param mixed $data    The data to be unserialized.
     * @param mixed $mode    The mode of unserialization. Can be either a
     *                       single mode or array of modes.  If array, will be
     *                       unserialized in the order provided.
     * @param mixed $params  Any additional parameters the unserialization
     *                       method requires.
     *
     * @return mixed  Unserialized data.
     * @throws Horde_Serialize_Exception
     */
    static protected function _unserialize(&$data, $mode, $params = null)
    {
        switch ($mode) {
        case self::NONE:
        case self::SQLXML:
            break;

        case self::RAW:
            $data = rawurldecode($data);
            break;

        case self::URL:
            $data = urldecode($data);
            break;

        case self::WDDX:
            $data = wddx_deserialize($data);
            break;

        case self::BZIP:
            // $params['small'] = Use bzip2 'small memory' mode?
            $data = bzdecompress($data, isset($params['small']) ? $params['small'] : false);
            break;

        case self::IMAP8:
            $data = quoted_printable_decode($data);
            break;

        case self::IMAPUTF7:
            $data = Horde_String::convertCharset(Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($data), 'UTF-8', 'ISO-8859-1');
            break;

        case self::IMAPUTF8:
            $data = Horde_Mime::encode($data, 'UTF-8');
            break;

        case self::BASIC:
            $data2 = @unserialize($data);
            // Unserialize can return false both on error and if $data is the
            // false value.
            if (($data2 === false) && ($data == serialize(false))) {
                return $data2;
            }
            $data = $data2;
            break;

        case self::GZ_DEFLATE:
            $data = gzinflate($data);
            break;

        case self::BASE64:
            $data = base64_decode($data);
            break;

        case self::GZ_COMPRESS:
            $data = gzuncompress($data);
            break;

        // $params = Output character set
        case self::UTF7:
            $data = Horde_String::convertCharset($data, 'utf-7', $params);
            break;

        // $params = Output character set
        case self::UTF7_BASIC:
            $data = self::unserialize($data, array(self::BASIC, self::UTF7), $params);
            break;

        case self::JSON:
            $out = json_decode($data);
            if (!is_null($out) || (strcasecmp($data, 'null') === 0)) {
                return $out;
            }
            break;

        case self::LZF:
            $data = @lzf_decompress($data);
            break;
        }

        if ($data === false) {
            throw new Horde_Serialize_Exception('Unserialization failed.');
        }

        return $data;
    }

}
