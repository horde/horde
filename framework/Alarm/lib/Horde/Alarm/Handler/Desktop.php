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
 * of active alarms by desktop notification through webkit browsers.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Alarm
 */
class Horde_Alarm_Handler_Desktop extends Horde_Alarm_Handler
{
    /**
     * A notification handler.
     *
     * @var Horde_Notification_Handler
     */
    protected $_notification;

    /**
     * An icon URL.
     *
     * @var string
     */
    protected $_icon;

    /**
     * Constructor.
     *
     * @param array $params  Any parameters that the handler might need.
     *                       Required parameter:
     *                       - notification: A Horde_Notification_Handler
     *                         instance.
     *                       Optional parameter:
     *                       - icon: URL of an icon to display.
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
        if (isset($params['icon'])) {
            $this->_icon = $params['icon'];
        }
        */
        $this->_notification = isset($params['notification']) ? $params['notification'] : $GLOBALS['injector']->getInstance('Horde_Notification');
        $this->_icon = isset($params['icon']) ? $params['icon'] : (string)Horde_Themes::img('alerts/alarm.png');
    }

    /**
     * Notifies about an alarm through Horde_Notification.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        $js = sprintf('if(window.webkitNotifications&&!window.webkitNotifications.checkPermission())(function(){var notify=window.webkitNotifications.createNotification(\'%s\',\'%s\',\'%s\');notify.show();(function(){notify.cancel()}).delay(5)})()',
                      $this->_icon,
                      addslashes($alarm['title']),
                      isset($alarm['text']) ? addslashes($alarm['text']) : '');
        $this->_notification->push($js, 'javascript');
    }

    /**
     * Returns a human readable description of the handler.
     *
     * @return string
     */
    public function getDescription()
    {
        return _("Desktop notification (with certain browsers)");
    }
}
