<?php

require_once 'Horde/String.php';
require_once dirname(__FILE__) . '/../Mime.php';

/**
 * The Horde_Mime_Part:: class provides a wrapper around MIME parts and
 * methods for dealing with them.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Horde_Mime_Part
{
    /* The character(s) used internally for EOLs. */
    const EOL = "\n";

    /* The character string designated by RFC 2045 to designate EOLs in MIME
     * messages. */
    const RFC_EOL = "\r\n";

    /* The default MIME character set. */
    const DEFAULT_CHARSET = 'us-ascii';

    /* The default MIME disposition. */
    const DEFAULT_DISPOSITION = 'inline';

    /* The default MIME encoding. */
    const DEFAULT_ENCODING = '7bit';

    /**
     * The type (ex.: text) of this part.
     * Per RFC 2045, the default is 'application'.
     *
     * @var string
     */
    protected $_type = 'application';

    /**
     * The subtype (ex.: plain) of this part.
     * Per RFC 2045, the default is 'octet-stream'.
     *
     * @var string
     */
    protected $_subtype = 'octet-stream';

    /**
     * The body of the part.
     *
     * @var string
     */
    protected $_contents = '';

    /**
     * The desired transfer encoding of this part.
     *
     * @var string
     */
    protected $_transferEncoding = self::DEFAULT_ENCODING;

    /**
     * Should the message be encoded via 7-bit?
     *
     * @var boolean
     */
    protected $_encode7bit = true;

    /**
     * The description of this part.
     *
     * @var string
     */
    protected $_description = '';

    /**
     * The disposition of this part (inline or attachment).
     *
     * @var string
     */
    protected $_disposition = self::DEFAULT_DISPOSITION;

    /**
     * The disposition parameters of this part.
     *
     * @var array
     */
    protected $_dispParams = array();

    /**
     * The content type parameters of this part.
     *
     * @var array
     */
    protected $_contentTypeParams = array();

    /**
     * The subparts of this part.
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * Information/Statistics on the subpart.
     *
     * @var array
     */
    protected $_information = array();

    /**
     * The MIME ID of this part.
     *
     * @var string
     */
    protected $_mimeid = null;

    /**
     * The sequence to use as EOL for this part.
     * The default is currently to output the EOL sequence internally as
     * just "\n" instead of the canonical "\r\n" required in RFC 822 & 2045.
     * To be RFC complaint, the full <CR><LF> EOL combination should be used
     * when sending a message.
     * It is not crucial here since the PHP/PEAR mailing functions will handle
     * the EOL details.
     *
     * @var string
     */
    protected $_eol = self::EOL;

    /**
     * Internal class flags.
     *
     * @var array
     */
    protected $_flags = array();

    /**
     * Unique Horde_Mime_Part boundary string.
     *
     * @var string
     */
    protected $_boundary = null;

    /**
     * Default value for this Part's size.
     *
     * @var integer
     */
    protected $_bytes = 0;

    /**
     * The content-ID for this part.
     *
     * @var string
     */
    protected $_contentid = null;

    /**
     * Do we need to reindex the current part.
     *
     * @var boolean
     */
    protected $_reindex = false;

    /**
     * TODO
     */
    protected $_headers;

    /**
     * Set the content-disposition of this part.
     *
     * @param string $disposition  The content-disposition to set (inline or
     *                             attachment).
     */
    public function setDisposition($disposition)
    {
        $disposition = String::lower($disposition);

        if (in_array($disposition, array('inline', 'attachment'))) {
            $this->_disposition = $disposition;
        }
    }

    /**
     * Get the content-disposition of this part.
     *
     * @return string  The part's content-disposition.
     */
    public function getDisposition()
    {
        return $this->_disposition;
    }

    /**
     * Add a disposition parameter to this part.
     *
     * @param string $label  The disposition parameter label.
     * @param string $data   The disposition parameter data.
     */
    public function setDispositionParameter($label, $data)
    {
        $this->_dispParams[$label] = $data;
    }

    /**
     * Get a disposition parameter from this part.
     *
     * @param string $label  The disposition parameter label.
     *
     * @return string  The data requested.
     *                 Returns null if $label is not set.
     */
    public function getDispositionParameter($label)
    {
        return (isset($this->_dispParams[$label]))
            ? $this->_dispParams[$label]
            : null;
    }

    /**
     * Get all parameters from the Content-Disposition header.
     *
     * @return array  An array of all the parameters
     *                Returns the empty array if no parameters set.
     */
    public function getAllDispositionParameters()
    {
        return $this->_dispParams;
    }

    /**
     * Set the name of this part.
     *
     * @param string $name  The name to set.
     */
    public function setName($name)
    {
        $this->setContentTypeParameter('name', $name);
    }

    /**
     * Get the name of this part.
     *
     * @param boolean $default  If the name parameter doesn't exist, should we
     *                          use the default name from the description
     *                          parameter?
     *
     * @return string  The name of the part.
     */
    public function getName($default = false)
    {
        $name = $this->getContentTypeParameter('name');

        if ($default && empty($name)) {
            $name = preg_replace('|\W|', '_', $this->getDescription(false));
        }

        return $name;
    }

    /**
     * Set the body contents of this part.
     *
     * @param string $contents  The part body.
     * @param string $encoding  The current encoding of the contents.
     */
    public function setContents($contents, $encoding = null)
    {
        $this->_contents = $contents;
        $this->_flags['contentsSet'] = true;
        $this->_flags['currentEncoding'] = is_null($encoding) ? $this->getCurrentEncoding() : $encoding;
    }

    /**
     * Add to the body contents of this part.
     *
     * @param string $contents  The contents to append to the current part
     *                          body.
     * @param string $encoding  The current encoding of the contents. If not
     *                          specified, will try to auto determine the
     *                          encoding.
     */
    public function appendContents($contents, $encoding = null)
    {
        if (empty($this->_flags['contentsSet'])) {
            $this->setContents($contents, $encoding);
        } else {
            if (!is_null($encoding) &&
                ($encoding != $this->getCurrentEncoding())) {
                $this->setTransferEncoding($encoding);
                $this->transferDecodeContents();
            }
            $this->setContents($this->_contents . $contents, $encoding);
        }
    }

    /**
     * Clears the body contents of this part.
     */
    public function clearContents()
    {
        $this->_contents = '';
        unset($this->_flags['contentsSet'], $this->_flags['currentEncoding']);
    }

    /**
     * Return the body of the part.
     *
     * @return string  The raw body of the part.
     */
    public function getContents()
    {
        return $this->_contents;
    }

    /**
     * Returns the contents in strict RFC 822 & 2045 output - namely, all
     * newlines end with the canonical <CR><LF> sequence.
     *
     * @return string  The raw body of the part, with <CR><LF> EOL..
     */
    public function getCanonicalContents()
    {
        return $this->replaceEOL($this->_contents, self::RFC_EOL);
    }

    /**
     * Transfer encode the contents (to the transfer encoding identified via
     * getTransferEncoding()) and set as the part's new contents.
     */
    public function transferEncodeContents()
    {
        $contents = $this->transferEncode();
        $encode = $this->_flags['currentEncoding'] = $this->_flags['lastTransferEncode'];
        $this->setContents($contents, $encode);
        $this->setTransferEncoding($encode);
    }

    /**
     * Transfer decode the contents and set them as the new contents.
     */
    public function transferDecodeContents()
    {
        $contents = $this->transferDecode();
        $encode = $this->_flags['currentEncoding'] = $this->_flags['lastTransferDecode'];
        $this->setTransferEncoding($encode);

        /* Don't set contents if they are empty, because this will do stuff
           like reset the internal bytes field, even though we shouldn't do
           that (the user has their reasons to set the bytes field to a
           non-zero value without putting the contents into this part). */
        if (strlen($contents)) {
            $this->setContents($contents, $encode);
        }
    }

    /**
     * Set the MIME type of this part.
     *
     * @param string $mimetype  The MIME type to set (ex.: text/plain).
     */
    public function setType($mimetype)
    {
        /* RFC 2045: Any entity with unrecognized encoding must be treated
           as if it has a Content-Type of "application/octet-stream"
           regardless of what the Content-Type field actually says. */
        if ($this->_transferEncoding == 'x-unknown') {
            return;
        }

        list($this->_type, $this->_subtype) = explode('/', String::lower($mimetype));

        /* Known types. */
        $known = array(
            'text', 'multipart', 'message', 'application', 'audio', 'image',
            'video', 'model'
        );

        if (in_array($this->_type, $known)) {
            /* Set the boundary string for 'multipart/*' parts. */
            if ($this->_type == 'multipart') {
                if (!$this->getContentTypeParameter('boundary')) {
                    $this->setContentTypeParameter('boundary', $this->_generateBoundary());
                }
            } else {
                $this->clearContentTypeParameter('boundary');
            }
        } else {
            $this->_type = 'x-unknown';
            $this->clearContentTypeParameter('boundary');
        }
    }

     /**
      * Get the full MIME Content-Type of this part.
      *
      * @param boolean $charset  Append character set information to the end
      *                          of the content type if this is a text/* part?
      *
      * @return string  The mimetype of this part
      *                 (ex.: text/plain; charset=us-ascii).
      */
     public function getType($charset = false)
     {
         if (!isset($this->_type) || !isset($this->_subtype)) {
             return false;
         }

         $ptype = $this->getPrimaryType();
         $type = $ptype . '/' . $this->getSubType();
         if ($charset && ($ptype == 'text')) {
             $type .= '; charset=' . $this->getCharset();
         }

         return $type;
     }

    /**
     * If the subtype of a MIME part is unrecognized by an application, the
     * default type should be used instead (See RFC 2046).  This method
     * returns the default subtype for a particular primary MIME type.
     *
     * @return string  The default MIME type of this part (ex.: text/plain).
     */
    public function getDefaultType()
    {
        switch ($this->getPrimaryType()) {
        case 'text':
            /* RFC 2046 (4.1.4): text parts default to text/plain. */
            return 'text/plain';

        case 'multipart':
            /* RFC 2046 (5.1.3): multipart parts default to multipart/mixed. */
            return 'multipart/mixed';

        default:
            /* RFC 2046 (4.2, 4.3, 4.4, 4.5.3, 5.2.4): all others default to
               application/octet-stream. */
            return 'application/octet-stream';
        }
    }

    /**
     * Get the primary type of this part.
     *
     * @return string  The primary MIME type of this part.
     */
    public function getPrimaryType()
    {
        return $this->_type;
    }

    /**
     * Get the subtype of this part.
     *
     * @return string  The MIME subtype of this part.
     */
    public function getSubType()
    {
        return $this->_subtype;
    }

    /**
     * Set the character set of this part.
     *
     * @param string $charset  The character set of this part.
     */
    public function setCharset($charset)
    {
        $this->setContentTypeParameter('charset', $charset);
    }

    /**
     * Get the character set to use for of this part.  Returns a charset for
     * all types (not just 'text/*') since we use this charset to determine
     * how to encode text in MIME headers.
     *
     * @return string  The character set of this part.  Returns null if there
     *                 is no character set.
     */
    public function getCharset()
    {
        $charset = $this->getContentTypeParameter('charset');
        return empty($charset) ? null : $charset;
    }

    /**
     * Set the description of this part.
     *
     * @param string $description  The description of this part.
     */
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    /**
     * Get the description of this part.
     *
     * @param boolean $default  If the name parameter doesn't exist, should we
     *                          use the default name from the description
     *                          parameter?
     *
     * @return string  The description of this part.
     */
    public function getDescription($default = false)
    {
        $desc = $this->_description;

        if ($default && empty($desc)) {
            $desc = $this->getName();
        }

        return $desc;
    }

    /**
     * Set the transfer encoding to use for this part.
     *
     * @param string $encoding  The transfer encoding to use.
     */
    public function setTransferEncoding($encoding)
    {
        $known = array('7bit', '8bit', 'binary', 'base64', 'quoted-printable');
        $encoding = String::lower($encoding);

        if (in_array($encoding, $known)) {
            $this->_transferEncoding = $encoding;
        } else {
            /* RFC 2045: Any entity with unrecognized encoding must be treated
               as if it has a Content-Type of "application/octet-stream"
               regardless of what the Content-Type field actually says. */
            $this->setType('application/octet-stream');
            $this->_transferEncoding = 'x-unknown';
        }
    }

    /**
     * Add a MIME subpart.
     *
     * @param Horde_Mime_Part $mime_part  Add a subpart to the current object.
     */
    public function addPart($mime_part)
    {
        $this->_parts[] = $mime_part;
        $this->_reindex = true;
    }

    /**
     * Get a list of all MIME subparts.
     *
     * @return array  An array of the Horde_Mime_Part subparts.
     */
    public function getParts()
    {
        return $this->_parts;
    }

    /**
     * Retrieve a specific MIME part.
     *
     * @param string $id  The MIME ID to get.
     *
     * @return Horde_Mime_Part  The part requested or null if the part doesn't
     *                          exist.
     */
    public function getPart($id)
    {
        return $this->_partAction($id, 'get');
    }

    /**
     * Remove a subpart.
     *
     * @param string $id  The MIME ID to delete.
     */
    public function removePart($id)
    {
        $this->_partAction($id, 'remove');
    }

    /**
     * Alter a current MIME subpart.
     *
     * @param string $id                  The MIME ID to alter.
     * @param Horde_Mime_Part $mime_part  The MIME part to store.
     */
    public function alterPart($id, $mime_part)
    {
        $this->_partAction($id, 'alter', $mime_part);
    }

    /**
     * Function used to find a specific MIME part by ID and perform an action
     * on it.
     *
     * @param string $id                  The MIME ID.
     * @param string $action              The action to perform ('get',
     *                                    'remove', or 'alter').
     * @param Horde_Mime_Part $mime_part  The object to use for 'alter'.
     *
     * @return mixed  For 'get', a pointer to the Horde_Mime_Part object, or
     *                null if the object is not found.
     */
    protected function _partAction($id, $action, $mime_part = null)
    {
        $this_id = $this->getMimeId();

        /* Need strcmp() because, e.g., '2.0' == '2'. */
        if (($action == 'get') && (strcmp($id, $this_id) === 0)) {
            return $this;
        }

        if ($this->_reindex) {
            $this->buildMimeIds(is_null($this_id) ? '1' : $this_id);
        }

        foreach (array_keys($this->_parts) as $val) {
            $partid = $this->_parts[$val]->getMimeId();
            if (strcmp($id, $partid) === 0) {
                switch ($action) {
                case 'alter':
                    $mime_part->setMimeId($this->_parts[$val]->getMimeId());
                    $this->_parts[$val] = $mime_part;
                    return;

                case 'get':
                    return $this->_parts[$val];

                case 'remove':
                    unset($this->_parts[$val]);
                    $this->_reindex = true;
                    return;
                }
            } elseif (strpos($id, $partid) === 0) {
                return $this->_parts[$val]->_partAction($id, $action, $mime_part);
            }
        }

        return null;
    }

    /**
     * Add a content type parameter to this part.
     *
     * @param string $label  The disposition parameter label.
     * @param string $data   The disposition parameter data.
     */
    public function setContentTypeParameter($label, $data)
    {
        $this->_contentTypeParams[$label] = $data;
    }

    /**
     * Clears a content type parameter from this part.
     *
     * @param string $label  The disposition parameter label.
     * @param string $data   The disposition parameter data.
     */
    public function clearContentTypeParameter($label)
    {
        unset($this->_contentTypeParams[$label]);
    }

    /**
     * Get a content type parameter from this part.
     *
     * @param string $label  The content type parameter label.
     *
     * @return string  The data requested.
     *                 Returns null if $label is not set.
     */
    public function getContentTypeParameter($label)
    {
        return isset($this->_contentTypeParams[$label])
            ? $this->_contentTypeParams[$label]
            : null;
    }

    /**
     * Get all parameters from the Content-Type header.
     *
     * @return array  An array of all the parameters
     *                Returns the empty array if no parameters set.
     */
    public function getAllContentTypeParameters()
    {
        return $this->_contentTypeParameters;
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
     * Returns a Horde_Mime_Header object containing all MIME headers needed
     * for the part.
     *
     * @param Horde_Mime_Headers $headers  The Horde_Mime_Headers object to
     *                                     add the MIME headers to. If not
     *                                     specified, adds the headers to a
     *                                     new object.
     *
     * @return Horde_Mime_Headers  A Horde_Mime_Headers object.
     */
    public function addMimeHeaders($headers = null)
    {
        if (is_null($headers)) {
            $headers = new Horde_Mime_Headers();
        }

        foreach ($this->getHeaderArray() as $key => $val) {
            $headers->addHeader($key, $val);
        }

        return $headers;
    }

    /**
     * Get the list of MIME headers for this part in an array.
     *
     * @return array  The full set of MIME headers.
     */
    public function getHeaderArray()
    {
        $ptype = $this->getPrimaryType();
        $stype = $this->getSubType();

        /* Get the character set for this part. */
        $charset = $this->getCharset();

        /* Get the Content-Type - this is ALWAYS required. */
        $ctype = $this->getType(true);

        /* Manually encode Content-Type and Disposition parameters in here,
         * rather than in Horde_Mime_Headers, since it is easier to do when
         * the paramters are broken down. Encoding in the headers object will
         * ignore these headers Since they will already be in 7bit. */
        foreach ($this->getAllContentTypeParameters() as $key => $value) {
            /* Skip the charset key since that would have already been
             * added to $ctype by getType(). */
            if ($key == 'charset') {
                continue;
            }

            $encode_2231 = Horde_Mime::encodeParamString($key, $value, $charset);
            /* Try to work around non RFC 2231-compliant MUAs by sending both
             * a RFC 2047-like parameter name and then the correct RFC 2231
             * parameter.  See:
             * http://lists.horde.org/archives/dev/Week-of-Mon-20040426/014240.html */
            if (!empty($GLOBALS['conf']['mailformat']['brokenrfc2231']) &&
                (strpos($encode_2231, '*=') !== false)) {
                $ctype .= '; ' . $key . '="' . Horde_Mime::encode($value, $charset) . '"';
            }
            $ctype .= '; ' . $encode_2231;
        }
        $headers['Content-Type'] = $ctype;

        /* Get the description, if any. */
        if (($descrip = $this->getDescription())) {
            $headers['Content-Description'] = $descrip;
        }

        /* RFC 2045 [4] - message/rfc822 and message/partial require the
           MIME-Version header only if they themselves claim to be MIME
           compliant. */
        if (($ptype == 'message') &&
            (($stype == 'rfc822') || ($stype == 'partial'))) {
            // TODO - Check for "MIME-Version" in message/rfc822 part.
            $headers['MIME-Version'] = '1.0';
        }

        /* message/* parts require no additional header information. */
        if ($ptype == 'message') {
            return $headers;
        }

        /* Don't show Content-Disposition for multipart messages unless
           there is a name parameter. */
        $name = $this->getName();
        if (($ptype != 'multipart') || !empty($name)) {
            $disp = $this->getDisposition();

            /* Add any disposition parameter information, if available. */
            if (!empty($name)) {
                $encode_2231 = Horde_Mime::encodeParamString('filename', $name, $charset);
                /* Same broken RFC 2231 workaround as above. */
                if (!empty($GLOBALS['conf']['mailformat']['brokenrfc2231']) &&
                    (strpos($encode_2231, '*=') !== false)) {
                    $disp .= '; filename="' . Horde_Mime::encode($name, $charset) . '"';
                }
                $disp .= '; ' . $encode_2231;
            }

            $headers['Content-Disposition'] = $disp;
        }

        /* Add transfer encoding information. */
        $headers['Content-Transfer-Encoding'] = $this->getTransferEncoding();

        /* Add content ID information. */
        if (!is_null($this->_contentid)) {
            $headers['Content-ID'] = $this->_contentid;
        }

        return $headers;
    }

    /**
     * Return the entire part in MIME format. Includes headers on request.
     *
     * @param boolean $headers  Include the MIME headers?
     *
     * @return string  The MIME string.
     */
    public function toString($headers = true)
    {
        $eol = $this->getEOL();
        $ptype = $this->getPrimaryType();
        $text = '';

        if ($headers) {
            $hdr_ob = $this->addMimeHeaders();
            $hdr_ob->setEOL($eol);
            $text = $hdr_ob->toString(array('charset' => $this->getCharset()));
        }

        /* Any information about a message/* is embedded in the message
           contents themself. Simply output the contents of the part
           directly and return. */
        if ($ptype == 'message') {
            return $text . $this->_contents;
        }

        $text .= $this->transferEncode();

        /* Deal with multipart messages. */
        if ($ptype == 'multipart') {
            $this->_generateBoundary();
            $boundary = trim($this->getContentTypeParameter('boundary'), '"');
            if (!strlen($this->_contents)) {
                $text .= 'This message is in MIME format.' . $eol;
            }
            reset($this->_parts);
            while (list(,$part) = each($this->_parts)) {
                $text .= $eol . '--' . $boundary . $eol;
                $oldEOL = $part->getEOL();
                $part->setEOL($eol);
                $text .= $part->toString(true);
                $part->setEOL($oldEOL);
            }
            $text .= $eol . '--' . $boundary . '--' . $eol;
        }

        return $text;
    }

    /**
     * Returns the encoded part in strict RFC 822 & 2045 output - namely, all
     * newlines end with the canonical <CR><LF> sequence.
     *
     * @param boolean $headers  Include the MIME headers?
     *
     * @return string  The entire MIME part.
     */
    public function toCanonicalString($headers = true)
    {
        $string = $this->toString($headers);
        return $this->replaceEOL($string, self::RFC_EOL);
    }

    /**
     * Should we make sure the message is encoded via 7-bit (e.g. to adhere
     * to mail delivery standards such as RFC 2821)?
     *
     * @param boolean $use7bit  Use 7-bit encoding?
     */
    public function strict7bit($use7bit)
    {
        $this->_encode7bit = $use7bit;
    }

    /**
     * Get the transfer encoding for the part based on the user requested
     * transfer encoding and the current contents of the part.
     *
     * @return string  The transfer-encoding of this part.
     */
    public function getTransferEncoding()
    {
        $encoding = $this->_transferEncoding;

        /* If there are no contents, return whatever the current value of
           $_transferEncoding is. */
        if (empty($this->_contents)) {
            return $encoding;
        }

        $ptype = $this->getPrimaryType();

        switch ($ptype) {
        case 'message':
            /* RFC 2046 [5.2.1] - message/rfc822 messages only allow 7bit,
               8bit, and binary encodings. If the current encoding is either
               base64 or q-p, switch it to 8bit instead.
               RFC 2046 [5.2.2, 5.2.3, 5.2.4] - All other message/* messages
               only allow 7bit encodings. */
            $encoding = ($this->getSubType() == 'rfc822') ? '8bit' : '7bit';
            break;

        case 'text':
            if (Horde_Mime::is8bit($this->_contents)) {
                $encoding = ($this->_encode7bit) ? 'quoted-printable' : '8bit';
            } elseif (preg_match("/(?:\n|^)[^\n]{999,}(?:\n|$)/", $this->_contents)) {
                /* If the text is longer than 998 characters between
                 * linebreaks, use quoted-printable encoding to ensure the
                 * text will not be chopped (i.e. by sendmail if being sent
                 * as mail text). */
                $encoding = 'quoted-printable';
            }
            break;

        default:
            if (Horde_Mime::is8bit($this->_contents)) {
                $encoding = ($this->_encode7bit) ? 'base64' : '8bit';
            }
            break;
        }

        /* Need to do one last check for binary data if encoding is 7bit or
         * 8bit.  If the message contains a NULL character at all, the message
         * MUST be in binary format. RFC 2046 [2.7, 2.8, 2.9]. Q-P and base64
         * can handle binary data fine so no need to switch those encodings. */
        if (in_array($encoding, array('8bit', '7bit')) &&
            preg_match('/\x00/', $this->_encoding)) {
            $encoding = ($this->_encode7bit) ? 'base64' : 'binary';
        }

        return $encoding;
    }

    /**
     * Retrieves the current encoding of the contents in the object.
     *
     * @return string  The current encoding.
     */
    public function getCurrentEncoding()
    {
        return empty($this->_flags['currentEncoding'])
            ? $this->_transferEncoding
            : $this->_flags['currentEncoding'];
    }

    /**
     * Encodes the contents with the part's transfer encoding.
     *
     * @return string  The encoded text.
     */
    public function transferEncode()
    {
        $encoding = $this->getTransferEncoding();
        $eol = $this->getEOL();

        /* Set the 'lastTransferEncode' flag so that transferEncodeContents()
           can save a call to getTransferEncoding(). */
        $this->_flags['lastTransferEncode'] = $encoding;

        /* If contents are empty, or contents are already encoded to the
           correct encoding, return now. */
        if (!strlen($this->_contents) ||
            ($encoding == $this->_flags['currentEncoding'])) {
            return $this->_contents;
        }

        switch ($encoding) {
        /* Base64 Encoding: See RFC 2045, section 6.8 */
        case 'base64':
            /* Keeping these two lines separate seems to use much less
               memory than combining them (as of PHP 4.3). */
            $encoded_contents = base64_encode($this->_contents);
            return chunk_split($encoded_contents, 76, $eol);

        /* Quoted-Printable Encoding: See RFC 2045, section 6.7 */
        case 'quoted-printable':
            $output = Horde_Mime::quotedPrintableEncode($this->_contents, $eol);
            if (($eollength = String::length($eol)) &&
                (substr($output, $eollength * -1) == $eol)) {
                return substr($output, 0, $eollength * -1);
            }
            return $output;

        default:
            return $this->replaceEOL($this->_contents);
        }
    }

    /**
     * Decodes the contents of the part to either a 7bit or 8bit encoding.
     *
     * @return string  The decoded text.
     *                 Returns the empty string if there is no text to decode.
     */
    public function transferDecode()
    {
        $encoding = $this->getCurrentEncoding();

        /* If the contents are empty, return now. */
        if (!strlen($this->_contents)) {
            $this->_flags['lastTransferDecode'] = $encoding;
            return $this->_contents;
        }

        switch ($encoding) {
        case 'base64':
            $this->_flags['lastTransferDecode'] = '8bit';
            return base64_decode($this->_contents);

        case 'quoted-printable':
            $message = preg_replace("/=\r?\n/", '', $this->_contents);
            $message = quoted_printable_decode($this->replaceEOL($message));
            $this->_flags['lastTransferDecode'] = (Horde_Mime::is8bit($message)) ? '8bit' : '7bit';
            return $message;

        /* Support for uuencoded encoding - although not required by RFCs,
           some mailers may still encode this way. */
        case 'uuencode':
        case 'x-uuencode':
        case 'x-uue':
            $this->_flags['lastTransferDecode'] = '8bit';
            return convert_uuencode($this->_contents);

        default:
            if (isset($this->_flags['lastTransferDecode']) &&
                ($this->_flags['lastTransferDecode'] != $encoding)) {
                $message = $this->replaceEOL($this->_contents);
            } else {
                $message = $this->_contents;
            }
            $this->_flags['lastTransferDecode'] = $encoding;
            return $message;
        }
    }

    /**
     * Split the contents of the current Part into its respective subparts,
     * if it is multipart MIME encoding.
     *
     * The boundary Content-Type parameter must be set for this function to
     * work correctly.
     *
     * @return boolean  True if the contents were successfully split.
     *                  False if any error occurred.
     */
    public function splitContents()
    {
        if ((!($boundary = $this->getContentTypeParameter('boundary'))) ||
            !strlen($this->_contents)) {
            return false;
        }

        $eol = $this->getEOL();
        $retvalue = false;

        $boundary = '--' . $boundary;
        if (substr($this->_contents, 0, strlen($boundary)) == $boundary) {
            $pos1 = 0;
        } else {
            $pos1 = strpos($this->_contents, $eol . $boundary);
            if ($pos1 === false) {
                return false;
            }
        }

        $pos1 = strpos($this->_contents, $eol, $pos1 + 1);
        if ($pos1 === false) {
            return false;
        }
        $pos1 += strlen($eol);

        reset($this->_parts);
        $part_ptr = key($this->_parts);

        while ($pos2 = strpos($this->_contents, $eol . $boundary, $pos1)) {
            $this->_parts[$part_ptr]->setContents(substr($this->_contents, $pos1, $pos2 - $pos1));
            $this->_parts[$part_ptr]->splitContents();
            next($this->_parts);
            $part_ptr = key($this->_parts);
            if (is_null($part_ptr)) {
                return false;
            }
            $pos1 = strpos($this->_contents, $eol, $pos2 + 1);
            if ($pos1 === false) {
                return true;
            }
            $pos1 += strlen($eol);
        }

        return true;
    }

    /**
     * Replace newlines in this part's contents with those specified by either
     * the given newline sequence or the part's current EOL setting.
     *
     * @param string $text  The text to replace.
     * @param string $eol   The EOL sequence to use. If not present, uses the
     *                      part's current EOL setting.
     *
     * @return string  The text with the newlines replaced by the desired
     *                 newline sequence.
     */
    public function replaceEOL($text, $eol = null)
    {
        if (is_null($eol)) {
            $eol = $this->getEOL();
        }
        return preg_replace("/\r?\n/", $eol, $text);
    }

    /**
     * Determine the size of this MIME part and its child members.
     *
     * @return integer  Size of the part, in bytes.
     */
    public function getBytes()
    {
        $bytes = 0;

        if (empty($this->_flags['contentsSet']) && $this->_bytes) {
            $bytes = $this->_bytes;
        } elseif ($this->getPrimaryType() == 'multipart') {
            reset($this->_parts);
            while (list(,$part) = each($this->_parts)) {
                /* Skip multipart entries (since this may result in double
                   counting). */
                if ($part->getPrimaryType() != 'multipart') {
                    $bytes += $part->getBytes();
                }
            }
        } else {
            $bytes = ($this->getPrimaryType() == 'text')
                ? String::length($this->_contents, $this->getCharset())
                : strlen($this->_contents);
        }

        return $bytes;
    }

    /**
     * Explicitly set the size (in bytes) of this part. This value will only
     * be returned (via getBytes()) if there are no contents currently set.
     * This function is useful for setting the size of the part when the
     * contents of the part are not fully loaded (i.e. creating a
     * Horde_Mime_Part object from IMAP header information without loading the
     * data of the part).
     *
     * @param integer $bytes  The size of this part in bytes.
     */
    public function setBytes($bytes)
    {
        $this->_bytes = $bytes;
    }

    /**
     * Output the size of this MIME part in KB.
     *
     * @return string  Size of the part, in string format.
     */
    public function getSize()
    {
        $bytes = $this->getBytes();
        if (empty($bytes)) {
            return $bytes;
        }

        $localeinfo = NLS::getLocaleInfo();
        return number_format($bytes / 1024, 2, $localeinfo['decimal_point'], $localeinfo['thousands_sep']);
     }

    /**
     * Sets the Content-ID header for this part.
     *
     * @param string $cid  Use this CID (if not already set).  Else, generate
     *                     a random CID.
     */
    public function setContentId($cid = null)
    {
        if (is_null($this->_contentid)) {
            $this->_contentid = (is_null($cid)) ? (Horde_Mime::generateRandomId() . '@' . $_SERVER['SERVER_NAME']) : $cid;
        }
        return $this->_contentid;
    }

    /**
     * Returns the Content-ID for this part.
     *
     * @return string  The Content-ID for this part.
     */
    public function getContentId()
    {
        return $this->_contentid;
    }

    /**
     * Alter the MIME ID of this part.
     *
     * @param string $mimeid  The MIME ID.
     */
    public function setMimeId($mimeid)
    {
        $this->_mimeid = $mimeid;
    }

    /**
     * Returns the MIME ID of this part.
     *
     * @return string  The MIME ID.
     */
    public function getMimeId()
    {
        return $this->_mimeid;
    }

    /**
     * Build the MIME IDs for this part and all subparts.
     *
     * @param string $id  The ID of this part.
     */
    public function buildMimeIds($id = null, $rfc822 = false)
    {
        if (is_null($id)) {
            $rfc822 = true;
            $id = '';
        }

        if ($rfc822) {
            if (empty($this->_parts)) {
                $this->setMimeId($id . '1');
            } else {
                $this->setMimeId($id . '0');
                $i = 1;
                foreach (array_keys($this->_parts) as $val) {
                    $this->_parts[$val]->buildMimeIds($id . $i++);
                }
            }
        } else {
            $this->setMimeId($id);
            $id .= '.';

            if ($this->getType() == 'message/rfc822') {
                reset($this->_parts);
                $this->_parts[key($this->_parts)]->buildMimeIds($id, true);
            } elseif (!empty($this->_parts)) {
                $i = 1;
                foreach (array_keys($this->_parts) as $val) {
                    $this->_parts[$val]->buildMimeIds($id . $i++);
                }
            }
        }

        $this->_reindex = false;
    }

    /**
     * Generate the unique boundary string (if not already done).
     *
     * @return string  The boundary string.
     */
    protected function _generateBoundary()
    {
        if (is_null($this->_boundary)) {
            $this->_boundary = '=_' . Horde_Mime::generateRandomId();
        }
        return $this->_boundary;
    }

    /**
     * Returns a mapping of all MIME IDs to their content-types.
     *
     * @param boolean $sort  Sort by MIME ID?
     *
     * @return array  Keys: MIME ID; values: content type.
     */
    public function contentTypeMap($sort = true)
    {
        $map = array($this->getMimeId() => $this->getType());
        foreach ($this->_parts as $val) {
            $map += $val->contentTypeMap(false);
        }

        if ($sort) {
            uksort($map, 'strnatcasecmp');
        }

        return $map;
    }

}
