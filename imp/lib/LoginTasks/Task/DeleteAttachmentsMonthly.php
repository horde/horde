<?php
/**
 * Login tasks module that deletes old linked attachments.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
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
     * Purges the old linked attachment folders.
     *
     * @return boolean  Whether any old attachments were deleted.
     */
    public function execute()
    {
        /* Find the UNIX timestamp of the last second that we will not
         * purge. */
        $del_time = gmmktime(0, 0, 0, date('n') - $GLOBALS['prefs']->getValue('delete_attachments_monthly_keep'), 1, date('Y'));

        try {
            $vfs = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create();
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }
        $path = IMP_Compose::VFS_LINK_ATTACH_PATH . '/' . $GLOBALS['registry']->getAuth();

        /* Make sure cleaning is done recursively. */
        try {
            $files = $vfs->listFolder($path, null, true, false, true);
        } catch (Horde_Vfs_Exception $e) {
            return false;
        }

        $retval = false;
        foreach ($files as $dir) {
            $filetime = (isset($dir['date'])) ? $dir['date'] : intval(basename($dir['name']));
            if ($del_time > $filetime) {
                try {
                    $vfs->deleteFolder($path, $dir['name'], true);
                    $retval = true;
                } catch (Horde_Vfs_Exception $e) {}
            }
        }

        return $retval;
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
