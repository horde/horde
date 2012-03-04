<?php
/**
 * Activities to run on shutdown for Horde_Registry.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Core
 */
class Horde_Core_Queue_Registry implements Horde_Queue_Task
{
    /**
     * Run the task.
     */
    public function run()
    {
        /* Register access key logger for translators. */
        if (!empty($GLOBALS['conf']['log_accesskeys'])) {
            Horde::getAccessKey(null, null, true);
        }

        /* Register memory tracker if logging in debug mode. */
        if (function_exists('memory_get_peak_usage')) {
            Horde::logMessage('Max memory usage: ' . memory_get_peak_usage(true) . ' bytes', 'DEBUG');
        }
    }

}
