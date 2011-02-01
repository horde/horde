<?php
/**
 * Envelope data as returned by the IMAP FETCH command (RFC 3501 [7.4.2]).
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Imap_Client
 *
 * @property array $bcc                        Bcc address(es).
 * @property array $cc                         Cc address(es).
 * @property Horde_Imap_Client_DateTime $date  IMAP internal date.
 * @property array $from                       From address(es).
 * @property string $in_reply_to               Message-ID of the message
 *                                             replied to.
 * @property string $message_id                Message-ID of the message.
 * @property array $reply_to                   Reply-to address(es).
 * @property array $sender                     Sender address.
 * @property string $subject                   Subject.
 * @property array $to                         To address(es).
 *
 * For array properties, the value will be an array of arrays. Each of the
 * the underlying arrays corresponds to a single address and contains
 * these keys: 'personal', 'adl', 'mailbox', and 'host'.
 */
class Horde_Imap_Client_Data_Envelope
{
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
            return $this->_data[$name];
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
            return array();

        case 'date':
            return new Horde_Imap_Client_DateTime('@0', new DateTimeZone('UTC'));
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
            $save = array();

            if (is_array($value)) {
                foreach ($value as $val) {
                    $save[] = (array)$val;
                }
            }

            switch ($name) {
            case 'from':
                foreach (array('reply_to', 'sender') as $val) {
                    if ($save == $this->$val) {
                        unset($this->_data[$val]);
                    }
                }
                break;

            case 'reply_to':
            case 'sender':
                if ($save == $this->from) {
                    unset($this->_data[$name]);
                    $save = array();
                }
                break;
            }

            if (!empty($save)) {
                $this->_data[$name] = $save;
            }
            break;

        case 'date':
            $this->_data['date'] = new Horde_Imap_Client_DateTime($value, new DateTimeZone('UTC'));
            break;

        case 'in_reply_to':
        case 'message_id':
        case 'subject':
            if (strlen($value)) {
                $this->_data[$name] = $value;
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

}
