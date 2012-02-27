<?php
/**
 * Envelope data as returned by the IMAP FETCH command (RFC 3501 [7.4.2]).
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
 *
 * @property Horde_Mail_Rfc822_List $bcc  Bcc address(es).
 * @property Horde_Mail_Rfc822_List $cc  Cc address(es).
 * @property Horde_Imap_Client_DateTime $date  IMAP internal date.
 * @property Horde_Mail_Rfc822_List $from  From address(es).
 * @property string $in_reply_to  Message-ID of the message replied to.
 * @property string $message_id  Message-ID of the message.
 * @property Horde_Mail_Rfc822_List $reply_to  Reply-to address(es).
 * @property Horde_Mail_Rfc822_List $sender  Sender address.
 * @property string $subject  Subject.
 * @property Horde_Mail_Rfc822_List $to  To address(es).
 *
 */
class Horde_Imap_Client_Data_Envelope implements Serializable
{
    /** Serializable version. */
    const VERSION = 2;

    /**
     * Internal data array.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Constructor.
     *
     * @var array $data  An array of property names (keys) and values to set
     *                   in this object.
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     */
    public function __get($name)
    {
        if (isset($this->_data[$name])) {
            return is_object($this->_data[$name])
                ? clone $this->_data[$name]
                : $this->_data[$name];
        }

        switch ($name) {
        case 'reply_to':
        case 'sender':
            if ($data = $this->from) {
                return $data;
            }
            // Fall-through

        case 'bcc':
        case 'cc':
        case 'from':
        case 'to':
            return new Horde_Mail_Rfc822_List();

        case 'date':
            return new Horde_Imap_Client_DateTime();

        case 'in_reply_to':
        case 'message_id':
        case 'subject':
            return '';
        }

        return null;
    }

    /**
     */
    public function __set($name, $value)
    {
        switch ($name) {
        case 'bcc':
        case 'cc':
        case 'from':
        case 'reply_to':
        case 'sender':
        case 'to':
            switch ($name) {
            case 'from':
                foreach (array('reply_to', 'sender') as $val) {
                    if ($value == $this->$val) {
                        unset($this->_data[$val]);
                    }
                }
                break;

            case 'reply_to':
            case 'sender':
                if ($value == $this->from) {
                    unset($this->_data[$name]);
                    $value = array();
                }
                break;
            }

            if (!empty($value)) {
                $value->setIteratorFilter(Horde_Mail_Rfc822_List::HIDE_GROUPS);
                foreach ($value as $val) {
                    $val->personal = Horde_Mime_Headers::sanityCheck($name, $val->personal);
                }
                $value->setIteratorFilter();
                $this->_data[$name] = $value;
            }
            break;

        case 'date':
            $this->_data['date'] = new Horde_Imap_Client_DateTime($value);
            break;

        case 'in_reply_to':
        case 'message_id':
            if (strlen($value)) {
                $this->_data[$name] = $value;
            }
            break;

        case 'subject':
            if (strlen($value)) {
                $this->_data[$name] = Horde_Mime_Headers::sanityCheck($name, $value);
            }
            break;
        }
    }

    /**
     */
    public function __isset($name)
    {
        switch ($name) {
        case 'reply_to':
        case 'sender':
            return (isset($this->_data[$name]) ||
                    isset($this->_data['from']));
        }

        return isset($this->_data[$name]);
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        // For first serializable version, we can rely on storage format
        // change to identify instead of explicit VERSION number.
        return serialize(array(
            'd' => $this->_data,
            'v' => self::VERSION
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (empty($data['v']) || ($data['v'] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_data = $data['d'];
    }

}
