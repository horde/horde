<?php
/**
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

/**
 * A Horde_Alarm handler that notifies of active alarms over the
 * Horde_Notification system.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Core
 */
class Horde_Core_Alarm_Handler_Notify extends Horde_Alarm_Handler
{
    /**
     * Whether a sound already had been played during the page request.
     *
     * @var boolean
     */
    protected $_soundPlayed = false;

    /**
     * Notifies about an alarm through Horde_Notification.
     *
     * @param array $alarm  An alarm hash.
     */
    public function notify(array $alarm)
    {
        global $notification;

        $notification->push($alarm['title'], 'horde.alarm', array(
            'alarm' => $alarm
        ));
        if (!empty($alarm['params']['notify']['sound']) &&
            !isset($this->_soundPlayed[$alarm['params']['notify']['sound']])) {
            $notification->attach('audio');
            $notification->push($alarm['params']['notify']['sound'], 'audio');
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
        return Horde_Core_Translation::t("Inline");
    }

    /**
     * Returns a hash of user-configurable parameters for the handler.
     *
     * The parameters are hashes with parameter names as keys and parameter
     * information as values. The parameter information is a hash with the
     * following keys:
     *   - desc: a parameter description.
     *   - required: whether this parameter is required.
     *   - type: the parameter type as a preference type.
     *
     * @return array
     */
    public function getParameters()
    {
        return array(
            'sound' => array(
                'type' => 'sound',
                'desc' => Horde_Core_Translation::t("Play a sound?"),
                'required' => false
            )
        );
    }
}
