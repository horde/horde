<?php
/**
 * Copyright 2009-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

/**
 * Login system task for automated garbage collection tasks.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2009-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */
class Horde_LoginTasks_SystemTask_GarbageCollection
extends Horde_LoginTasks_SystemTask
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
        global $injector;

        /* Clean out static cache files. Any user has a 0.1% chance of
         * triggering weekly (these static files are shared amongst all
         * users). */
        if (substr(time(), -3) === '000') {
            /* CSS files. */
            $injector->getInstance('Horde_Core_CssCache')->gc();

            /* Javascript files. */
            $injector->getInstance('Horde_Core_JavascriptCache')->gc();
        }

        /* GC on identity prefs. */
        $injector->getInstance('Horde_Core_Factory_Identity')->create()->prefsGc();
    }

}
