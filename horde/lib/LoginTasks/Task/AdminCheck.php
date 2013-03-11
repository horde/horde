<?php
/**
 * Login task to check various Horde configuration/setup values, and then
 * report failures to an admin via the notification system.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */
class Horde_LoginTasks_Task_AdminCheck extends Horde_LoginTasks_Task
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
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['registry']->isAdmin();
    }

    /**
     * Perform all functions for this task.
     */
    public function execute()
    {
        /* Check if test script is active. */
        if (empty($GLOBALS['conf']['testdisable'])) {
            $GLOBALS['notification']->push(_("The test script is currently enabled. For security reasons, disable test scripts when you are done testing (see horde/docs/INSTALL)."), 'horde.warning');
        }

        if (!empty($GLOBALS['conf']['sql']['phptype'])) {
            /* Check for outdated DB schemas. */
            $migration = new Horde_Core_Db_Migration();
            foreach ($migration->apps as $app) {
                $migrator = $migration->getMigrator($app);
                if ($migrator->getTargetVersion() > $migrator->getCurrentVersion()) {
                    $GLOBALS['notification']->push(
                        Horde::link(Horde::url('admin/config/index.php', false, array('app' => 'horde'))) . _("At least one database schema is outdated.") . '</a>',
                        'horde.warning',
                        array('content.raw', 'sticky')
                    );
                    break;
                }
            }
        }
    }
}
