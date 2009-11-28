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

        return Nag::deleteTasklist($this->_tasklist);
    }

}
