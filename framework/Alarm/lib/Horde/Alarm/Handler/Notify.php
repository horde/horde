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
 * The Horde_Alarm_Handler_Notification class is a Horde_Alarm handler that
 * notifies of active alarms over the Horde_Notification system.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Alarm
 */
class Horde_Alarm_Handler_Notify extends Horde_Alarm_Handler
{
    /**
     * A notification handler.
     *
     * @var Horde_Notification_Handler
     */
    protected $_notification;

    /**
     * Whether a sound already had been played during the page request.
     *
     * @var boolean
     */
    protected $_soundPlayed = false;

    /**
     * Constructor.
     *
     * @param array $params  Any parameters that the handler might need.
     *                       Required parameter:
     *                       - notification: A Horde_Notification_Handler
     *                         instance.
     */
    public function __construct(array $params = null)
    {
        /*
        if (!isset($params['notification'])) {
            throw new Horde_Alarm_Exception('Parameter \'notification\' missing.');
        }
        if (!($params['notification'] instanceof Horde_Notification_Handler)) {
            throw new Horde_Alarm_Exception('Parameter \'notification\' is not a Horde_Notification_Handler object.');
        }
        $this->_notification = $params['notification'];
        */
        $this->_notification = isset($params['notification']) ? $params['notification'] : $GLOBALS['injector']->getInstance('Horde_Notification');
    }

    /**
     * Notifies about an alarm through Horde_Notification.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        $this->_notification->push($alarm['title'], 'horde.alarm', array('alarm' => $alarm));
        if (!empty($alarm['params']['notify']['sound']) &&
            !isset($this->_soundPlayed[$alarm['params']['notify']['sound']])) {
            $this->_notification->attach('audio');
            $this->_notification->push($alarm['params']['notify']['sound'], 'audio');
            $this->_soundPlayed[$alarm['params']['notify']['sound']] = true;
        }
    }

    /**
     * Returns a human readable description of the handler.
     *
     * @return string
     */
    public function getDescription()
    {
        return Horde_Alarm_Translation::t("Inline");
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
            'sound' => array(
                'type' => 'sound',
                'desc' => Horde_Alarm_Translation::t("Play a sound?"),
                'required' => false));
    }
}
