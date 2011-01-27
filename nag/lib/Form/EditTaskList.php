<?php
/**
 * Horde_Form for editing task lists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */
/**
 * The Nag_EditTaskListForm class provides the form for
 * editing a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_EditTaskList extends Horde_Form
{
    /**
     * Task list being edited
     *
     *
     * @var Horde_Share_Object
     */
    protected $_tasklist;

    /**
     *
     * @param array $vars
     * @param Horde_Share_Object $tasklist
     */
    public function __construct($vars, Horde_Share_Object $tasklist)
    {
        $this->_tasklist = $tasklist;
        parent::__construct($vars, sprintf(_("Edit %s"), $tasklist->get('name')));
        $this->addHidden('', 't', 'text', true);
        $this->addVariable(_("Task List Name"), 'name', 'text', true);
        $this->addVariable(_("Task List Description"), 'description', 'longtext', false, false, null, array(4, 60));
        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(
                _("System Task List"), 'system', 'boolean', false, false,
                _("System task lists don't have an owner. Only administrators can change the task list settings and permissions.")
            );
        }
        $this->setButtons(array(_("Save")));
    }

    public function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description', 'system') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Nag::updateTasklist($this->_tasklist, $info);
    }

}
