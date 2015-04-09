<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * Abstract class representing a PGP element that can be represented within
 * ASCII armor (RFC 4880 [6]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */
class Horde_Pgp_Element_Armored
extends Horde_Pgp_Element
{
    /**
     * Armor header.
     *
     * @var string
     */
    static protected $_header = '';

    /**
     * Message object.
     *
     * @var OpenPGP_Message
     */
    protected $_message;

    /**
     * Convert raw PGP data to a PGP element.
     *
     * @param string $data  Raw PGP data.
     *
     * @return Horde_Pgp_Element  PGP element object.
     */
    static public function createFromRawData($data)
    {
        return static::create(
            OpenPGP::enarmor($data, static::getArmorHeader())
        );
    }

    /**
     * Returns the armor header identifier for this part.
     *
     * @return string  Armor header identifier.
     */
    static public function getArmorHeader()
    {
        return 'PGP ' . static::$_header;
    }

    /**
     * Returns header information for an armor part.
     *
     * @return array  Keys are header names, values are header values.
     */
    public function getHeaders()
    {
        $out = array();
        $stream = $this->_data;

        $pos = $stream->pos();
        $stream->seek($this->_start, false);

        /* Discard armor header line. */
        $stream->getToChar("\n", false);

        while (($line = rtrim($stream->getToChar("\n", false), "\r")) !== '') {
            list($header, $val) = explode(':', $line, 2);
            $out[trim($header)] = trim($val);
        }

        $this->_resetPos($pos);

        return $out;
    }

    /**
     */
    public function getData()
    {
        return $this->_getData(false);
    }

    /**
     * Return armored data.
     *
     * @param string $base64_only  Only return the base64-encoded data?
     *
     * @return string  Armored data.
     */
    protected function _getData($base64_only)
    {
        $stream = $this->_data;

        $pos = $stream->pos();
        $stream->seek($this->_start, false);

        while (rtrim($stream->getToChar("\n", false), "\r") !== '') {}
        $start = $stream->pos();

        $stream->seek($this->_end - 1, false);
        $end = $stream->search("\n", true, false);

        if ($base64_only) {
            /* Strip checksum, if it exists. */
            $checksum_pos = $stream->search("\n", true, false);
            if ($stream->peek(2) === "\n=") {
                $end = $checksum_pos;
            }
        }

        $out = $stream->getString($start, $end);
        $this->_resetPos($pos);

        return $out;
    }

    /**
     * Return unarmored data.
     *
     * @return string  Unarmored data.
     */
    public function getUnarmoredData()
    {
        return base64_decode($this->_getData(true));
    }

    /**
     * Return a OpenPGP message object for this data.
     *
     * @return OpenPGP_Message  Message object
     */
    public function getMessageOb()
    {
        if (!$this->_message) {
            Horde_Pgp_Backend_Openpgp::autoload();
            $this->_message = OpenPGP_Message::parse($this->getUnarmoredData());
        }

        return $this->_message;
    }

}
