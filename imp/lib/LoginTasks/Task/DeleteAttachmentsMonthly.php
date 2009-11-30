<?php
/**
 * Login tasks module that deletes old linked attachments.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andrew Coleman <mercury@appisolutions.net>
 * @package Horde_LoginTasks
 */
class IMP_LoginTasks_Task_DeleteAttachmentsMonthly extends Horde_LoginTasks_Task
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->active = $GLOBALS['prefs']->getValue('delete_attachments_monthly');
        if ($this->active &&
            $GLOBALS['prefs']->isLocked('delete_attachments_monthly')) {
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

        $vfs = VFS::singleton($GLOBALS['conf']['vfs']['type'], Horde::getDriverConfig('vfs', $GLOBALS['conf']['vfs']['type']));
        $path = IMP_Compose::VFS_LINK_ATTACH_PATH . '/' . Horde_Auth::getAuth();

        /* Make sure cleaning is done recursively. */
        $files = $vfs->listFolder($path, null, true, false, true);
        if (($files instanceof PEAR_Error) || !is_array($files)) {
            return false;
        }

        foreach ($files as $dir) {
            $filetime = (isset($dir['date'])) ? $dir['date'] : intval(basename($dir['name']));
            if ($del_time > $filetime) {
                $vfs->deleteFolder($path, $dir['name'], true);
            }
        }

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
