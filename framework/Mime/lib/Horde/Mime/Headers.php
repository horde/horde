<?php
/**
 * The Horde_Mime_Headers:: class contains generic functions related to
 * handling the headers of mail messages.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Headers implements Serializable
{
    /* Serialized version. */
    const VERSION = 1;

    /* Constants for getValue(). */
    const VALUE_STRING = 1;
    const VALUE_BASE = 2;
    const VALUE_PARAMS = 3;

    /**
     * The default charset to use when parsing text parts with no charset
     * information.
     *
     * @var string
     */
    static public $defaultCharset = 'us-ascii';

    /**
     * The internal headers array.
     *
     * Keys are the lowercase header name.
     * Values are:
     * <pre>
     * 'h' - The case-sensitive header name.
     * 'p' - Parameters for this header.
     * 'v' - The value of the header.
     * </pre>
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * The sequence to use as EOL for the headers.
     * The default is currently to output the EOL sequence internally as
     * just "\n" instead of the canonical "\r\n" required in RFC 822 & 2045.
     * To be RFC complaint, the full <CR><LF> EOL combination should be used
     * when sending a message.
     *
     * @var string
     */
    protected $_eol = "\n";

    /**
     * The User-Agent string to use.
     *
     * @var string
     */
    protected $_agent = null;

    /**
     * Returns the internal header array in array format.
     *
     * @param array $options  Optional parameters:
     * <pre>
     * 'charset' => (string) Encodes the headers using this charset.
     *              DEFAULT: No encoding.
     * 'defserver' => (string) The default domain to append to mailboxes.
     *              DEFAULT: No default name.
     * 'nowrap' => (integer) Don't wrap the headers.
     *             DEFAULT: Headers are wrapped.
     * </pre>
     *
     * @return array  The headers in array format.
     */
    public function toArray($options = array())
    {
        $charset = empty($options['charset']) ? null : $options['charset'];
        $address_keys = $this->addressFields();
        $mime = $this->mimeParamFields();
        $ret = array();

        foreach ($this->_headers as $header => $ob) {
            $val = is_array($ob['v']) ? $ob['v'] : array($ob['v']);

            foreach (array_keys($val) as $key) {
                if (in_array($header, $address_keys) ) {
                    /* Address encoded headers. */
                    try {
                        $text = Horde_Mime::encodeAddress($val[$key], $charset, empty($options['defserver']) ? null : $options['defserver']);
                    } catch (Horde_Mime_Exception $e) {
                        $text = $val[$key];
                    }
                } elseif (in_array($header, $mime) && !empty($ob['p'])) {
                    /* MIME encoded headers (RFC 2231). */
                    $text = $val[$key];
                    foreach ($ob['p'] as $name => $param) {
                        foreach (Horde_Mime::encodeParam($name, $param, $charset, array('escape' => true)) as $name2 => $param2) {
                            $text .= '; ' . $name2 . '=' . $param2;
                        }
                    }
                } else {
                    $text = $charset
                        ? Horde_Mime::encode($val[$key], $charset)
                        : $val[$key];
                }

                if (empty($options['nowrap'])) {
                    /* Remove any existing linebreaks and wrap the line. */
                    $header_text = $ob['h'] . ': ';
                    $text = ltrim(substr(wordwrap($header_text . strtr(trim($text), array("\r" => '', "\n" => '')), 76, $this->_eol . ' '), strlen($header_text)));
                }

                $val[$key] = $text;
            }

            $ret[$ob['h']] = (count($val) == 1) ? reset($val) : $val;
        }

        return $ret;
    }

    /**
     * Returns the internal header array in string format.
     *
     * @param array $options  Optional parameters:
     * <pre>
     * 'charset' => (string) Encodes the headers using this charset.
     *              DEFAULT: No encoding.
     * 'defserver' => (string) The default domain to append to mailboxes.
     *              DEFAULT: No default name.
     * 'nowrap' => (integer) Don't wrap the headers.
     *             DEFAULT: Headers are wrapped.
     * </pre>
     *
     * @return string  The headers in string format.
     */
    public function toString($options = array())
    {
        $text = '';

        foreach ($this->toArray($options) as $key => $val) {
            if (!is_array($val)) {
                $val = array($val);
            }
            foreach ($val as $entry) {
                $text .= $key . ': ' . $entry . $this->_eol;
            }
        }

        return $text . $this->_eol;
    }

    /**
     * Generate the 'Received' header for the Web browser->Horde hop
     * (attempts to conform to guidelines in RFC 5321 [4.4]).
     *
     * @param array $options  Additional options:
     * <pre>
     * 'dns' - (Net_DNS_Resolver) Use the DNS resolver object to lookup
     *         hostnames.
     *         DEFAULT: Use gethostbyaddr() function.
     * 'server' - (string) Use this server name.
     *            DEFAULT: Auto-detect using current PHP values.
     * </pre>
     */
    public function addReceivedHeader($options = array())
    {
        $old_error = error_reporting(0);
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            /* This indicates the user is connecting through a proxy. */
            $remote_path = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $remote_addr = $remote_path[0];
            if (!empty($options['dns'])) {
                $remote = $remote_addr;
                if ($response = $options['dns']->query($remote_addr, 'PTR')) {
                    foreach ($response->answer as $val) {
                        if (isset($val->ptrdname)) {
                            $remote = $val->ptrdname;
                            break;
                        }
                    }
                }
            } else {
                $remote = gethostbyaddr($remote_addr);
            }
        } else {
            $remote_addr = $_SERVER['REMOTE_ADDR'];
            if (empty($_SERVER['REMOTE_HOST'])) {
                if (!empty($options['dns'])) {
                    $remote = $remote_addr;
                    if ($response = $options['dns']->query($remote_addr, 'PTR')) {
                        foreach ($response->answer as $val) {
                            if (isset($val->ptrdname)) {
                                $remote = $val->ptrdname;
                                break;
                            }
                        }
                    }
                } else {
                    $remote = gethostbyaddr($remote_addr);
                }
            } else {
                $remote = $_SERVER['REMOTE_HOST'];
            }
        }
        error_reporting($old_error);

        if (!empty($_SERVER['REMOTE_IDENT'])) {
            $remote_ident = $_SERVER['REMOTE_IDENT'] . '@' . $remote . ' ';
        } elseif ($remote != $_SERVER['REMOTE_ADDR']) {
            $remote_ident = $remote . ' ';
        } else {
            $remote_ident = '';
        }

        if (!empty($options['server'])) {
            $server_name = $options['server'];
        } elseif (!empty($_SERVER['SERVER_NAME'])) {
            $server_name = $_SERVER['SERVER_NAME'];
        } elseif (!empty($_SERVER['HTTP_HOST'])) {
            $server_name = $_SERVER['HTTP_HOST'];
        } else {
            $server_name = 'unknown';
        }

        $received = 'from ' . $remote . ' (' . $remote_ident .
            '[' . $remote_addr . ']) ' .
            'by ' . $server_name . ' (Horde Framework) with HTTP; ' .
            date('r');

        $this->addHeader('Received', $received);
    }

    /**
     * Generate the 'Message-ID' header.
     */
    public function addMessageIdHeader()
    {
        $this->addHeader('Message-ID', Horde_Mime::generateMessageId());
    }

    /**
     * Generate the user agent description header.
     */
    public function addUserAgentHeader()
    {
        $this->addHeader('User-Agent', $this->getUserAgent());
    }

    /**
     * Returns the user agent description header.
     *
     * @return string  The user agent header.
     */
    public function getUserAgent()
    {
        if (is_null($this->_agent)) {
            $this->_agent = 'Horde Application Framework 4';
        }
        return $this->_agent;
    }

    /**
     * Explicitly sets the User-Agent string.
     *
     * @param string $agent  The User-Agent string to use.
     */
    public function setUserAgent($agent)
    {
        $this->_agent = $agent;
    }

    /**
     * Add a header to the header array.
     *
     * @param string $header  The header name.
     * @param string $value   The header value.
     * @param array $options  Additional options:
     * <pre>
     * 'decode' - (boolean) MIME decode the value?
     * 'params' - (array) MIME parameters for Content-Type or
     *            Content-Disposition.
     * </pre>
     */
    public function addHeader($header, $value, $options = array())
    {
        $header = trim($header);
        $lcHeader = Horde_String::lower($header);

        if (!isset($this->_headers[$lcHeader])) {
            $this->_headers[$lcHeader] = array(
                'h' => $header
            );
        }
        $ptr = &$this->_headers[$lcHeader];

        if (!empty($options['decode'])) {
            // Fields defined in RFC 2822 that contain address information
            if (in_array($lcHeader, $this->addressFields())) {
                try {
                    $value = Horde_Mime::decodeAddrString($value);
                } catch (Horde_Mime_Exception $e) {
                    $value = '';
                }
            } else {
                $value = Horde_Mime::decode($value, null);
            }
        }

        if (isset($ptr['v'])) {
            if (!is_array($ptr['v'])) {
                $ptr['v'] = array($ptr['v']);
            }
            $ptr['v'][] = $value;
        } else {
            $ptr['v'] = $value;
        }

        if (!empty($options['params'])) {
            $ptr['p'] = $options['params'];
        }
    }

    /**
     * Remove a header from the header array.
     *
     * @param string $header  The header name.
     */
    public function removeHeader($header)
    {
        unset($this->_headers[Horde_String::lower(trim($header))]);
    }

    /**
     * Replace a value of a header.
     *
     * @param string $header  The header name.
     * @param string $value   The header value.
     * @param array $options  Additional options:
     * <pre>
     * 'decode' - (boolean) MIME decode the value?
     * 'params' - (array) MIME parameters for Content-Type or
     *            Content-Disposition
     * </pre>
     */
    public function replaceHeader($header, $value, $options = array())
    {
        $this->removeHeader($header);
        $this->addHeader($header, $value, $options);
    }

    /**
     * Set a value for a particular header ONLY if that header is set.
     *
     * @param string $header  The header name.
     * @param string $value   The header value.
     * @param array $options  Additional options:
     * <pre>
     * 'decode' - (boolean) MIME decode the value?
     * 'params' - (array) MIME parameters for Content-Type or
     *            Content-Disposition.
     * </pre>
     *
     * @return boolean  True if value was set.
     */
    public function setValue($header, $value, $options = array())
    {
        if (isset($this->_headers[Horde_String::lower($header)])) {
            $this->addHeader($header, $value, $options);
            return true;
        }

        return false;
    }

    /**
     * Attempts to return the header in the correct case.
     *
     * @param string $header  The header to search for.
     *
     * @return string  The value for the given header.
     *                 If the header is not found, returns null.
     */
    public function getString($header)
    {
        $lcHeader = Horde_String::lower($header);
        return (isset($this->_headers[$lcHeader]))
            ? $this->_headers[$lcHeader]['h']
            : null;
    }

    /**
     * Attempt to return the value for a given header.
     * The following header fields can only have 1 entry, so if duplicate
     * entries exist, the first value will be used:
     *   * To, From, Cc, Bcc, Date, Sender, Reply-to, Message-ID, In-Reply-To,
     *     References, Subject (RFC 2822 [3.6])
     *   * All List Headers (RFC 2369 [3])
     * The values are not MIME encoded.
     *
     * @param string $header  The header to search for.
     * @param integer $type   The type of return:
     * <pre>
     * VALUE_STRING - Returns a string representation of the entire header.
     * VALUE_BASE - Returns a string representation of the base value of the
     *              header. If this is not a header that allows parameters,
     *              this will be equivalent to VALUE_STRING.
     * VALUE_PARAMS - Returns the list of parameters for this header. If this
     *                is not a header that allows parameters, this will be
     *                an empty array.
     * </pre>
     *
     * @return mixed  The value for the given header.
     *                If the header is not found, returns null.
     */
    public function getValue($header, $type = self::VALUE_STRING)
    {
        $header = Horde_String::lower($header);

        if (!isset($this->_headers[$header])) {
            return null;
        }

        $ptr = &$this->_headers[$header];
        $base = (is_array($ptr['v']) && in_array($header, $this->singleFields(true)))
            ? $ptr['v'][0]
            : $ptr['v'];
        $params = isset($ptr['p']) ? $ptr['p'] : array();

        switch ($type) {
        case self::VALUE_BASE:
            return $base;

        case self::VALUE_PARAMS:
            return $params;

        case self::VALUE_STRING:
            foreach ($params as $key => $val) {
                $base .= '; ' . $key . '=' . $val;
            }
            return $base;
        }
    }

    /**
     * Returns the list of RFC defined header fields that contain address
     * info.
     *
     * @return array  The list of headers, in lowercase.
     */
    public function addressFields()
    {
        return array(
            'from', 'to', 'cc', 'bcc', 'reply-to', 'resent-to', 'resent-cc',
            'resent-bcc', 'resent-from', 'sender'
        );
    }

    /**
     * Returns the list of RFC defined header fields that can only contain
     * a single value.
     *
     * @param boolean $list  Return list-related headers also?
     *
     * @return array  The list of headers, in lowercase.
     */
    public function singleFields($list = true)
    {
        $single = array(
            'to', 'from', 'cc', 'bcc', 'date', 'sender', 'reply-to',
            'message-id', 'in-reply-to', 'references', 'subject', 'x-priority'
        );

        if ($list) {
            $single = array_merge($single, array_keys($this->listHeaders()));
        }

        return $single;
    }

    /**
     * Returns the list of RFC defined MIME header fields that may contain
     * parameter info.
     *
     * @return array  The list of headers, in lowercase.
     */
    static public function mimeParamFields()
    {
        return array('content-type', 'content-disposition');
    }

    /**
     * Returns the list of valid mailing list headers.
     *
     * @return array  The list of valid mailing list headers.
     */
    static public function listHeaders()
    {
        return array(
            /* RFC 2369 */
            'list-help'         =>  _("List-Help"),
            'list-unsubscribe'  =>  _("List-Unsubscribe"),
            'list-subscribe'    =>  _("List-Subscribe"),
            'list-owner'        =>  _("List-Owner"),
            'list-post'         =>  _("List-Post"),
            'list-archive'      =>  _("List-Archive"),
            /* RFC 2919 */
            'list-id'           =>  _("List-Id")
        );
    }

    /**
     * Do any mailing list headers exist?
     *
     * @return boolean  True if any mailing list headers exist.
     */
    public function listHeadersExist()
    {
        return (bool)count(array_intersect(array_keys($this->listHeaders()), array_keys($this->_headers)));
    }

    /**
     * Sets a new string to use for EOLs.
     *
     * @param string $eol  The string to use for EOLs.
     */
    public function setEOL($eol)
    {
        $this->_eol = $eol;
    }

    /**
     * Get the string to use for EOLs.
     *
     * @return string  The string to use for EOLs.
     */
    public function getEOL()
    {
        return $this->_eol;
    }

    /**
     * Returns a header from the header object.
     *
     * @param string $field  The header to return as an object.
     *
     * @return array  The object for the field requested.
     * @see Horde_Mime_Address::parseAddressList()
     */
    public function getOb($field)
    {
        $val = $this->getValue($field);
        if (!is_null($val)) {
            try {
                return Horde_Mime_Address::parseAddressList($val);
            } catch (Horde_Mime_Exception $e) {}
        }
        return array();
    }

    /**
     * Builds a Horde_Mime_Headers object from header text.
     * This function can be called statically:
     *   $headers = Horde_Mime_Headers::parseHeaders().
     *
     * @param string $text  A text string containing the headers.
     *
     * @return Horde_Mime_Headers  A new Horde_Mime_Headers object.
     */
    static public function parseHeaders($text)
    {
        $currheader = $currtext = null;
        $mime = self::mimeParamFields();
        $to_process = array();

        foreach (explode("\n", $text) as $val) {
            $val = rtrim($val);
            if (empty($val)) {
                break;
            }

            if (($val[0] == ' ') || ($val[0] == "\t")) {
                $currtext .= ' ' . ltrim($val);
            } else {
                if (!is_null($currheader)) {
                    $to_process[] = array($currheader, $currtext);
                }

                $pos = strpos($val, ':');
                $currheader = substr($val, 0, $pos);
                $currtext = ltrim(substr($val, $pos + 1));
            }
        }

        if (!is_null($currheader)) {
            $to_process[] = array($currheader, $currtext);
        }

        $headers = new Horde_Mime_Headers();
        $eightbit_check = (self::$defaultCharset != 'us-ascii');

        reset($to_process);
        while (list(,$val) = each($to_process)) {
            /* Ignore empty headers. */
            if (!strlen($val[1])) {
                continue;
            }
            if ($eightbit_check && Horde_Mime::is8bit($val[1])) {
                $val[1] = Horde_String::convertCharset($val[1], self::$defaultCharset);
            }

            if (in_array(Horde_String::lower($val[0]), $mime)) {
                $res = Horde_Mime::decodeParam($val[0], $val[1]);
                $headers->addHeader($val[0], $res['val'], array('decode' => true, 'params' => $res['params']));
            } else {
                $headers->addHeader($val[0], $val[1], array('decode' => true));
            }
        }

        return $headers;
    }

    /* Serializable methods. */

    /**
     * Serialization.
     *
     * @return string  Serialized data.
     */
    public function serialize()
    {
        return serialize(array(
            // Serialized data ID.
            self::VERSION,
            $this->_headers,
            $this->_eol,
            $this->_agent
        ));
    }

    /**
     * Unserialization.
     *
     * @param string $data  Serialized data.
     *
     * @throws Exception
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_headers = $data[1];
        $this->_eol = $data[2];
        $this->_agent = $data[3];
    }

}
