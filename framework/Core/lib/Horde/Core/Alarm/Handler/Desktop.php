<?php
/**
 * @package Alarm
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * The Horde_Alarm_Handler_Desktop class is a Horde_Alarm handler that notifies
 * of active alarms by desktop notification through webkit browsers.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Alarm
 */
class Horde_Core_Alarm_Handler_Desktop extends Horde_Alarm_Handler
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
     *                       @todo Change this to be dynamically generated
     *                             when needed.
     */
    public function __construct(array $params = null)
    {
        if ($GLOBALS['registry']->getView() != Horde_Registry::VIEW_DYNAMIC) {
            if (!isset($params['js_notify'])) {
                throw new BadMethodCallException('Parameter \'js_notify\' missing.');
            }
            if (!is_callable($params['js_notify'])) {
                throw new InvalidArgumentException('Parameter \'js_notify\' is not a valid callback.');
            }
            $this->_jsNotify = $params['js_notify'];
        }
        if (isset($params['icon'])) {
            $this->_icon = $params['icon'];
        }
    }

    /**
     * Notifies about an alarm through javascript.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        global $notification, $page_output, $registry;

        $icon = strval($this->_icon);

        $page_output->addScriptFile('webnotification.js', 'horde');

        if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC) {
            $alarm['params']['desktop']['icon'] = $icon;
            $notification->push($alarm['title'], 'horde.alarm', array(
                'alarm' => $alarm
            ));
       } else {
            $js = sprintf('if(window.Notification){if (window.Notification.permission != "granted") {window.Notification.requestPermission(function(){if (window.Notification.permission == "granted") { new window.Notification("%s", {body: "%s", icon: "%s" }); } }) } else { new window.Notification("%s", {body: "%s", icon: "%s" }); } };',
               $alarm['title'], $alarm['params']['desktop']['subtitle'], $icon, $alarm['title'], $alarm['params']['desktop']['subtitle'], $icon);
            call_user_func($this->_jsNotify, $js);
       }
    }

    /**
     * Returns a human readable description of the handler.
     *
     * @return string
     */
    public function getDescription()
    {
        return Horde_Alarm_Translation::t("Desktop notification (with certain browsers)");
    }

}
