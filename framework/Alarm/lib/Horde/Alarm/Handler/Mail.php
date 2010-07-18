<?php
/**
 * @package Horde_Alarm
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 */

/**
 * The Horde_Alarm_Handler_Mail class is a Horde_Alarm handler that notifies
 * of active alarms by e-mail.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Alarm
 */
class Horde_Alarm_Handler_Mail extends Horde_Alarm_Handler
{
    /**
     * An identity factory.
     *
     * @var Horde_Core_Factory_Identity
     */
    protected $_identity;

    /**
     * A Horde_Mail_Transport object.
     *
     * @var Horde_Mail_Transport
     */
    protected $_mail;

    /**
     * The message charset.
     *
     * @var string
     */
    protected $_charset;

    /**
     * Constructor.
     *
     * @param array $params  Any parameters that the handler might need.
     *                       Required parameter:
     *                       - identity: An identity factory that implements
     *                                   getIdentity().
     *                       - mail: A Horde_Mail_Transport instance.
     *                       - charset: The charset of the messages.
     */
    public function __construct(array $params = null)
    {
        foreach (array('identity', 'mail', 'charset') as $param) {
            if (!isset($params[$param])) {
                throw new Horde_Alarm_Exception('Parameter \'' . $param . '\' missing.');
            }
        }
        if (!method_exists($params['identity'], 'getIdentity')) {
            throw new Horde_Alarm_Exception('Parameter \'identity\' does not implement getIdentity().');
        }
        if (!($params['mail'] instanceof Horde_Mail_Transport)) {
            throw new Horde_Alarm_Exception('Parameter \'mail\' is not a Horde_Mail_Transport object.');
        }
        $this->_identity = $params['identity'];
        $this->_mail     = $params['mail'];
        $this->_charset  = $params['charset'];
    }

    /**
     * Notifies about an alarm by e-mail.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        if (!empty($alarm['internal']['mail']['sent'])) {
            return;
        }

        if (empty($alarm['params']['mail']['email'])) {
            if (empty($alarm['user'])) {
                return;
            }
            $email = $this->_identity
                ->getIdentity($alarm['user'])
                ->getDefaultFromAddress(true);
        } else {
            $email = $alarm['params']['mail']['email'];
        }

        $mail = new Horde_Mime_Mail(array(
            'subject' => $alarm['title'],
            'body' => empty($alarm['params']['mail']['body']) ? $alarm['text'] : $alarm['params']['mail']['body'],
            'to' => $email,
            'from' => $email,
            'charset' => $this->_charset
        ));
        $mail->addHeader('Auto-Submitted', 'auto-generated');
        $mail->addHeader('X-Horde-Alarm', $alarm['title'], $this->_charset);
        $mail->send($this->_mail);

        $alarm['internal']['mail']['sent'] = true;
        $this->alarm->internal($alarm['id'], $alarm['user'], $alarm['internal']);
    }

    /**
     * Resets the internal status of the handler, so that alarm notifications
     * are sent again.
     *
     * @param array $alarm  An alarm hash.
     */
    public function reset(array $alarm)
    {
        $alarm['internal']['mail']['sent'] = false;
        $this->alarm->internal($alarm['id'], $alarm['user'], $alarm['internal']);
    }

    /**
     * Returns a human readable description of the handler.
     *
     * @return string
     */
    public function getDescription()
    {
        return _("Email");
    }

    /**
     * Returns a hash of user-configurable parameters for the handler.
     *
     * The parameters are hashes with parameter names as keys and parameter
     * information as values. The parameter information is a hash with the
     * following keys:
     * - type: the parameter type as a preference type.
     * - desc: a parameter description.
     * - required: whether this parameter is required.
     *
     * @return array
     */
    public function getParameters()
    {
        return array(
            'email' => array(
                'type' => 'text',
                'desc' => _("Email address (optional)"),
                'required' => false));
    }
}
