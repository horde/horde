<?php
/**
 * Login task to output last login information.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_LoginTasks_Task_LastLogin extends Horde_LoginTasks_Task
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::EVERY;

    /**
     * Display type.
     *
     * @var integer
     */
    public $display = Horde_LoginTasks::DISPLAY_NONE;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        global $injector, $notification, $prefs, $registry;

        /* Fetch the user's last login time. */
        $old_login = @unserialize($prefs->getValue('last_login'));

        /* Set the timezone to the current default. */
        $registry->setTimeZone();

        /* Display it, if we have a notification object and the
         * show_last_login preference is active. */
        if (isset($notification) && $prefs->getValue('show_last_login')) {
            $date_format = $prefs->getValue('date_format') . ' (' . $prefs->getValue('time_format') . ')';

            if (empty($old_login['time'])) {
                $notification->push(_("Last login: Never"), 'horde.message');
            } elseif (empty($old_login['host'])) {
                $notification->push(sprintf(_("Last login: %s"), strftime($date_format, $old_login['time'])), 'horde.message');
            } else {
                $notification->push(sprintf(_("Last login: %s from %s"), strftime($date_format, $old_login['time']), $old_login['host']), 'horde.message');
            }
        }

        /* Set the user's last_login information. */
        $host = empty($_SERVER['HTTP_X_FORWARDED_FOR'])
            ? $_SERVER['REMOTE_ADDR']
            : $_SERVER['HTTP_X_FORWARDED_FOR'];

        if ($dns = $injector->getInstance('Net_DNS2_Resolver')) {
            $ptrdname = $host;
            try {
                if ($response = $dns->query($host, 'PTR')) {
                    foreach ($response->answer as $val) {
                        if (isset($val->ptrdname)) {
                            $ptrdname = $val->ptrdname;
                            break;
                        }
                    }
                }
            } catch (Net_DNS2_Exception $e) {}
        } else {
            $ptrdname = @gethostbyaddr($host);
        }

        $prefs->setValue('last_login', serialize(array(
            'host' => $ptrdname,
            'time' => time()
        )));
    }

}
