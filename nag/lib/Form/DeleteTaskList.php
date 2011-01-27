<?php
/**
 * Horde_Form for deleting task lists.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Nag
 */
/**
 * The Nag_DeleteTaskListForm class provides the form for
 * deleting a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_Form_DeleteTaskList extends Horde_Form
{
    /**
     * Task list being deleted.
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
        parent::__construct($vars, sprintf(_("Delete %s"), $tasklist->get('name')));
        $this->addHidden('', 't', 'text', true);
        $this->addVariable(
            sprintf(_("Really delete the task list \"%s\"? This cannot be undone and all data on this task list will be permanently removed."),
            $this->_tasklist->get('name')), 'desc', 'description', false
        );
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    public function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        return Nag::deleteTasklist($this->_tasklist);
    }

}
