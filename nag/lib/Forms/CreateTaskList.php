<?php
/**
 * Horde_Form for creating task lists.
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
 * The Nag_CreateTaskListForm class provides the form for
 * creating a task list.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Nag
 */
class Nag_CreateTaskListForm extends Horde_Form {

    function Nag_CreateTaskListForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Task List"));

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Color"), 'color', 'colorpicker', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));

        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        $info = array();
        foreach (array('name', 'color', 'description') as $key) {
            $info[$key] = $this->_vars->get($key);
        }
        return Nag::addTasklist($info);
    }

}
