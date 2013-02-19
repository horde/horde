<?php
/**
 * @package Alarm
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * The Horde_Alarm_Handler_Mail class is a Horde_Alarm handler that notifies
 * of active alarms by e-mail.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Alarm
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
     * Constructor.
     *
     * @param array $params  Any parameters that the handler might need.
     *                       Required parameter:
     *                       - identity: An identity factory that implements
     *                                   create().
     *                       - mail: A Horde_Mail_Transport instance.
     *
     * @throws Horde_Alarm_Exception
     */
    public function __construct(array $params = null)
    {
        foreach (array('identity', 'mail') as $param) {
            if (!isset($params[$param])) {
                throw new Horde_Alarm_Exception('Parameter \'' . $param . '\' missing.');
            }
        }
        if (!method_exists($params['identity'], 'create')) {
            throw new Horde_Alarm_Exception('Parameter \'identity\' does not have a method create().');
        }
        if (!($params['mail'] instanceof Horde_Mail_Transport)) {
            throw new Horde_Alarm_Exception('Parameter \'mail\' is not a Horde_Mail_Transport object.');
        }
        $this->_identity = $params['identity'];
        $this->_mail     = $params['mail'];
    }

    /**
     * Notifies about an alarm by e-mail.
     *
     * @param array $alarm  An alarm hash.
     *
     * @throws Horde_Alarm_Exception
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
                ->create($alarm['user'])
                ->getDefaultFromAddress(true);
        } else {
            $email = $alarm['params']['mail']['email'];
        }

        try {
            $mail = new Horde_Mime_Mail(array(
                'Subject' => $alarm['title'],
                'To' => $email,
                'From' => $email,
                'Auto-Submitted' => 'auto-generated',
                'X-Horde-Alarm' => $alarm['title']));
            if (isset($alarm['params']['mail']['mimepart'])) {
                $mail->setBasePart($alarm['params']['mail']['mimepart']);
            } elseif (empty($alarm['params']['mail']['body'])) {
                $mail->setBody($alarm['text']);
            } else {
                $mail->setBody($alarm['params']['mail']['body']);
            }

            $mail->send($this->_mail);
        } catch (Horde_Mime_Exception $e) {
            throw new Horde_Alarm_Exception($e);
        }

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
        return Horde_Alarm_Translation::t("Email");
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
                'desc' => Horde_Alarm_Translation::t("Email address (optional)"),
                'required' => false));
    }
}
