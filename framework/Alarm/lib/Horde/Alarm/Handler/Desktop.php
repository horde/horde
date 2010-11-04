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
     * A notification callback.
     *
     * @var callback
     */
    protected $_jsNotify;

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
     *                       - js_notify: A Horde_Notification_Handler
     *                         instance.
     *                       Optional parameter:
     *                       - icon: URL of an icon to display.
     */
    public function __construct(array $params = null)
    {
        /*
        if (!isset($params['js_notify'])) {
            throw new InvalidArgumentException('Parameter \'js_notify\' missing.');
        }
        if (!is_callable($params['js_notify'])) {
            throw new Horde_Alarm_Exception('Parameter \'js_notify\' is not a Horde_Notification_Handler object.');
        }
        $this->_jsNotify = $params['jsNotify'];
        if (isset($params['icon'])) {
            $this->_icon = $params['icon'];
        }
        */
        $this->_jsNotify = isset($params['js_notify'])
            ? $params['js_notify']
            : array('Horde', 'addInlineScript');
        $this->_icon = isset($params['icon']) ? $params['icon'] : (string)Horde_Themes::img('alerts/alarm.png');
    }

    /**
     * Notifies about an alarm through javscript.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        $js = sprintf('if(window.webkitNotifications&&!window.webkitNotifications.checkPermission())(function(){var notify=window.webkitNotifications.createNotification(\'%s\',\'%s\',\'%s\');notify.show();(function(){notify.cancel()}).delay(5)})()',
                      $this->_icon,
                      addslashes($alarm['title']),
                      isset($alarm['text']) ? addslashes($alarm['text']) : '');
        call_user_func($this->_jsNotify($js));
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
