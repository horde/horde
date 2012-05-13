<?php
/**
 * Horde_ActiveSync_Rfc822::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2010-2012 Horde LLC (http://www.horde.org)
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
  * @copyright 2010-2012 Horde LLC (http://www.horde.org)
  * @author    Michael J Rubinsky <mrubinsk@horde.org>
  * @package   ActiveSync
  */
class Horde_ActiveSync_Rfc822
{

    /**
     * The raw message.
     *
     * @var mixed string|stream resource
     */
    protected $_rfc822;

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
     * Constructor.
     *
     * @param mixed $rfc822  The incoming message. Either a string or a
     *                       stream resource.
     */
    public function __construct($rfc822)
    {
        $this->_rfc822 = $rfc822;
        list($this->_hdr_pos, $this->_eol) = $this->_findHeader($this->_rfc822);
    }

    /**
     * Returns the raw message with the message headers stripped.
     *
     * @return mixed  string or stream resource.
     */
    public function getMessage()
    {
        return substr($this->_rfc822, $this->_hdr_pos + $this->_eol);
    }

    /**
     * Return the message headers.
     *
     * @return Horde_Mime_Headers  The header object.
     */
    public function getHeaders()
    {
        return Horde_Mime_Headers::parseHeaders(substr($this->_rfc822, 0, $this->_hdr_pos));
    }

    /**
     * Return a Mime object representing the message.
     *
     * @return Horde_Mime_Part  The Mime object.
     */
    public function getMimeObject()
    {
        return Horde_Mime_Part::parseMessage($this->_rfc822);
    }

    /**
     * Find the location of the end of the header text.
     *
     * @TODO: Be able to use and parse from stream.
     * @param string $text  The text to search.
     *
     * @return array  1st element: Header position, 2nd element: Length of
     *                trailing EOL.
     */
    protected function _findHeader($text)
    {
        $hdr_pos = strpos($text, "\r\n\r\n");
        if ($hdr_pos !== false) {
            return array($hdr_pos, 4);
        }

        $hdr_pos = strpos($text, "\n\n");
        return ($hdr_pos === false)
            ? array(strlen($text), 0)
            : array($hdr_pos, 2);
    }

}