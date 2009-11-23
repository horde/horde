<?php
/**
 * Horde_Form for deleting task lists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Nag_DeleteTaskListForm class provides the form for
 * deleting a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_DeleteTaskListForm extends Horde_Form {

    /**
     * Task list being deleted
     */
    var $_tasklist;

    function Nag_DeleteTaskListForm(&$vars, &$tasklist)
    {
        $this->_tasklist = &$tasklist;
        parent::Horde_Form($vars, sprintf(_("Delete %s"), $tasklist->get('name')));

        $this->addHidden('', 't', 'text', true);
        $this->addVariable(sprintf(_("Really delete the task list \"%s\"? This cannot be undone and all data on this task list will be permanently removed."), $this->_tasklist->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if ($this->_tasklist->get('owner') != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("Permission denied"));
        }

        // Delete the task list.
        $storage = &Nag_Driver::singleton($this->_tasklist->getName());
        $result = $storage->deleteAll();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to delete \"%s\": %s"), $this->_tasklist->get('name'), $result->getMessage()));
        } else {
            // Remove share and all groups/permissions.
            $result = $GLOBALS['nag_shares']->removeShare($this->_tasklist);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        // Make sure we still own at least one task list.
        if (count(Nag::listTasklists(true)) == 0) {
            // If the default share doesn't exist then create it.
            if (!$GLOBALS['nag_shares']->exists(Horde_Auth::getAuth())) {
                $identity = Horde_Prefs_Identity::singleton();
                $name = $identity->getValue('fullname');
                if (trim($name) == '') {
                    $name = Horde_Auth::getOriginalAuth();
                }
                $tasklist = &$GLOBALS['nag_shares']->newShare(Horde_Auth::getAuth());
                if (is_a($tasklist, 'PEAR_Error')) {
                    return;
                }
                $tasklist->set('name', sprintf(_("%s's Task List"), $name));
                $GLOBALS['nag_shares']->addShare($tasklist);
            }
        }

        return true;
    }

}
