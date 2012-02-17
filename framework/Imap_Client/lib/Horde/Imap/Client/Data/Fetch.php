<?php
/**
 * Object containing data returned by the Horde_Imap_Client_Base#fetch()
 * command.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 */
class Horde_Imap_Client_Data_Fetch
{
    /* Constants. */
    const HEADER_PARSE = 1;
    const HEADER_STREAM = 2;

    /**
     * Internal data array.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Set the full message property.
     *
     * @param mixed $msg  The full message text, as either a string or stream
     *                    resource.
     */
    public function setFullMsg($msg)
    {
        $this->_data[Horde_Imap_Client::FETCH_FULLMSG] = $msg;
    }

    /**
     * Returns the full message.
     *
     * @param boolean $stream  Return as a stream?
     *
     * @return mixed  The full text of the entire message.
     */
    public function getFullMsg($stream = false)
    {
        return $this->_msgText($stream, isset($this->_data[Horde_Imap_Client::FETCH_FULLMSG]) ? $this->_data[Horde_Imap_Client::FETCH_FULLMSG] : null);
    }

    /**
     * Set the message structure.
     *
     * @param Horde_Mime_Part $structure  The base MIME part of the message.
     */
    public function setStructure(Horde_Mime_Part $structure)
    {
        $this->_data[Horde_Imap_Client::FETCH_STRUCTURE] = $structure;
    }

    /**
     * Get the message structure.
     *
     * @return Horde_Mime_Part $structure  The base MIME part of the message.
     */
    public function getStructure()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_STRUCTURE])
            ? clone $this->_data[Horde_Imap_Client::FETCH_STRUCTURE]
            : new Horde_Mime_Part();
    }

    /**
     * Set a header entry.
     *
     * @param string $label  The search label.
     * @param mixed $data    Either a Horde_Mime_Headers object or the raw
     *                       header text.
     */
    public function setHeaders($label, $data)
    {
        $this->_data[Horde_Imap_Client::FETCH_HEADERS][$label] = $data;
    }

    /**
     * Get a header entry.
     *
     * @param string $label    The search label.
     * @param integer $format  The return format. If self::HEADER_PARSE,
     *                         returns a Horde_Mime_Headers object. If
     *                         self::HEADER_STREAM, returns a stream.
     *                         Otherwise, returns header text.
     *
     * @return mixed  See $format.
     */
    public function getHeaders($label, $format = 0)
    {
        return $this->_getHeaders($label, $format, Horde_Imap_Client::FETCH_HEADERS);
    }

    /**
     * Set a header text entry.
     *
     * @param string $id    The MIME ID.
     * @param string $text  The header text.
     */
    public function setHeaderText($id, $text)
    {
        $this->_data[Horde_Imap_Client::FETCH_HEADERTEXT][$id] = $text;
    }

    /**
     * Get a header text entry.
     *
     * @param string $id       The MIME ID.
     * @param integer $format  The return format. If self::HEADER_PARSE,
     *                         returns a Horde_Mime_Headers object. If
     *                         self::HEADER_STREAM, returns a stream.
     *                         Otherwise, returns header text.
     *
     * @return mixed  See $format.
     */
    public function getHeaderText($id = 0, $format = 0)
    {
        return $this->_getHeaders($id, $format, Horde_Imap_Client::FETCH_HEADERTEXT);
    }

    /**
     * Set a MIME header entry.
     *
     * @param string $id    The MIME ID.
     * @param string $text  The header text.
     */
    public function setMimeHeader($id, $text)
    {
        $this->_data[Horde_Imap_Client::FETCH_MIMEHEADER][$id] = $text;
    }

    /**
     * Get a MIME header entry.
     *
     * @param string $id       The MIME ID.
     * @param integer $format  The return format. If self::HEADER_PARSE,
     *                         returns a Horde_Mime_Headers object. If
     *                         self::HEADER_STREAM, returns a stream.
     *                         Otherwise, returns header text.
     *
     * @return mixed  See $format.
     */
    public function getMimeHeader($id, $format = 0)
    {
        return $this->_getHeaders($id, $format, Horde_Imap_Client::FETCH_MIMEHEADER);
    }

    /**
     * Set a body part entry.
     *
     * @param string $id      The MIME ID.
     * @param mixed $text     The body part text, as either a string or stream
     *                        resource.
     * @param string $decode  Either '8bit', 'binary', or null.
     */
    public function setBodyPart($id, $text, $decode = null)
    {
        $this->_data[Horde_Imap_Client::FETCH_BODYPART][$id] = array(
            'd' => $decode,
            't' => $text
        );
    }

    /**
     * Set the body part size for a body part.
     *
     * @param string $id     The MIME ID.
     * @param integer $size  The size (in bytes).
     */
    public function setBodyPartSize($id, $size)
    {
        $this->_data[Horde_Imap_Client::FETCH_BODYPARTSIZE][$id] = intval($size);
    }

    /**
     * Get a body part entry.
     *
     * @param string $id       The MIME ID.
     * @param boolean $stream  Return as a stream?
     *
     * @return mixed  The full text of the body part.
     */
    public function getBodyPart($id, $stream = false)
    {
        return $this->_msgText($stream, isset($this->_data[Horde_Imap_Client::FETCH_BODYPART][$id]) ? $this->_data[Horde_Imap_Client::FETCH_BODYPART][$id]['t'] : null);
    }

    /**
     * Determines if/how a body part was MIME decoded on the server.
     *
     * @param string $id  The MIME ID.
     *
     * @return string  Either '8bit', 'binary', or null.
     */
    public function getBodyPartDecode($id)
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_BODYPART][$id])
            ? $this->_data[Horde_Imap_Client::FETCH_BODYPART][$id]['d']
            : null;
    }

    /**
     * Returns the body part size, if returned by the server.
     *
     * @param string $id  The MIME ID.
     *
     * @return integer  The body part size, in bytes.
     */
    public function getBodyPartSize($id)
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_BODYPARTSIZE][$id])
            ? $this->_data[Horde_Imap_Client::FETCH_BODYPARTSIZE][$id]
            : null;
    }

    /**
     * Set a body text entry.
     *
     * @param string $id   The MIME ID.
     * @param mixed $text  The body part text, as either a string or stream
     *                     resource.
     */
    public function setBodyText($id, $text)
    {
        $this->_data[Horde_Imap_Client::FETCH_BODYTEXT][$id] = $text;
    }

    /**
     * Get a body text entry.
     *
     * @param string $id       The MIME ID.
     * @param boolean $stream  Return as a stream?
     *
     * @return mixed  The full text of the body text.
     */
    public function getBodyText($id = 0, $stream = false)
    {
        return $this->_msgText($stream, isset($this->_data[Horde_Imap_Client::FETCH_BODYTEXT][$id]) ? $this->_data[Horde_Imap_Client::FETCH_BODYTEXT][$id] : null);
    }

    /**
     * Set envelope data.
     *
     * @param array $data  The envelope data to pass to the Envelope object
     *                     constructor, or an Envelope object.
     */
    public function setEnvelope($data)
    {
        $this->_data[Horde_Imap_Client::FETCH_ENVELOPE] = is_array($data)
            ? new Horde_Imap_Client_Data_Envelope($data)
            : $data;
    }

    /**
     * Get envelope data.
     *
     * @return Horde_Imap_Client_Data_Envelope  An envelope object.
     */
    public function getEnvelope()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_ENVELOPE])
            ? clone $this->_data[Horde_Imap_Client::FETCH_ENVELOPE]
            : new Horde_Imap_Client_Data_Envelope();
    }

    /**
     * Set IMAP flags.
     *
     * @param array $flags  An array of IMAP flags.
     */
    public function setFlags(array $flags)
    {
        $this->_data[Horde_Imap_Client::FETCH_FLAGS] = array_map('strtolower', $flags);
    }

    /**
     * Get IMAP flags.
     *
     * @return array  An array of IMAP flags (all flags in lowercase).
     */
    public function getFlags()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_FLAGS])
            ? $this->_data[Horde_Imap_Client::FETCH_FLAGS]
            : array();
    }

    /**
     * Set IMAP internal date.
     *
     * @param mixed $date  Either a Horde_Imap_Client_DateTime object or a
     *                     date string.
     */
    public function setImapDate($date)
    {
        $this->_data[Horde_Imap_Client::FETCH_IMAPDATE] = is_object($date)
            ? $date
            : new Horde_Imap_Client_DateTime($date);
    }

    /**
     * Get internal IMAP date.
     *
     * @return Horde_Imap_Client_DateTime  A date object.
     */
    public function getImapDate()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_IMAPDATE])
            ? clone $this->_data[Horde_Imap_Client::FETCH_IMAPDATE]
            : new Horde_Imap_Client_DateTime();
    }

    /**
     * Set message size.
     *
     * @param integer $size  The size of the message, in bytes.
     */
    public function setSize($size)
    {
        $this->_data[Horde_Imap_Client::FETCH_SIZE] = intval($size);
    }

    /**
     * Get message size.
     *
     * @return integer  The size of the message, in bytes.
     */
    public function getSize()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_SIZE])
            ? $this->_data[Horde_Imap_Client::FETCH_SIZE]
            : 0;
    }

    /**
     * Set UID.
     *
     * @param integer $uid  The message UID.
     */
    public function setUid($uid)
    {
        $this->_data[Horde_Imap_Client::FETCH_UID] = intval($uid);
    }

    /**
     * Get UID.
     *
     * @return integer  The message UID.
     */
    public function getUid()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_UID])
            ? $this->_data[Horde_Imap_Client::FETCH_UID]
            : null;
    }

    /**
     * Set message sequence number.
     *
     * @param integer $seq  The message sequence number.
     */
    public function setSeq($seq)
    {
        $this->_data[Horde_Imap_Client::FETCH_SEQ] = intval($seq);
    }

    /**
     * Get message sequence number.
     *
     * @return integer  The message sequence number.
     */
    public function getSeq()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_SEQ])
            ? $this->_data[Horde_Imap_Client::FETCH_SEQ]
            : null;
    }

    /**
     * Set the modified sequence value for the message.
     *
     * @param integer $modseq  The modseq value.
     */
    public function setModSeq($modseq)
    {
        $this->_data[Horde_Imap_Client::FETCH_MODSEQ] = intval($modseq);
    }

    /**
     * Get the modified sequence value for the message.
     *
     * @return integer  The modseq value.
     */
    public function getModSeq()
    {
        return isset($this->_data[Horde_Imap_Client::FETCH_MODSEQ])
            ? $this->_data[Horde_Imap_Client::FETCH_MODSEQ]
            : null;
    }

    /**
     * Return the internal representation of the data.
     *
     * @return array  The data array.
     */
    public function getRawData()
    {
        return $this->_data;
    }

    /**
     * Merge a fetch object into this one.
     *
     * @param Horde_Imap_Client_Data_Fetch $data  A fetch object.
     */
    public function merge(Horde_Imap_Client_Data_Fetch $data)
    {
        $this->_data = Horde_Array::replaceRecursive($this->_data, $data->getRawData());
    }

    /**
     * Does this object containing cacheable data of the given type?
     *
     * @param integer $type  The type to query.
     *
     * @return boolean  True if the type is cacheable.
     */
    public function exists($type)
    {
        return isset($this->_data[$type]);
    }

    /**
     * Does this object contain only default values for all fields?
     *
     * @since 1.2.0
     *
     * @return boolean  True if object contains default data.
     */
    public function isDefault()
    {
        return empty($this->_data);
    }

    /**
     * Return text representation of a field.
     *
     * @param boolean $stream  Return as a stream?
     * @param mixed $data      The field data (string or resource) or null if
     *                         field does not exist.
     *
     * @return mixed  Requested text representation.
     */
    protected function _msgText($stream, $data)
    {
        if ($stream) {
            if (is_resource($data)) {
                rewind($data);
                return $data;
            }

            $tmp = fopen('php://temp', 'w+');

            if (!is_null($data)) {
                fwrite($tmp, $data);
                rewind($tmp);
            }

            return $tmp;
        }

        if (is_resource($data)) {
            rewind($data);
            return stream_get_contents($data);
        }

        return strval($data);
    }

    /**
     * Return representation of a header field.
     *
     * @param string $id       The header id.
     * @param integer $format  The return format. If self::HEADER_PARSE,
     *                         returns a Horde_Mime_Headers object. If
     *                         self::HEADER_STREAM, returns a stream.
     *                         Otherwise, returns header text.
     * @param integer $key     The array key where the data is stored in the
     *                         internal array.
     *
     * @return mixed  The data in the format specified by $format.
     */
    protected function _getHeaders($id, $format, $key)
    {
        switch ($format) {
        case self::HEADER_STREAM:
            if (!isset($this->_data[$key][$id])) {
                return $this->_msgText(true, null);
            } elseif (is_object($this->_data[$key][$id])) {
                return $this->_getHeaders($id, 0, $key);
            }
            return $this->_msgText(true, $this->_data[$key][$id]);

        case self::HEADER_PARSE:
            if (!isset($this->_data[$key][$id])) {
                return new Horde_Mime_Headers();
            } elseif (is_object($this->_data[$key][$id])) {
                return clone $this->_data[$key][$id];
            }
            return Horde_Mime_Headers::parseHeaders($this->_getHeaders($id, 0, $key));
        }

        if (!isset($this->_data[$key][$id])) {
            return '';
        }

        return is_object($this->_data[$key][$id])
            ? $this->_data[$key][$id]->toString(array('nowrap' => true))
            : $this->_msgText(false, $this->_data[$key][$id]);
    }

}
