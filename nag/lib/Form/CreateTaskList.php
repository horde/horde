<?php
/**
 * Horde_Form for creating task lists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */
/**
 * The Nag_CreateTaskListForm class provides the form for
 * creating a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_CreateTaskList extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars, _("Create Task List"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        if ($GLOBALS['registry']->isAdmin()) {
            $this->addVariable(
                _("System Task List"), 'system', 'boolean', false, false,
                _("System task lists don't have an owner. Only administrators can change the task list settings and permissions."));
        }

        $this->setButtons(array(_("Create")));
    }

    public function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description', 'system') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Nag::addTasklist($info);
    }

}
