<?php
/**
 * An object that provides a way to switch between UTF7-IMAP and
 * human-readable representations of a mailbox name.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Imap_Client
 *
 * @property string $utf7imap  Mailbox in UTF7-IMAP.
 * @property string $utf8      Mailbox in UTF-8.
 */
class Horde_Imap_Client_Mailbox implements Serializable
{
    /**
     * UTF7-IMAP representation of mailbox.
     * If boolean true, it is identical to UTF-8 representation.
     *
     * @var mixed
     */
    protected $_utf7imap;

    /**
     * UTF8 representation of mailbox.
     *
     * @var string
     */
    protected $_utf8;

    /**
     * Shortcut to obtaining mailbox object.
     *
     * @param string $mbox     The mailbox name.
     * @param mixed $utf7imap  Is mailbox UTF7-IMAP encoded (true), UTF-8
     *                         encoded (false), or should it be
     *                         auto-determined (null).  NOTE:
     *                         auto-determination is not 100% accurate.
     *
     * @return Horde_Imap_Client_Mailbox  A mailbox object.
     */
    static public function get($mbox, $utf7imap = false)
    {
        if ($mbox instanceof Horde_Imap_Client_Mailbox) {
            return $mbox;
        }

        if (is_null($utf7imap)) {
            $mbox = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($mbox);
            $utf7imap = true;
        }

        return new Horde_Imap_Client_Mailbox($mbox, $utf7imap);
    }

    /**
     * Constructor.
     *
     * @param string $mbox     The mailbox name.
     * @param mixed $utf7imap  Is mailbox UTF7-IMAP encoded (true). Otherwise,
     *                         mailbox is taken as UTF-8 encoded.
     */
    public function __construct($mbox, $utf7imap = false)
    {
        if ($utf7imap) {
            $this->_utf7imap = $mbox;
        } else {
            $this->_utf8 = $mbox;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'utf7imap':
            if (!isset($this->_utf7imap)) {
                $n = Horde_Imap_Client_Utf7imap::Utf8ToUtf7Imap($this->_utf8, true);
                $this->_utf7imap = ($n == $this->_utf8)
                    ? true
                    : $n;
            }

            return ($this->_utf7imap === true)
                ? $this->_utf8
                : $this->_utf7imap;

        case 'utf8':
            if (!isset($this->_utf8)) {
                $this->_utf8 = Horde_Imap_Client_Utf7imap::Utf7ImapToUtf8($this->_utf7imap);
                if ($this->_utf8 == $this->_utf7imap) {
                    $this->_utf7imap = true;
                }
            }
            return $this->_utf8;
        }
    }

    /**
     */
    public function __toString()
    {
        return $this->utf8;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return json_encode(array($this->_utf7imap, $this->_utf8));
    }

    /**
     */
    public function unserialize($data)
    {
        list($this->_utf7imap, $this->_utf8) = json_decode($data, true);
    }

}
