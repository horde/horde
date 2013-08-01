<?php
/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

/**
 * Login tasks module that deletes old linked attachments.
 *
 * @author   Andrew Coleman <mercury@appisolutions.net>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_LoginTasks_Task_DeleteAttachmentsMonthly extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $conf, $prefs;

        $this->active = !empty($conf['compose']['link_attachments']) &&
            $prefs->getValue('delete_attachments_monthly_keep');
        if ($this->active &&
            $prefs->isLocked('delete_attachments_monthly_keep')) {
            $this->display = Horde_LoginTasks::DISPLAY_NONE;
        }
    }

    /**
     * Purges the old linked attachments.
     *
     * @return boolean  True.
     */
    public function execute()
    {
        $GLOBALS['injector']->getInstance('IMP_Factory_ComposeAtc')->create(null, null, 'linked')->gc();

        return true;
    }

    /**
     * Returns information for the login task.
     *
     * @return string  Description of what the operation is going to do during
     *                 this login.
     */
    public function describe()
    {
        return sprintf(_("All old linked attachments more than %s months old will be deleted."), $GLOBALS['prefs']->getValue('delete_attachments_monthly_keep'));
    }

}
