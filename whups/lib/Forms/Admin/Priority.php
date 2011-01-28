<?php
/**
 * This file contains all Horde_Form classes for priority administration.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddPriorityForm extends Horde_Form {

    function AddPriorityForm(&$vars)
    {
        parent::Horde_Form($vars, _("Add Priority"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Priority Name"), 'name', 'text', true);
        $this->addVariable(_("Priority Description"), 'description', 'text', true);
    }

}

class EditPriorityStep1Form extends Horde_Form {

    function EditPriorityStep1Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Edit or Delete Priorities"));
        $this->setButtons(array(_("Edit Priority"), _("Delete Priority")));

        $priorities = $whups_driver->getPriorities($vars->get('type'));
        if ($priorities) {
            $stype = 'enum';
            $type_params = array($priorities);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no priorities to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Priority Name"), 'priority', $stype, true, false, null, $type_params);
    }

}

class EditPriorityStep2Form extends Horde_Form {

    function EditPriorityStep2Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Edit Priority"));

        $priority = $vars->get('priority');
        $info = $whups_driver->getPriority($priority);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'priority', 'int', true, true);

        $pname = &$this->addVariable(_("Priority Name"), 'name', 'text', true);
        $pname->setDefault($info['name']);

        $pdesc = &$this->addVariable(_("Priority Description"), 'description', 'text', true);
        $pdesc->setDefault($info['description']);
    }

}

class DefaultPriorityForm extends Horde_Form {

    function DefaultPriorityForm(&$vars)
    {
        parent::Horde_Form($vars, _("Set Default Priority"));

        $this->setButtons(array(_("Set Default Priority")));

        $priorities = $GLOBALS['whups_driver']->getPriorities($vars->get('type'));
        if ($priorities) {
            $stype = 'enum';
            $type_params = array($priorities);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no priorities to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $var = &$this->addVariable(_("Priority Name"), 'priority', $stype, false,
                                   false, null, $type_params);
        $var->setDefault($GLOBALS['whups_driver']->getDefaultPriority($vars->get('type')));
    }

}

class DeletePriorityForm extends Horde_Form {

    function DeletePriorityForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Delete Priority Confirmation"));

        $priority = $vars->get('priority');
        $info = $whups_driver->getPriority($priority);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'priority', 'int', true, true);

        $pname = &$this->addVariable(_("Priority Name"), 'name', 'text', false, true);
        $pname->setDefault($info['name']);

        $pdesc = &$this->addVariable(_("Priority Description"), 'description', 'text', false, true);
        $pdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(_("Really delete this priority? This may cause data problems!"), 'yesno', 'enum', true, false, null, $yesno);
    }

}
