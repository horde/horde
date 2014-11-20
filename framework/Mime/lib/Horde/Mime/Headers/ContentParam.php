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
 * This class represents a header element that contains MIME content
 * parameters (RFCs 2045, 2183, 2231).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mime
 * @since     2.5.0
 *
 * @property-read array $params  Content parameters.
 */
class Horde_Mime_Headers_ContentParam
    extends Horde_Mime_Headers_Element_Single
    implements ArrayAccess, Serializable
{
    /**
     * Content parameters.
     *
     * @var Horde_Support_CaseInsensitiveArray
     */
    protected $_params;

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'full_value':
            $tmp = $this->value;
            foreach ($this->_escapeParams($this->_params) as $key => $val) {
                $tmp .= '; ' . $key . '=' . $val;
            }
            return $tmp;

        case 'params':
            return $this->_params->getArrayCopy();
        }

        return parent::__get($name);
    }

    /**
     * @param mixed $data  Either an array (interpreted as a list of
     *                     parameters), a string (interpreted as a RFC
     *                     encoded parameter list), an object with two
     *                     properties: value and params, or a
     *                     Horde_Mime_Headers_ContentParam object.
     */
    protected function _setValue($data)
    {
        if (!$this->_params) {
            $this->_params = new Horde_Support_CaseInsensitiveArray();
        }

        if ($data instanceof Horde_Mime_Headers_ContentParam) {
            if (empty($this->_values)) {
                $this->_values = array($data->value);
            }
            $data = $data->params;
        } elseif (is_object($data)) {
            $this->_values = array($data->value);
            $data = $data->params;
        }

        $this->decode($data);
    }

    /**
     * @param array $opts  See encode().
     */
    protected function _sendEncode($opts)
    {
        $out = $this->value;

        foreach ($this->encode($opts) as $key => $val) {
            $out .= '; ' . $key . '=' . $val;
        }

        return array($out);
    }

    /**
     */
    public static function getHandles()
    {
        return array(
            // MIME: RFC 2045
            'content-type',
            // MIME: RFC 2183
            'content-disposition'
        );
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

        foreach ($this->_params as $key => $val) {
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

        /* Escape characters in params (See RFC 2045 [Appendix A]).
         * Must be quoted-string if one of these exists. */
        return $this->_escapeParams($out);
    }

    /**
     * Escape the parameter array.
     *
     * @param array $params  Parameter array.
     *
     * @return array  Escaped parameter array.
     */
    protected function _escapeParams($params)
    {
        foreach ($params as $k => $v) {
            foreach (str_split($v) as $c) {
                if (!Horde_Mime_ContentParam_Decode::isAtextNonTspecial($c)) {
                    $params[$k] = '"' . addcslashes($v, '\\"') . '"';
                    break;
                }
            }
        }

        return $params;
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

        $this->_params = new Horde_Support_CaseInsensitiveArray();
        $this->_values = array();

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
                    if (!count($this->_params)) {
                        $this->_values = array($value);
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

            if (isset($this->_params[$name])) {
                $this->_params[$name] .= $val;
            } else {
                $this->_params[$name] = $val;
            }

            if ($encoded) {
                $convert[$name] = true;
            }
        }

        foreach (array_keys($convert) as $name) {
            $val = $this->_params[$name];
            $quote = strpos($val, "'");

            if ($quote === false) {
                $this->_params[$name] = urldecode($val);
            } else {
                $orig_charset = substr($val, 0, $quote);
                if (Horde_String::lower($orig_charset) == 'iso-8859-1') {
                    $orig_charset = 'windows-1252';
                }

                /* Ignore language. */
                $quote = strpos($val, "'", $quote + 1);
                substr($val, $quote + 1);
                $this->_params[$name] = Horde_String::convertCharset(
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
            foreach ($this->_params as $key => $val) {
                $this->_params[$key] = Horde_Mime::decode($val);
            }
        }

        if (!count($this->_params) && is_string($data)) {
            $this->_values = array(trim($parts[0]));
        }
    }

    /* ArrayAccess methods */

    /**
     */
    public function offsetExists($offset)
    {
        return isset($this->_params[$offset]);
    }

    /**
     */
    public function offsetGet($offset)
    {
        return $this->_params[$offset];
    }

    /**
     */
    public function offsetSet($offset, $value)
    {
        $this->_params[$offset] = $value;
    }

    /**
     */
    public function offsetUnset($offset)
    {
        unset($this->_params[$offset]);
    }

    /* Serializable methods */

    /**
     */
    public function serialize()
    {
        $this->_params = $this->params;
        return serialize(get_object_vars($this));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        foreach (unserialize($data) as $key => $val) {
            if ($key != '_params') {
                $this->$key = $val;
            }
        }

        $this->_setValue($data['_params']);
    }

}
