<?php
/**
 * Login task that checks for drafts to be recovered.
 *
 * Copyright 2011 The Horde Project (http:://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_RecoverDraft extends Horde_LoginTasks_Task
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
     * Recovers saved drafts.
     */
    public function execute()
    {
        /* Check for drafts due to session timeouts. */
        $GLOBALS['injector']->getInstance('IMP_Factory_Compose')->create()->recoverSessionExpireDraft();
    }

}
