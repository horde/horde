<?php
/**
 * Horde_ActiveSync_Rfc822::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2013 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
 /**
  * Horde_ActiveSync_Rfc822:: class provides functionality related to dealing
  * with raw RFC822 message strings within an ActiveSync context.
  *
  * @license   http://www.horde.org/licenses/gpl GPLv2
  *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
  *            Version 2, the distribution of the Horde_ActiveSync module in or
  *            to the United States of America is excluded from the scope of this
  *            license.
  * @copyright 2010-2013 Horde LLC (http://www.horde.org)
  * @author    Michael J Rubinsky <mrubinsk@horde.org>
  * @package   ActiveSync
  */
class Horde_ActiveSync_Rfc822
{

    /**
     * The memory limit for use with the PHP temp stream.
     *
     * @var integer
     */
    static public $memoryLimit = 2097152;

    /**
     * Position of end of headers.
     *
     * @var integer
     */
    protected $_hdr_pos;

    /**
     * The size of the EOL sequence.
     *
     * @var integer
     */
    protected $_eol;

    /**
     * The raw message data in a stream.
     *
     * @var Horde_Stream
     */
    protected $_stream;

    /**
     * Constructor.
     *
     * @param mixed $rfc822  The incoming message. Either a string or a
     *                       stream resource.
     */
    public function __construct($rfc822)
    {
        if (is_resource($rfc822)) {
            $this->_stream = new Horde_Stream_Existing(array('stream' => $rfc822));
            rewind($this->_stream->stream);
        } else {
            $this->_stream = new Horde_Stream_Temp(array('max_memory' => self::$memoryLimit));
            $this->_stream->add($rfc822, true);
        }
        list($this->_hdr_pos, $this->_eol) = $this->_findHeader();
    }

    /**
     * Returns the raw message with the message headers stripped.
     *
     * @return Horde_Stream
     */
    public function getMessage()
    {
        // Position to after the headers.
        fseek($this->_stream->stream, $this->_hdr_pos + $this->_eol);
        $new_stream = new Horde_Stream_Temp(array('max_memory' => self::$memoryLimit));
        $new_stream->add($this->_stream, true);
        return $new_stream;
    }

    /**
     * Return the raw message data.
     *
     * @return stream resource
     */
    public function getString()
    {
        $this->_stream->rewind();
        return $this->_stream->stream;
    }

    /**
     * Return the message headers.
     *
     * @return Horde_Mime_Headers  The header object.
     */
    public function getHeaders()
    {
        $hdr_text = $this->_stream->read($this->_hdr_pos, true);
        return Horde_Mime_Headers::parseHeaders($hdr_text);
    }

    /**
     * Return a Mime object representing the entire message.
     *
     * @return Horde_Mime_Part  The Mime object.
     */
    public function getMimeObject()
    {
        $this->_stream->rewind();
        $part = Horde_Mime_Part::parseMessage($this->_stream->getString());
        $part->isBasePart(true);

        return $part;
    }

    /**
     * Return the length of the message data.
     *
     * @return integer
     */
    public function getBytes()
    {
        if (!isset($this->_bytes)) {
            $this->_bytes = $this->_stream->length();
        }

        return $this->_bytes;
    }

    /**
     * Find the location of the end of the header text.
     *
     * @return array  1st element: Header position, 2nd element: Length of
     *                trailing EOL.
     */
    protected function _findHeader()
    {
        $i = 0;
        while (!$this->_stream->eof())
            $data = $this->_stream->string(null, 8192);
            $hdr_pos = strpos($data, "\r\n\r\n");
            if ($hdr_pos !== false) {
                return array($hdr_pos + ($i * 8192), 4);
            }
            $hdr_pos = strpos($data, "\n\n");
            if ($hdr_pos !== false) {
                return array($hdr_pos + ($i * 8192), 2);
            }
            $i++;
        }
        $this->_stream->end();
        return array($this->_stream->pos(), 0);
    }

}