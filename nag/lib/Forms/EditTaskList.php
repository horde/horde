<?php
/**
 * Horde_Form for editing task lists.
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
 * The Nag_EditTaskListForm class provides the form for
 * editing a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_EditTaskListForm extends Horde_Form {

    /**
     * Task list being edited
     */
    var $_tasklist;

    function Nag_EditTaskListForm(&$vars, &$tasklist)
    {
        $this->_tasklist = &$tasklist;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $tasklist->get('name')));

        $this->addHidden('', 't', 'text', true);
        $this->addVariable(_("Task List Name"), 'name', 'text', true);
        $this->addVariable(_("Task List Description"), 'description', 'longtext', false, false, null, array(4, 60));
        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(_("System Task List"), 'system', 'boolean', false, false, _("System task lists don't have an owner. Only administrators can change the task list settings and permissions."));
        }

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description', 'system') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Nag::updateTasklist($this->_tasklist, $info);
    }

}
