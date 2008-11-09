<?php
/**
 * The MIME:: class provides methods for dealing with various MIME (see, e.g.,
 * RFC 2045) standards.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@curecanti.org>
 * @package Horde_MIME
 */
class Horde_MIME
{
    /**
     * Determines if a string contains 8-bit (non US-ASCII) characters.
     *
     * @param string $string   The string to check.
     * @param string $charset  The charset of the string. Defaults to
     *                         US-ASCII.
     *
     * @return boolean  True if string contains non US-ASCII characters.
     */
    static public function is8bit($string, $charset = null)
    {
        /* ISO-2022-JP is a 7bit charset, but it is an 8bit representation so
         * it needs to be entirely encoded. */
        return is_string($string) &&
               ((stristr('iso-2022-jp', $charset) &&
                (strstr($string, "\x1b\$B"))) ||
                preg_match('/[\x80-\xff]/', $string));
    }

    /**
     * Encodes a string containing non-ASCII characters according to RFC 2047.
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    static public function encode($text, $charset = null)
    {
        if (is_null($charset)) {
            require_once 'Horde/NLS.php';
            $charset = NLS::getCharset();
        }
        $charset = String::lower($charset);

        if (($charset == 'us-ascii') || !self::is8bit($text, $charset)) {
            return $text;
        }

        /* Get the list of elements in the string. */
        $size = preg_match_all('/([^\s]+)([\s]*)/', $text, $matches, PREG_SET_ORDER);

        $line = '';

        /* Return if nothing needs to be encoded. */
        foreach ($matches as $key => $val) {
            if (self::is8bit($val[1], $charset)) {
                if ((($key + 1) < $size) &&
                    self::is8bit($matches[$key + 1][1], $charset)) {
                    $line .= self::_encode($val[1] . $val[2], $charset) . ' ';
                } else {
                    $line .= self::_encode($val[1], $charset) . $val[2];
                }
            } else {
                $line .= $val[1] . $val[2];
            }
        }

        return rtrim($line);
    }

    /**
     * Internal recursive function to RFC 2047 encode a string.
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters.
     */
    static protected function _encode($text, $charset)
    {
        $encoded = trim(base64_encode($text));
        $c_size = strlen($charset) + 7;

        if ((strlen($encoded) + $c_size) > 75) {
            $parts = explode("\r\n", rtrim(chunk_split($encoded, intval((75 - $c_size) / 4) * 4)));
        } else {
            $parts[] = $encoded;
        }

        $p_size = count($parts);
        $out = '';

        foreach ($parts as $key => $val) {
            $out .= '=?' . $charset . '?b?' . $val . '?=';
            if ($p_size > $key + 1) {
                /* RFC 2047 [2]: no encoded word can be more than 75
                 * characters long. If longer, you must split the word with
                 * CRLF SPACE. */
                $out .= "\r\n ";
            }
        }

        return $out;
    }

    /**
     * Encodes a line via quoted-printable encoding.
     *
     * @param string $text   The text to encode.
     * @param string $eol    The EOL sequence to use.
     * @param integer $wrap  Wrap a line at this many characters.
     *
     * @return string  The quoted-printable encoded string.
     */
    static public function quotedPrintableEncode($text, $eol, $wrap = 76)
    {
        $line = $output = '';
        $curr_length = 0;

        /* We need to go character by character through the data. */
        for ($i = 0, $length = strlen($text); $i < $length; ++$i) {
            $char = $text[$i];

            /* If we have reached the end of the line, reset counters. */
            if ($char == "\n") {
                $output .= $eol;
                $curr_length = 0;
                continue;
            } elseif ($char == "\r") {
                continue;
            }

            /* Spaces or tabs at the end of the line are NOT allowed. Also,
             * ASCII characters below 32 or above 126 AND 61 must be
             * encoded. */
            $ascii = ord($char);
            if ((($ascii === 32) &&
                 ($i + 1 != $length) &&
                 (($text[$i + 1] == "\n") || ($text[$i + 1] == "\r"))) ||
                (($ascii < 32) || ($ascii > 126) || ($ascii === 61))) {
                $char_len = 3;
                $char = '=' . String::upper(sprintf('%02s', dechex($ascii)));
            } else {
                $char_len = 1;
            }

            /* Lines must be $wrap characters or less. */
            $curr_length += $char_len;
            if ($curr_length > $wrap) {
                $output .= '=' . $eol;
                $curr_length = $char_len;
            }
            $output .= $char;
        }

        return $output;
    }

    /**
     * Encodes a string containing email addresses according to RFC 2047.
     *
     * This differs from encode() because it keeps email addresses legal, only
     * encoding the personal information.
     *
     * @param mixed $addresses   The email addresses to encode (either a
     *                           string or an array of addresses).
     * @param string $charset    The character set of the text.
     * @param string $defserver  The default domain to append to mailboxes.
     *
     * @return string  The text, encoded only if it contains non-ASCII
     *                 characters, or PEAR_Error on error.
     */
    static public function encodeAddress($addresses, $charset = null,
                                         $defserver = null)
    {
        if (!is_array($addresses)) {
            /* parseAddressList() does not process the null entry
             * 'undisclosed-recipients:;' correctly. */
            $addresses = trim($addresses);
            if (preg_match('/undisclosed-recipients:\s*;/i', $addresses)) {
                return $addresses;
            }

            $addresses = Horde_MIME_Address::parseAddressList($addresses, array('defserver' => $defserver, 'nestgroups' => true));
            if (is_a($addresses, 'PEAR_Error')) {
                return $addresses;
            }
        }

        $text = '';
        foreach ($addresses as $addr) {
            // Check for groups.
            if (empty($addr['groupname'])) {
                if (empty($addr['personal'])) {
                    $personal = '';
                } else {
                    if (($addr['personal'][0] == '"') &&
                        (substr($addr['personal'], -1) == '"')) {
                        $addr['personal'] = stripslashes(substr($addr['personal'], 1, -1));
                    }
                    $personal = self::encode($addr['personal'], $charset);
                }
                $text .= Horde_MIME_Address::writeAddress($addr['mailbox'], $addr['host'], $personal) . ', ';
            } else {
                $text .= Horde_MIME_Address::writeGroupAddress($addr['groupname'], $addr['addresses']) . ' ';
            }
        }

        return rtrim($text, ' ,');
    }

    /**
     * Decodes an RFC 2047-encoded string.
     *
     * @param string $string      The text to decode.
     * @param string $to_charset  The charset that the text should be decoded
     *                            to.
     *
     * @return string  The decoded text.
     */
    static public function decode($string, $to_charset = null)
    {
        if (($pos = strpos($string, '=?')) === false) {
            return $string;
        }

        /* Take out any spaces between multiple encoded words. */
        $string = preg_replace('|\?=\s+=\?|', '?==?', $string);

        /* Save any preceding text. */
        $preceding = substr($string, 0, $pos);

        $search = substr($string, $pos + 2);
        $d1 = strpos($search, '?');
        if ($d1 === false) {
            return $string;
        }

        $charset = substr($string, $pos + 2, $d1);
        $search = substr($search, $d1 + 1);

        $d2 = strpos($search, '?');
        if ($d2 === false) {
            return $string;
        }

        $encoding = substr($search, 0, $d2);
        $search = substr($search, $d2 + 1);

        $end = strpos($search, '?=');
        if ($end === false) {
            $end = strlen($search);
        }

        $encoded_text = substr($search, 0, $end);
        $rest = substr($string, (strlen($preceding . $charset . $encoding . $encoded_text) + 6));

        if (is_null($to_charset)) {
            require_once 'Horde/NLS.php';
            $to_charset = NLS::getCharset();
        }

        switch ($encoding) {
        case 'Q':
        case 'q':
            $decoded = preg_replace('/=([0-9a-f]{2})/ie', 'chr(0x\1)', str_replace('_', ' ', $encoded_text));
            $decoded = String::convertCharset($decoded, $charset, $to_charset);
            break;

        case 'B':
        case 'b':
            $decoded = String::convertCharset(base64_decode($encoded_text), $charset, $to_charset);
            break;

        default:
            $decoded = '=?' . $charset . '?' . $encoding . '?' . $encoded_text . '?=';
            break;
        }

        return $preceding . $decoded . self::decode($rest, $to_charset);
    }

    /**
     * Decodes an RFC 2047-encoded address string.
     *
     * @param string $string      The text to decode.
     * @param string $to_charset  The charset that the text should be decoded
     *                            to.
     *
     * @return string  The decoded text.
     */
    static public function decodeAddrString($string, $to_charset = null)
    {
        $addr_list = array();
        foreach (Horde_MIME_Address::parseAddressList($string) as $ob) {
            $ob['personal'] = isset($ob['personal'])
                ? self::decode($ob['personal'], $to_charset)
                : '';
            $addr_list[] = $ob;
        }

        return Horde_MIME_Address::addrArray2String($addr_list);
    }

    /**
     * Encodes a parameter string pursuant to RFC 2231.
     *
     * @param string $name     The parameter name.
     * @param string $string   The string to encode.
     * @param string $charset  The charset the text should be encoded with.
     * @param string $lang     The language to use when encoding.
     *
     * @return array  The encoded parameter string.
     */
    static public function encodeParamString($name, $string, $charset,
                                             $lang = null)
    {
        $encode = $wrap = false;
        $output = array();

        if (self::is8bit($string, $charset)) {
            $string = String::lower($charset) . '\'' . (is_null($lang) ? '' : String::lower($lang)) . '\'' . rawurlencode($string);
            $encode = true;
        }

        // 4 = '*', 2x '"', ';'
        $pre_len = strlen($name) + 4 + (($encode) ? 1 : 0);
        if (($pre_len + strlen($string)) > 76) {
            while ($string) {
                $chunk = 76 - $pre_len;
                $pos = min($chunk, strlen($string) - 1);
                if (($chunk == $pos) && ($pos > 2)) {
                    for ($i = 0; $i <= 2; $i++) {
                        if ($string[$pos-$i] == '%') {
                            $pos -= $i + 1;
                            break;
                        }
                    }
                }
                $lines[] = substr($string, 0, $pos + 1);
                $string = substr($string, $pos + 1);
            }
            $wrap = true;
        } else {
            $lines = array($string);
        }

        $i = 0;
        foreach ($lines as $val) {
            $output[] =
                $name .
                (($wrap) ? ('*' . $i++) : '') .
                (($encode) ? '*' : '') .
                '="' . $val . '"';
        }

        return implode('; ', $output);
    }

    /**
     * Decodes a parameter string encoded pursuant to RFC 2231.
     *
     * @param string $string      The entire string to decode, including the
     *                            parameter name.
     * @param string $to_charset  The charset the text should be decoded to.
     *
     * @return array  The decoded text, or the original string if it was not
     *                encoded.
     */
    static public function decodeParamString($string, $to_charset = null)
    {
        if (($pos = strpos($string, '*')) === false) {
            return false;
        }

        if (!isset($to_charset)) {
            require_once 'Horde/NLS.php';
            $to_charset = NLS::getCharset();
        }

        $attribute = substr($string, 0, $pos);
        $charset = $lang = null;
        $output = '';

        /* Get the character set and language used in the encoding, if
         * any. */
        if (preg_match("/^[^=]+\*\=([^']*)'([^']*)'/", $string, $matches)) {
            $charset = $matches[1];
            $lang = $matches[2];
            $string = str_replace($charset . "'" . $lang . "'", '', $string);
        }

        $lines = preg_split('/\s*' . preg_quote($attribute) . '(?:\*\d)*/', $string);
        foreach ($lines as $line) {
            if (strpos($line, '*=') === 0) {
                $output .= urldecode(str_replace(array('_', '='), array('%20', '%'), substr($line, 2)));
            } else {
                $output .= substr($line, 1);
            }
        }

        /* RFC 2231 uses quoted printable encoding. */
        if (!is_null($charset)) {
            $output = String::convertCharset($output, $charset, $to_charset);
        }

        return array(
            'attribute' => $attribute,
            'value' => $output
        );
    }

    /**
     * Generates a Message-ID string conforming to RFC 2822 [3.6.4] and the
     * standards outlined in 'draft-ietf-usefor-message-id-01.txt'.
     *
     * @param string  A message ID string.
     */
    static public function generateMessageID()
    {
        return '<' . date('YmdHis') . '.' . self::generateRandomID() . '@' . $_SERVER['SERVER_NAME'] . '>';
    }

    /**
     * Generates a Random-ID string suitable for use with MIME features that
     * require a random string.
     *
     * @return string  A random string.
     */
    static public function generateRandomID()
    {
        return base_convert(dechex(strtr(microtime(), array('0.' => '', ' ' => ''))) . uniqid(), 16, 36);
    }
}
