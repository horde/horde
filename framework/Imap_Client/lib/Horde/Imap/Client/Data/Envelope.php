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
 * @property array $bcc                        Bcc address(es).
 * @property array $bcc_decoded                Bcc address(es) (MIME decoded).
 *                                             (Since 1.1.0)
 * @property array $cc                         Cc address(es).
 * @property array $cc_decoded                 Cc address(es) (MIME decoded).
 *                                             (Since 1.1.0)
 * @property Horde_Imap_Client_DateTime $date  IMAP internal date.
 * @property array $from                       From address(es).
 * @property array $from_decoded               From address(es) (MIME decoded).
 *                                             (Since 1.1.0)
 * @property string $in_reply_to               Message-ID of the message
 *                                             replied to.
 * @property string $message_id                Message-ID of the message.
 * @property array $reply_to                   Reply-to address(es).
 * @property array $reply_to_decoded           Reply-to address(es) (MIME
 *                                             decoded).
 *                                             (Since 1.1.0)
 * @property array $sender                     Sender address.
 * @property array $sender_decoded             Sender address (MIME decoded).
 *                                             (Since 1.1.0)
 * @property string $subject                   Subject.
 * @property string $subject_decoded           Subject (MIME decoded).
 *                                             (Since 1.1.0)
 * @property array $to                         To address(es).
 * @property array $to_decoded                 To address(es) (MIME decoded).
 *                                             (Since 1.1.0)
 *
 * For array (address) properties, the values will be
 * Horde_Mail_Rfc822_Address objects (since 1.4.4; the object is fully BC
 * with the former array return).
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
            return array();

        case 'date':
            return new Horde_Imap_Client_DateTime();

        case 'subject_decoded':
            return Horde_Mime::decode($this->subject, 'UTF-8');

        case 'bcc_decoded':
        case 'cc_decoded':
        case 'from_decoded':
        case 'reply_to_decoded':
        case 'sender_decoded':
        case 'to_decoded':
            $tmp = $this->__get(substr($name, 0, strrpos($name, '_')));
            foreach (array_keys($tmp) as $key) {
                $tmp[$key]->personal = Horde_Mime::decode($tmp[$key]->personal, 'UTF-8');
            }
            return $tmp;

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
                $this->_data[$name] = array();
                foreach ($value as $val) {
                    $val->personal = Horde_Mime_Headers::sanityCheck($name, $val->personal, array(
                        'encode' => true
                    ));
                    $this->_data[$name][] = $val;
                }
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
                $this->_data[$name] = Horde_Mime_Headers::sanityCheck($name, $value, array(
                    'encode' => true
                ));
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
