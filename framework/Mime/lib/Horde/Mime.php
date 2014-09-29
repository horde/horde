<?php
/**
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * Provide methods for dealing with MIME encoding (RFC 2045-2049);
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */
class Horde_Mime
{
    /**
     * The RFC defined EOL string.
     *
     * @var string
     */
    const EOL = "\r\n";

    /**
     * Use windows-1252 charset when decoding ISO-8859-1 data?
     *
     * @var boolean
     */
    static public $decodeWindows1252 = false;

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
        return ($string != Horde_String::convertCharset($string, $charset, 'US-ASCII'));
    }

    /**
     * MIME encodes a string (RFC 2047).
     *
     * @param string $text     The text to encode (UTF-8).
     * @param string $charset  The character set to encode to.
     *
     * @return string  The MIME encoded string (US-ASCII).
     */
    static public function encode($text, $charset = 'UTF-8')
    {
        /* The null character is valid US-ASCII, but was removed from the
         * allowed e-mail header characters in RFC 2822. */
        if (!self::is8bit($text, 'UTF-8') && (strpos($text, null) === false)) {
            return $text;
        }

        $charset = Horde_String::lower($charset);
        $text = Horde_String::convertCharset($text, 'UTF-8', $charset);

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
     * Internal helper function to MIME encode a string.
     *
     * @param string $text     The text to encode.
     * @param string $charset  The character set of the text.
     *
     * @return string  The MIME encoded text.
     */
    static protected function _encode($text, $charset)
    {
        $encoded = trim(base64_encode($text));
        $c_size = strlen($charset) + 7;

        if ((strlen($encoded) + $c_size) > 75) {
            $parts = explode(self::EOL, rtrim(chunk_split($encoded, intval((75 - $c_size) / 4) * 4)));
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
                $out .= self::EOL . ' ';
            }
        }

        return $out;
    }

    /**
     * Encodes a line via quoted-printable encoding.
     *
     * @param string $text   The text to encode (UTF-8).
     * @param string $eol    The EOL sequence to use.
     * @param integer $wrap  Wrap a line at this many characters.
     *
     * @return string  The quoted-printable encoded string.
     */
    static public function quotedPrintableEncode($text, $eol = self::EOL,
                                                 $wrap = 76)
    {
        $curr_length = 0;
        $output = '';

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
                $char = '=' . Horde_String::upper(sprintf('%02s', dechex($ascii)));
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
     * Decodes a MIME encoded (RFC 2047) string.
     *
     * @param string $string  The MIME encoded text.
     *
     * @return string  The decoded text.
     */
    static public function decode($string)
    {
        $old_pos = 0;
        $out = '';

        while (($pos = strpos($string, '=?', $old_pos)) !== false) {
            /* Save any preceding text, if it is not LWSP between two
             * encoded words. */
            $pre = substr($string, $old_pos, $pos - $old_pos);
            if (!$old_pos ||
                (strspn($pre, " \t\n\r") != strlen($pre))) {
                $out .= $pre;
            }

            /* Search for first delimiting question mark (charset). */
            if (($d1 = strpos($string, '?', $pos + 2)) === false) {
                break;
            }

            $orig_charset = substr($string, $pos + 2, $d1 - $pos - 2);
            if (self::$decodeWindows1252 &&
                (Horde_String::lower($orig_charset) == 'iso-8859-1')) {
                $orig_charset = 'windows-1252';
            }

            /* Search for second delimiting question mark (encoding). */
            if (($d2 = strpos($string, '?', $d1 + 1)) === false) {
                break;
            }

            $encoding = substr($string, $d1 + 1, $d2 - $d1 - 1);

            /* Search for end of encoded data. */
            if (($end = strpos($string, '?=', $d2 + 1)) === false) {
                break;
            }

            $encoded_text = substr($string, $d2 + 1, $end - $d2 - 1);

            switch ($encoding) {
            case 'Q':
            case 'q':
                $out .= Horde_String::convertCharset(
                    quoted_printable_decode(
                        str_replace('_', ' ', $encoded_text)
                    ),
                    $orig_charset,
                    'UTF-8'
                );
            break;

            case 'B':
            case 'b':
                $out .= Horde_String::convertCharset(
                    base64_decode($encoded_text),
                    $orig_charset,
                    'UTF-8'
                );
            break;

            default:
                // Ignore unknown encoding.
                break;
            }

            $old_pos = $end + 2;
        }

        return $out . substr($string, $old_pos);
    }

    /* Deprecated methods. */

    /**
     * @deprecated  Use Horde_Mime_Headers#generateMessageId() instead.
     */
    static public function generateMessageId()
    {
        $hdr = new Horde_Mime_Headers();
        return $hdr->generateMessageId();
    }

    /**
     * @deprecated  Use Horde_Mime_Uudecode instead.
     */
    static public function uudecode($input)
    {
        $uudecode = new Horde_Mime_Uudecode($input);
        return iterator_to_array($input);
    }

    /**
     * @deprecated
     */
    static public $brokenRFC2231 = false;

    /**
     * @deprecated
     */
    const MIME_PARAM_QUOTED = '/[\x01-\x20\x22\x28\x29\x2c\x2f\x3a-\x40\x5b-\x5d]/';

    /**
     * @deprecated  Use Horde_Mime_ContentParam#encode() instead.
     */
    static public function encodeParam($name, $val, array $opts = array())
    {
        $cp = new Horde_Mime_ContentParam(array($name => $val));

        return $cp->encode(array_merge(array(
            'broken_rfc2231' => self::$brokenRFC2231
        ), $opts));
    }

    /**
     * @deprecated  Use Horde_Mime_ContentParam instead.
     */
    static public function decodeParam($type, $data)
    {
        $cp = new Horde_Mime_ContentParam();
        $cp->decode($data);

        if (!strlen($cp->value)) {
            $cp->value = (Horde_String::lower($type) == 'content-type')
                ? 'text/plain'
                : 'attachment';
        }

        return array(
            'params' => $cp->params,
            'val' => $cp->value
        );
    }

    /**
     * @deprecated  Use Horde_Mime_Id instead.
     */
    static public function mimeIdArithmetic($id, $action, $options = array())
    {
        $id_ob = new Horde_Mime_Id($id);

        switch ($action) {
        case 'down':
            $action = $id_ob::ID_DOWN;
            break;

        case 'next':
            $action = $id_ob::ID_NEXT;
            break;

        case 'prev':
            $action = $id_ob::ID_PREV;
            break;

        case 'up':
            $action = $id_ob::ID_UP;
            break;
        }

        return $id_ob->idArithmetic($action, $options);
    }

    /**
     * @deprecated  Use Horde_Mime_Id instead.
     */
    static public function isChild($base, $id)
    {
        $id_ob = new Horde_Mime_Id($base);
        return $id_ob->isChild($id);
    }

}
