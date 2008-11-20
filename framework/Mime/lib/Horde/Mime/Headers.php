<?php
/**
 * The Horde_Mime_Headers:: class contains generic functions related to
 * handling the headers of mail messages.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Horde_Mime_Headers
{
    /**
     * The internal headers array.
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
     * 'defserver' => (string) TODO
     *              DEFAULT: NO
     * 'nowrap' => (integer) Don't wrap the headers.
     *             DEFAULT: Headers are wrapped.
     * </pre>
     *
     * @return array  The headers in array format.
     */
    public function toArray($options = array())
    {
        $ret = array();
        $address_keys = empty($options['charset'])
            ? array()
            : $this->addressFields();

        foreach ($this->_headers as $header => $ob) {
            $val = is_array($ob['value']) ? $ob['value'] : array($ob['value']);

            foreach (array_keys($val) as $key) {
                if (!empty($address_keys)) {
                    if (in_array($header, $address_keys)) {
                        $text = Horde_Mime::encodeAddress($val[$key], $charset, empty($options['defserver']) ? null : $options['defserver']);
                                                                                                        if (is_a($text, 'PEAR_Error')) {
                            $text = $val[$key];
                        }
                    } else {
                        $text = Horde_Mime::encode($val[$key], $options['charset']);
                    }
                } else {
                    $text = $val[$key];
                }

                if (empty($options['nowrap'])) {
                    $text = $this->wrapHeaders($header, $text);
                }

                $val[$key] = $text;
            }

            $ret[$ob['header']] = (count($val) == 1) ? reset($val) : $val;
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
     * 'defserver' => (string) TODO
     *              DEFAULT: NO
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
     */
    public function addReceivedHeader()
    {
        $old_error = error_reporting(0);
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            /* This indicates the user is connecting through a proxy. */
            $remote_path = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $remote_addr = $remote_path[0];
            $remote = gethostbyaddr($remote_addr);
        } else {
            $remote_addr = $_SERVER['REMOTE_ADDR'];
            $remote = empty($_SERVER['REMOTE_HOST'])
                ? gethostbyaddr($remote_addr)
                : $_SERVER['REMOTE_HOST'];
        }
        error_reporting($old_error);

        if (!empty($_SERVER['REMOTE_IDENT'])) {
            $remote_ident = $_SERVER['REMOTE_IDENT'] . '@' . $remote . ' ';
        } elseif ($remote != $_SERVER['REMOTE_ADDR']) {
            $remote_ident = $remote . ' ';
        } else {
            $remote_ident = '';
        }

        if (!empty($GLOBALS['conf']['server']['name'])) {
            $server_name = $GLOBALS['conf']['server']['name'];
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
     * Generate the 'Resent' headers (conforms to guidelines in
     * RFC 2822 [3.6.6]).
     *
     * @param string $from  The address to use for 'Resent-From'.
     * @param string $to    The address to use for 'Resent-To'.
     */
    public function addResentHeaders($from, $to)
    {
        /* We don't set Resent-Sender, Resent-Cc, or Resent-Bcc. */
        $this->addHeader('Resent-Date', date('r'));
        $this->addHeader('Resent-From', $from);
        $this->addHeader('Resent-To', $to);
        $this->addHeader('Resent-Message-ID', Horde_Mime::generateMessageId());
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
            $this->_agent = 'Horde Application Framework 4.0';
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
     * @param string $header   The header name.
     * @param string $value    The header value.
     * @param boolean $decode  MIME decode the value?
     */
    public function addHeader($header, $value, $decode = false)
    {
        require_once 'Horde/String.php';

        $header = trim($header);
        $lcHeader = String::lower($header);

        if (!isset($this->_headers[$lcHeader])) {
            $this->_headers[$lcHeader] = array();
            $this->_headers[$lcHeader]['header'] = $header;
        }
        $ptr = &$this->_headers[$lcHeader];

        if ($decode) {
            // Fields defined in RFC 2822 that contain address information
            if (in_array($lcHeader, $this->addressFields())) {
                $value = Horde_Mime::decodeAddrString($value);
            } else {
                $value = Horde_Mime::decode($value);
            }
        }

        if (isset($ptr['value'])) {
            if (!is_array($ptr['value'])) {
                $ptr['value'] = array($ptr['value']);
            }
            $ptr['value'][] = $value;
        } else {
            $ptr['value'] = $value;
        }
    }

    /**
     * Remove a header from the header array.
     *
     * @param string $header  The header name.
     */
    public function removeHeader($header)
    {
        require_once 'Horde/String.php';
        unset($this->_headers[String::lower(trim($header))]);
    }

    /**
     * Set a value for a particular header ONLY if that header is set.
     *
     * @param string $header   The header name.
     * @param string $value    The header value.
     * @param boolean $decode  MIME decode the value?
     *
     * @return boolean  True if value was set.
     */
    public function setValue($header, $value, $decode = false)
    {
        require_once 'Horde/String.php';

        if (isset($this->_headers[String::lower($header)])) {
            $this->addHeader($header, $value, $decode);
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
        require_once 'Horde/String.php';

        $lcHeader = String::lower($header);
        return (isset($this->_headers[$lcHeader]))
            ? $this->_headers[$lcHeader]['header']
            : null;
    }

    /**
     * Attempt to return the value for a given header.
     * The following header fields can only have 1 entry, so if duplicate
     * entries exist, the first value will be used:
     *   * To, From, Cc, Bcc, Date, Sender, Reply-to, Message-ID, In-Reply-To,
     *     References, Subject (RFC 2822 [3.6])
     *   * All List Headers (RFC 2369 [3])
     *
     * @param string $header  The header to search for.
     *
     * @return mixed  The value for the given header.
     *                If the header is not found, returns null.
     */
    public function getValue($header)
    {
        require_once 'Horde/String.php';

        $header = String::lower($header);

        if (isset($this->_headers[$header])) {
            return (is_array($this->_headers[$header]['value']) && in_array($header, $this->singleFields(true)))
                ? $this->_headers[$header]['value'][0]
                : $this->_headers[$header]['value'];
        }

        return null;
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
     * Returns the list of valid mailing list headers.
     *
     * @return array  The list of valid mailing list headers.
     */
    public function listHeaders()
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
        return (bool) count(array_intersect(array_keys($this->listHeaders()), array_keys($this->_headers)));
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
     */
    public function getOb($field)
    {
        $val = $this->getValue($field);
        return is_null($val)
            ? array()
            : Horde_Mime_Address::parseAddressList($val);
    }

    /**
     * Adds proper linebreaks to a header string.
     * RFC 2822 says headers SHOULD only be 78 characters a line, but also
     * says that a header line MUST not be more than 998 characters.
     *
     * @param string $header  The header name.
     * @param string $text    The text of the header field.
     *
     * @return string  The header value, with linebreaks inserted.
     */
    public function wrapHeaders($header, $text)
    {
        $eol = $this->_eol;
        $header_text = rtrim($header) . ': ';

        /* Remove any existing linebreaks. */
        $text = $header_text . preg_replace("/\r?\n\s?/", ' ', rtrim($text));

        if (!in_array(strtolower($header), array('content-type', 'content-disposition'))) {
            /* Wrap the line. */
            $line = wordwrap($text, 75, $eol . ' ');

            /* Make sure there are no empty lines. */
            $line = preg_replace('/' . $eol . ' ' . $eol . ' /', '/' . $eol . ' /', $line);

            return substr($line, strlen($header_text));
        }

        /* Split the line by the RFC parameter separator ';'. */
        $params = preg_split("/\s*;\s*/", $text);

        $line = '';
        $eollength = strlen($eol);
        $length = 1000 - $eollength;
        $paramcount = count($params) - 1;

        reset($params);
        while (list($count, $val) = each($params)) {
            /* If longer than RFC allows, then simply chop off the excess. */
            $moreparams = ($count != $paramcount);
            $maxlength = $length - (!empty($line) ? 1 : 0) - (($moreparams) ? 1 : 0);
            if (strlen($val) > $maxlength) {
                $val = substr($val, 0, $maxlength);

                /* If we have an opening quote, add a closing quote after
                 * chopping the rest of the text. */
                if (strpos($val, '"') !== false) {
                    $val = substr($val, 0, -1) . '"';
                }
            }

            if (!empty($line)) {
                $line .= ' ';
            }
            $line .= $val . (($moreparams) ? ';' : '') . $eol;
        }

        return substr($line, strlen($header_text), ($eollength * -1));
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
        $headers = new Horde_Mime_Headers();
        $currheader = $currtext = null;

        foreach (explode("\n", $text) as $val) {
            $val = rtrim($val);
            if (empty($val)) {
                break;
            }

            if (($val[0] == ' ') || ($val[0] == "\t")) {
                $currtext .= ' ' . ltrim($val);
            } else {
                if (!is_null($currheader)) {
                    $headers->addHeader($currheader, $currtext, true);
                }
                $pos = strpos($val, ':');
                $currheader = substr($val, 0, $pos);
                $currtext = ltrim(substr($val, $pos + 1));
            }
        }
        $headers->addHeader($currheader, $currtext, true);

        return $headers;
    }
}
