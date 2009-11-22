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

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $this->_tasklist->set('name', $this->_vars->get('name'));
        $this->_tasklist->set('desc', $this->_vars->get('description'));
        $result = $this->_tasklist->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save task list \"%s\": %s"), $id, $result->getMessage()));
        }
        return true;
    }

}
