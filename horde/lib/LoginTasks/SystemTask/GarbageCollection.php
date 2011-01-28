<?php
/**
 * Login system task for automated garbage collection tasks.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_LoginTasks_SystemTask_GarbageCollection extends Horde_LoginTasks_SystemTask
{
    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::WEEKLY;

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        /* Clean out static cache files. Any user has a 10% chance of
         * triggering this weekly - no need to have every user trigger
         * this once weekly since these static files are shared among
         * all users. */
        if (rand(0, 9) === 0) {
            foreach (array('cachecss', 'cachejs') as $val) {
                if (!empty($GLOBALS['conf'][$val]) &&
                    ($GLOBALS['conf'][$val] == 'filesystem')) {
                        $this->_staticFilesGc($val);
                }
            }
        }
    }

    /**
     * Do cleanup of static files directory.
     */
    protected function _staticFilesGc($type)
    {
        if (!($lifetime = $GLOBALS['conf'][$type . 'params']['lifetime'])) {
            continue;
        }

        /* Keep a file in the static directory that prevents us from doing
         * garbage collection more than once a day. */
        $curr_time = time();
        $static_dir = $GLOBALS['registry']->get('fileroot', 'horde') . '/static';
        $static_stat = $static_dir . '/gc_' . $type;
        $next_run = null;

        if (file_exists($static_stat)) {
            $next_run = $static_stat;
        }

        if (is_null($next_run) || ($curr_time > $next_run)) {
            file_put_contents($static_stat, $curr_time + 86400);
        }

        if (is_null($next_run) || ($curr_time < $next_run)) {
            return;
        }

        $c_time = $curr_time - $lifetime;
        foreach (glob($static_dir . '/*.' . substr($type, 5)) as $file) {
            if ($c_time > filemtime($file)) {
                @unlink($file);
            }
        }

        Horde::logMessage('Cleaned out static files for ' . $type, 'DEBUG');
    }

}
