<?php
/**
 * Defines AJAX calls used exclusively in the smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */
class Nag_Ajax_Application_Handler_Smartmobile extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Toggle the completed flag.
     *
     * Variables used:
     *   - task: TODO
     *   - tasklist: TODO
     *
     * @return array  TODO
     */
    public function smartmobileToggle()
    {
        $out = new stdClass;

        if (!isset($this->vars->task) || !isset($this->vars->tasklist)) {
            $out->error = 'missing parameters';
        } else {
            $nag_task = new Nag_Task_Complete();
            $out = (object)$nag_task->complete($this->vars->task, $this->vars->tasklist);
        }

        return $out;
    }

}
