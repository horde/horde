<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 */

/**
 * Handle MIME content parameters (RFC 2045; 2183; 2231).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 */
class Horde_Mime_ContentParam
{
    /**
     * Valid atext but not tspecials characters.
     *
     * See RFC 2045 [5.1]
     */
     const ATEXT_NON_TSPECIAL = '!#$%&\'*+-.0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ^_`abcdefghijklmnopqrstuvwxyz{|}~';

    /**
     * Content-Type parameters.
     *
     * @var array
     */
    public $params = array();

    /**
     * Content-Type value.
     *
     * @var string
     */
    public $value;

    /**
     * Constructor.
     *
     * @param mixed $data  Either an array (interpreted as a list of
     *                     parameters) or a string (interpreted as a RFC
     *                     encoded parameter list).
     */
    public function __construct($data = null)
    {
        if (!is_null($data)) {
            if (is_array($data)) {
                $this->params = $data;
            } else {
                $this->decode($data);
            }
        }
    }

    /**
     * Encodes a MIME content parameter string pursuant to RFC 2183 & 2231
     * (Content-Type and Content-Disposition headers).
     *
     * @param array $opts  Options:
     *   - broken_rfc2231: (boolean) Attempt to work around non-RFC
     *                     2231-compliant MUAs by generating both a RFC
     *                     2047-like parameter name and also the correct RFC
     *                     2231 parameter
     *                     DEFAULT: false
     *   - charset: (string) The charset to encode to.
     *              DEFAULT: UTF-8
     *   - lang: (string) The language to use when encoding.
     *           DEFAULT: None specified
     *
     * @return array  The encoded parameter string (US-ASCII).
     */
    public function encode(array $opts = array())
    {
        $opts = array_merge(array(
            'charset' => 'UTF-8',
        ), $opts);

        $out = array();

        foreach ($this->params as $key => $val) {
            $out = array_merge($out, $this->_encode($key, $val, $opts));
        }

        return $out;
    }

    /**
     * @see encode()
     */
    protected function _encode($name, $val, $opts)
    {
        $curr = 0;
        $encode = $wrap = false;
        $out = array();

        // 2 = '=', ';'
        $pre_len = strlen($name) + 2;

        /* Several possibilities:
         *   - String is ASCII. Output as ASCII (duh).
         *   - Language information has been provided. We MUST encode output
         *     to include this information.
         *   - String is non-ASCII, but can losslessly translate to ASCII.
         *     Output as ASCII (most efficient).
         *   - String is in non-ASCII, but doesn't losslessly translate to
         *     ASCII. MUST encode output (duh). */
        if (empty($opts['lang']) && !Horde_Mime::is8bit($val, 'UTF-8')) {
            $string = $val;
        } else {
            $cval = Horde_String::convertCharset($val, 'UTF-8', $opts['charset']);
            $string = Horde_String::lower($opts['charset']) . '\'' . (empty($opts['lang']) ? '' : Horde_String::lower($opts['lang'])) . '\'' . rawurlencode($cval);
            $encode = true;
            /* Account for trailing '*'. */
            ++$pre_len;
        }

        if (($pre_len + strlen($string)) > 75) {
            /* Account for continuation '*'. */
            ++$pre_len;
            $wrap = true;

            while ($string) {
                $chunk = 75 - $pre_len - strlen($curr);
                $pos = min($chunk, strlen($string) - 1);

                /* Don't split in the middle of an encoded char. */
                if (($chunk == $pos) && ($pos > 2)) {
                    for ($i = 0; $i <= 2; ++$i) {
                        if ($string[$pos - $i] == '%') {
                            $pos -= $i + 1;
                            break;
                        }
                    }
                }

                $lines[] = substr($string, 0, $pos + 1);
                $string = substr($string, $pos + 1);
                ++$curr;
            }
        } else {
            $lines = array($string);
        }

        foreach ($lines as $i => $line) {
            $out[$name . (($wrap) ? ('*' . $i) : '') . (($encode) ? '*' : '')] = $line;
        }

        if (!empty($opts['broken_rfc2231']) && !isset($out[$name])) {
            $out = array_merge(array(
                $name => Horde_Mime::encode($val, $opts['charset'])
            ), $out);
        }

        /* Escape certain characters in params (See RFC 2045 [Appendix A]).
         * Must be quoted-string if one of these exists.
         * Forbidden: SPACE, CTLs, ()<>@,;:\"/[]?= */
        foreach ($out as $k => $v) {
            if (strlen($v) !== strspn($v, self::ATEXT_NON_TSPECIAL)) {
                $out[$k] = '"' . addcslashes($v, '\\"') . '"';
            }
        }

        return $out;
    }

    /**
     * Decodes a MIME content parameter string pursuant to RFC 2183 & 2231
     * (Content-Type and Content-Disposition headers).
     *
     * Stores value/parameter data in the current object.
     *
     * @param mixed $data  Parameter data. Either an array or a string.
     */
    public function decode($data)
    {
        $convert = array();

        $this->params = array();
        $this->value = null;

        if (is_array($data)) {
            $params = $data;
        } else {
            $parts = explode(';', $data, 2);
            if (count($parts) === 1) {
                $param = $parts[0];
            } else {
                $value = trim($parts[0]);
                if (strlen($value)) {
                    $this->decode($parts[0]);
                    if (empty($this->params)) {
                        $this->value = $value;
                    }
                }
                $param = $parts[1];
            }

            $decode = new Horde_Mime_ContentParam_Decode();
            $params = $decode->decode($param);
        }

        /* Sort the params list. Prevents us from having to manually keep
         * track of continuation values below. */
        uksort($params, 'strnatcasecmp');

        foreach ($params as $name => $val) {
            /* Asterisk at end indicates encoded value. */
            if (substr($name, -1) == '*') {
                $name = substr($name, 0, -1);
                $encoded = true;
            } else {
                $encoded = false;
            }

            /* This asterisk indicates continuation parameter. */
            if ((($pos = strrpos($name, '*')) !== false) &&
                is_numeric(substr($name, $pos + 1))) {
                $name = substr($name, 0, $pos);
            }

            if (isset($this->params[$name])) {
                $this->params[$name] .= $val;
            } else {
                $this->params[$name] = $val;
            }

            if ($encoded) {
                $convert[$name] = true;
            }
        }

        foreach (array_keys($convert) as $name) {
            $val = $this->params[$name];
            $quote = strpos($val, "'");

            if ($quote === false) {
                $this->params[$name] = urldecode($val);
            } else {
                $orig_charset = substr($val, 0, $quote);
                if (Horde_String::lower($orig_charset) == 'iso-8859-1') {
                    $orig_charset = 'windows-1252';
                }

                /* Ignore language. */
                $quote = strpos($val, "'", $quote + 1);
                substr($val, $quote + 1);
                $this->params[$name] = Horde_String::convertCharset(
                    urldecode(substr($val, $quote + 1)),
                    $orig_charset,
                    'UTF-8'
                );
            }
        }

        /* MIME parameters are supposed to be encoded via RFC 2231, but many
         * mailers do RFC 2045 encoding instead. However, if we see at least
         * one RFC 2231 encoding, then assume the sending mailer knew what
         * it was doing and didn't send any parameters RFC 2045 encoded. */
        if (empty($convert)) {
            foreach ($this->params as $key => $val) {
                $this->params[$key] = Horde_Mime::decode($val);
            }
        }

        if (empty($this->params) && is_string($data)) {
            $this->value = trim($parts[0]);
        }
    }

}
