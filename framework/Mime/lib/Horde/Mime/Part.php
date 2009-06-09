<?php
/**
 * The Horde_Mime_Part:: class provides a wrapper around MIME parts and
 * methods for dealing with them.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
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

    /* The default MIME disposition. */
    const DEFAULT_DISPOSITION = 'inline';

    /* The default MIME encoding. */
    const DEFAULT_ENCODING = '7bit';

    /**
     * The default charset to use when parsing text parts with no charset
     * information.
     *
     * @var string
     */
    static public $defaultCharset = 'us-ascii';

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
     * Is this the base MIME part?
     *
     * @var boolean
     */
    protected $_basepart = false;

    /**
     * Set the content-disposition of this part.
     *
     * @param string $disposition  The content-disposition to set (inline or
     *                             attachment).
     */
    public function setDisposition($disposition)
    {
        $disposition = Horde_String::lower($disposition);

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

        list($this->_type, $this->_subtype) = explode('/', Horde_String::lower($mimetype));

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
        $encoding = Horde_String::lower($encoding);

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
     *
     * @param boolean  Success status.
     */
    public function removePart($id)
    {
        return $this->_partAction($id, 'remove');
    }

    /**
     * Alter a current MIME subpart.
     *
     * @param string $id                  The MIME ID to alter.
     * @param Horde_Mime_Part $mime_part  The MIME part to store.
     *
     * @param boolean  Success status.
     */
    public function alterPart($id, $mime_part)
    {
        return $this->_partAction($id, 'alter', $mime_part);
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
     * @return mixed  See calling functions.
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
                    return true;

                case 'get':
                    return $this->_parts[$val];

                case 'remove':
                    unset($this->_parts[$val]);
                    $this->_reindex = true;
                    return true;
                }
            }

            if ((strpos($id, $partid . '.') === 0) ||
                (strrchr($partid, '.') === '.0')) {
                return $this->_parts[$val]->_partAction($id, $action, $mime_part);
            }
        }

        return ($action == 'get') ? null : false;
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
        return $this->_contentTypeParams;
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

        /* Get the Content-Type itself. */
        $ptype = $this->getPrimaryType();
        $c_params = $this->getAllContentTypeParameters();
        if ($ptype != 'text') {
            unset($c_params['charset']);
        }
        $headers->replaceHeader('Content-Type', $this->getType(), array('params' => $c_params));

        /* Get the description, if any. */
        if (($descrip = $this->getDescription())) {
            $headers->replaceHeader('Content-Description', $descrip);
        }

        /* Per RFC 2046 [4], this MUST appear in the base message headers. */
        if ($this->_basepart) {
            $headers->replaceHeader('MIME-Version', '1.0');
        }

        /* message/* parts require no additional header information. */
        if ($ptype == 'message') {
            return $headers;
        }

        /* Don't show Content-Disposition for multipart messages unless
           there is a name parameter. */
        $name = $this->getName();
        if (($ptype != 'multipart') || !empty($name)) {
            $headers->replaceHeader('Content-Disposition', $this->getDisposition(), array('params' => (!empty($name) ? array('filename' => $name) : array())));
        }

        /* Add transfer encoding information. */
        $headers->replaceHeader('Content-Transfer-Encoding', $this->getTransferEncoding());

        /* Add content ID information. */
        if (!is_null($this->_contentid)) {
            $headers->replaceHeader('Content-ID', '<' . $this->_contentid . '>');
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
            $eol = $this->getEOL();
            if (Horde_Mime::is8bit($this->_contents)) {
                $encoding = ($this->_encode7bit) ? 'quoted-printable' : '8bit';
            } elseif (preg_match("/(?:" . $eol . "|^)[^" . $eol . "]{999,}(?:" . $eol . "|$)/", $this->_contents)) {
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
            preg_match('/\x00/', $this->_contents)) {
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
            if (($eollength = Horde_String::length($eol)) &&
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
                ? Horde_String::length($this->_contents, $this->getCharset())
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
     * @param string $id       The ID of this part.
     * @param boolean $rfc822  Is this a message/rfc822 part?
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
                if (empty($id) && ($this->getType() == 'message/rfc822')) {
                    $this->setMimeId('1');
                    $id = '1.';
                } else {
                    $this->setMimeId($id . '0');
                }
                $i = 1;
                foreach (array_keys($this->_parts) as $val) {
                    $this->_parts[$val]->buildMimeIds($id . $i++);
                }
            }
        } else {
            $this->setMimeId($id);
            $id .= '.';

            if ($this->getType() == 'message/rfc822') {
                if (count($this->_parts)) {
                    reset($this->_parts);
                    $this->_parts[key($this->_parts)]->buildMimeIds($id, true);
                }
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

    /**
     * Is this the base MIME part?
     *
     * @param boolean $base  True if this is the base MIME part.
     */
    public function isBasePart($base)
    {
        $this->_basepart = $base;
    }

    /**
     * Sends this message.
     *
     * @param string $email                 The address list to send to.
     * @param Horde_Mime_Headers $headers   The Horde_Mime_Headers object
     *                                      holding this message's headers.
     * @param string $driver                The Mail:: driver to use.
     * @param array $params                 Any parameters necessary for the
     *                                      Mail driver.
     *
     * @throws Horde_Mime_Exception
     */
    public function send($email, $headers, $driver, $params = array())
    {
        require_once 'Mail.php';
        $mailer = Mail::factory($driver, $params);

        $old_basepart = $this->_basepart;
        $this->_basepart = true;

        /* Add MIME Headers if they don't already exist. */
        if (!$headers->getValue('MIME-Version')) {
            $headers = $this->addMimeHeaders($headers);
        }
        $headerArray = $headers->toArray(array('charset' => $this->getCharset()));

        /* Does the SMTP backend support 8BITMIME (RFC 1652) or
         * BINARYMIME (RFC 3030) extensions? Requires PEAR's Mail package
         * version 1.2+ and Net_SMTP version 1.3+. */
        if (($driver == 'smtp') && method_exists($mailer, 'getSMTPObject')) {
            $net_smtp = $mailer->getSMTPObject();
            if (!is_a($net_smtp, 'PEAR_Error') &&
                method_exists($net_smtp, 'getServiceExtensions')) {
                $smtp_ext = $net_smtp->getServiceExtensions();
                $this->strict7bit(false);
                $encoding = $this->getTransferEncoding();
                if (($encoding == '8bit') &&
                    isset($smtp_ext['8BITMIME'])) {
                    $mailer->addServiceExtensionParameter('BODY', '8BITMIME');
                } elseif (($encoding == 'binary') &&
                          isset($smtp_ext['BINARYMIME'])) {
                    $mailer->addServiceExtensionParameter('BODY', 'BINARYMIME');
                } else {
                    $this->strict7bit(true);
                    $encoding = $this->getTransferEncoding();
                }
                $headers->replaceHeader('Content-Transfer-Encoding', $encoding);
            }
        }

        /* Make sure the message has a trailing newline. */
        $msg = $this->toString(false);
        if (substr($msg, -1) != "\n") {
            $msg .= "\n";
        }

        $result = $mailer->send(Horde_Mime::encodeAddress($email), $headerArray, $msg);

        $this->_basepart = $old_basepart;

        if (is_a($result, 'PEAR_Error') && ($driver == 'sendmail')) {
            $error = Horde_Mime_Mail::sendmailError($result->getCode());
            if (is_null($error)) {
                $error = $result;
                $userinfo = null;
            } else {
                $userinfo = $result->toString();
            }
            // TODO: userinfo
            throw new Horde_Mime_Exception($error);
        }

        return $result;
    }

    /**
     * Finds the main "body" text part (if any) in a message.
     * "Body" data is the first text part under this part.
     *
     * @param string $subtype  Specifically search for this subtype.
     *
     * @return mixed  The MIME ID of the main body part, or null if a body
                      part is not found.
     */
    public function findBody($subtype = null)
    {
        foreach ($this->contentTypeMap() as $mime_id => $mime_type) {
            if ((strpos($mime_type, 'text/') === 0) &&
                (intval($mime_id) == 1) &&
                (is_null($subtype) || (substr($mime_type, 5) == $subtype))) {
                return $mime_id;
            }
        }

        return null;
    }

    /**
     * Parse an array of MIME structure information into a Horde_Mime_Part
     * object.
     * This function can be called statically via:
     *    $mime_part = Horde_Mime_Part::parseStructure();
     *
     * @param array $structure  An array of structure information in the
     *                          following format:
     * <pre>
     * MANDATORY:
     *   'type' - (string) The MIME type
     *   'subtype' - (string) The MIME subtype
     *
     * The array MAY contain the following information:
     *   'contents' - (string) The contents of the part.
     *   'disposition' - (string) The disposition type of the part (e.g.
     *                   'attachment', 'inline').
     *   'dparameters' - (array) Attribute/value pairs from the part's
     *                   Content-Disposition header.
     *   'language' - (array) A list of body language values.
     *   'location' - (string) The body content URI.
     *
     * Depending on the MIME type of the part, the array will also contain
     * further information. If labeled as [OPTIONAL], the array MAY
     * contain this information, but only if 'noext' is false and the
     * server returned the requested information. Else, the value is not
     * set.
     *
     * multipart/* parts:
     * ==================
     * 'parts' - (array) An array of subparts (follows the same format as
     *           the base structure array).
     * 'parameters' - [OPTIONAL] (array) Attribute/value pairs from the
     *                part's Content-Type header.
     *
     * All other parts:
     * ================
     * 'parameters' - (array) Attribute/value pairs from the part's
     *                Content-Type header.
     * 'id' - (string) The part's Content-ID value.
     * 'description' - (string) The part's Content-Description value.
     * 'encoding' - (string) The part's Content-Transfer-Encoding value.
     * 'size' - (integer) - The part's size in bytes.
     * 'envelope' - [ONLY message/rfc822] (array) See 'envelope' response.
     * 'structure' - [ONLY message/rfc822] (array) See 'structure'
     *               response.
     * 'lines' - [ONLY message/rfc822 and text/*] (integer) The size of
     *           the body in text lines.
     * 'md5' - [OPTIONAL] (string) The part's MD5 value.
     * </pre>
     *
     * @return object  A Horde_Mime_Part object.
     */
    static public function parseStructure($structure)
    {
        $ob = self::_parseStructure($structure);
        $ob->buildMimeIds();
        return $ob;
    }

    /**
     * Parse a subpart of a MIME message into a Horde_Mime_Part object.
     *
     * @param array $data  Structure information in the format described
     *                     in parseStructure().
     *
     * @return Horde_Mime_Part  The generated object.
     */
    static protected function _parseStructure($data)
    {
        $ob = new Horde_Mime_Part();
        $ob->setType($data['type'] . '/' . $data['subtype']);

        if (isset($data['encoding'])) {
            $ob->setTransferEncoding($data['encoding']);
        }

        if (isset($data['contents'])) {
            $ob->setContents($data['contents'], $ob->getTransferEncoding());
            $ob->transferDecodeContents();
        }

        if (isset($data['disposition'])) {
            $ob->setDisposition($data['disposition']);
            if (!empty($data['dparameters'])) {
                $params = Horde_Mime::decodeParam('content-disposition', $data['dparameters']);
                foreach ($params['params'] as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }
        }

        if (isset($data['size'])) {
            $ob->setBytes($data['size']);
        }

        if (isset($data['id'])) {
            $ob->setContentId($data['id']);
        }

        if (!empty($data['parameters'])) {
            $params = Horde_Mime::decodeParam('content-type', $data['parameters']);
            foreach ($params['params'] as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }
        }

        /* Set the default character set. */
        if (($data['subtype'] == 'text') &&
            (self::$defaultCharset != 'us-ascii') &&
            (Horde_String::lower($ob->getCharset()) == 'us-ascii')) {
            $ob->setCharset(self::$defaultCharset);
        }

        if (isset($data['description'])) {
            $ob->setDescription(Horde_Mime::decode($data['description']));
        }

        /* Set the name. */
        if (!$ob->getName()) {
            $fname = $ob->getDispositionParameter('filename');
            if (strlen($fname)) {
                $ob->setName($fname);
            }
        }

        // @todo Handle language, location, md5, lines, envelope

        /* Add multipart parts. */
        if (!empty($data['parts'])) {
            reset($data['parts']);
            while (list(,$val) = each($data['parts'])) {
                $ob->addPart(self::_parseStructure($val));
            }
        } elseif (!empty($data['structure'])) {
            $ob->addPart(self::_parseStructure($data['structure']));
        }

        return $ob;
    }

    /**
     * Attempts to build a Horde_Mime_Part object from message text.
     * This function can be called statically via:
     *    $mime_part = Horde_Mime_Part::parseMessage();
     *
     * @param string $text    The text of the MIME message.
     * @param array $options  Additional options:
     * <pre>
     * 'structure' - (boolean) If true, returns a structure object instead of
     *               a Horde_Mime_Part object.
     * </pre>
     *
     * @return mixed  If 'structure' is true, a structure array. If 'structure'
     *                is false, a Horde_Mime_Part object.
     * @throws Horde_Mime_Exception
     */
    static public function parseMessage($text, $options = array())
    {
        /* Set up the options for the mimeDecode class. */
        $decode_args = array(
            'include_bodies' => true,
            'decode_bodies' => false,
            'decode_headers' => false
        );

        require_once 'Mail/mimeDecode.php';
        $mimeDecode = new Mail_mimeDecode($text, Horde_Mime_Part::EOL);
        if (!($ob = $mimeDecode->decode($decode_args))) {
            throw new Horde_Mime_Exception('Could not decode MIME message.');
        }

        $ob = self::_convertMimeDecodeData($ob);

        return empty($options['structure'])
            ? self::parseStructure($ob)
            : $ob;
    }

    /**
     * Convert the output from Mail_mimeDecode::decode() into a structure that
     * parseStructure() can handle.
     *
     * @param stdClass $ob  The output from Mail_mimeDecode::decode().
     *
     * @return array  An array of structure information.
     */
    static protected function _convertMimeDecodeData($ob)
    {
        /* Primary content-type. */
        if (isset($ob->ctype_primary)) {
            $part = array(
                'type' => strtolower($ob->ctype_primary),
                'subtype' => isset($ob->ctype_secondary) ? strtolower($ob->ctype_secondary) : 'x-unknown'
            );
        } else {
            $part = array(
                'type' => 'application',
                'subtype' => 'octet-stream'
            );
        }

        /* Content transfer encoding. */
        if (isset($ob->headers['content-transfer-encoding'])) {
            $part['encoding'] = strtolower($ob->headers['content-transfer-encoding']);
        }

        /* Content-type and Disposition parameters. */
        $param_types = array(
            'ctype_parameters' => 'parameters',
            'd_parameters' => 'dparameters'
        );

        foreach ($param_types as $param_key => $param_value) {
            if (isset($ob->$param_key)) {
                $part[$param_value] = array();
                foreach ($ob->$param_key as $key => $val) {
                    $part[$param_value][strtolower($key)] = $val;
                }
            }
        }

        /* Content-Description. */
        if (isset($ob->headers['content-description'])) {
            $part['description'] = $ob->headers['content-description'];
        }

        /* Content-Disposition. */
        if (isset($ob->headers['content-disposition'])) {
            $hdr = $ob->headers['content-disposition'];
            $pos = strpos($hdr, ';');
            if ($pos !== false) {
                $hdr = substr($hdr, 0, $pos);
            }
            $part['disposition'] = strtolower($hdr);
        }

        /* Content-ID. */
        if (isset($ob->headers['content-id'])) {
            $part['id'] = $ob->headers['content-id'];
        }

        /* Get file size (if 'body' text is set). */
        if (isset($ob->body)) {
            $part['contents'] = $ob->body;
            if (($part['type'] != 'message') &&
                ($part['subtype'] != 'rfc822')) {
                /* Mail_mimeDecode puts an extra linebreak at the end of body
                 * text. */
                $size = strlen(str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $ob->body)) - 2;
                $part['size'] = ($size < 0) ? 0 : $size;
            }
        }

        /* Process parts also. */
        if (isset($ob->parts)) {
            $part['parts'] = array();
            reset($ob->parts);
            while (list($key,) = each($ob->parts)) {
                $part['parts'][] = self::_convertMimeDecodeData($ob->parts[$key]);
            }
        }

        return $part;
    }

    /**
     * Attempts to obtain the raw text of a MIME part.
     * This function can be called statically via:
     *    $data = Horde_Mime_Part::getRawPartText();
     *
     * @param string $text  The full text of the MIME message.
     * @param string $type  Either 'header' or 'body'.
     * @param string $id    The MIME ID.
     *
     * @return string  The raw text.
     * @throws Horde_Mime_Exception
     */
    static public function getRawPartText($text, $type, $id)
    {
        return self::_getRawPartText($text, $type, $id, null);
    }

    /**
     * Obtain the raw text of a MIME part.
     *
     * @param string $text      The full text of the MIME message.
     * @param string $type      Either 'header' or 'body'.
     * @param string $id        The MIME ID.
     * @param string $boundary  The boundary string.
     *
     * @return string  The raw text.
     * @throws Horde_Mime_Exception
     */
    static protected function _getRawPartText($text, $type, $id,
                                              $boundary = null)
    {
        /* We need to carry around the trailing "\n" because this is needed
         * to correctly find the boundary string. */
        $hdr_pos = strpos($text, "\n\n");
        if ($hdr_pos === false) {
            $hdr_pos = strpos($text, "\r\n\r\n");
            $curr_pos = $hdr_pos + 3;
        } else {
            $curr_pos = $hdr_pos + 1;
        }

        if ($id == 0) {
            switch ($type) {
            case 'body':
                if (is_null($boundary)) {
                    return substr($text, $curr_pos + 1);
                }
                $end_boundary = strpos($text, "\n--" . $boundary, $curr_pos);
                if ($end_boundary === false) {
                    throw new Horde_Mime_Exception('Could not find MIME part.');
                }
                return substr($text, $curr_pos + 1, $end_boundary - $curr_pos);

            case 'header':
                return trim(substr($text, 0, $hdr_pos));
            }
        }

        $base_pos = strpos($id, '.');
        if ($base_pos !== false) {
            $base_pos = substr($id, 0, $base_pos);
            $id = substr($id, $base_pos + 1);
        } else {
            $base_pos = $id;
            $id = 0;
        }

        $hdr_ob = Horde_Mime_Headers::parseHeaders(trim(substr($text, 0, $hdr_pos)));
        $params = Horde_Mime::decodeParam('content-type', $hdr_ob->getValue('Content-Type'));
        if (!isset($params['params']['boundary'])) {
            throw new Horde_Mime_Exception('Could not find MIME part.');
        }

        $search = "\n--" . $params['params']['boundary'];
        $search_len = strlen($search);

        for ($i = 0; $i < $base_pos; ++$i) {
            $new_pos = strpos($text, $search, $curr_pos);
            if ($new_pos !== false) {
                $curr_pos = $new_pos + $search_len;
                if (isset($text[$curr_pos + 1])) {
                    switch ($text[$curr_pos + 1]) {
                    case "\r":
                        ++$curr_pos;
                        break;

                    case "\n":
                        // noop
                        break;

                    case '-':
                        throw new Horde_Mime_Exception('Could not find MIME part.');
                    }
                }
            }
        }

        return self::_getRawPartText(substr($text, $curr_pos), $type, $id, $params['params']['boundary']);
    }

}
