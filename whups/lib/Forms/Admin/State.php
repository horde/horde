<?php
/**
 * This file contains all Horde_Form classes for ticket state administration.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddStateForm extends Horde_Form {

    function AddStateForm(&$vars)
    {
        parent::Horde_Form($vars, _("Add State"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("State Name"), 'name', 'text', true);
        $this->addVariable(_("State Description"), 'description', 'text', true);
        $this->addVariable(_("State Category"), 'category', 'enum', false, false, null, array($GLOBALS['whups_driver']->getCategories()));
    }

}

class EditStateStep1Form extends Horde_Form {

    function EditStateStep1Form(&$vars)
    {
        parent::Horde_Form($vars, _("Edit or Delete States"));
        $this->setButtons(array(_("Edit State"), _("Delete State")));

        $states = $GLOBALS['whups_driver']->getStates($vars->get('type'));
        if ($states) {
            $stype = 'enum';
            $type_params = array($states);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no states to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("State Name"), 'state', $stype, false, false, null, $type_params);
    }

}

class EditStateStep2Form extends Horde_Form {

    function EditStateStep2Form(&$vars)
    {
        parent::Horde_Form($vars, _("Edit State"));

        $state = $vars->get('state');
        $info = $GLOBALS['whups_driver']->getState($state);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'state', 'int', true, true);

        $sname = &$this->addVariable(_("State Name"), 'name', 'text', true);
        $sname->setDefault($info['name']);

        $sdesc = &$this->addVariable(_("State Description"), 'description', 'text', true);
        $sdesc->setDefault($info['description']);

        $scat = &$this->addVariable(_("State Category"), 'category', 'enum', true, false, null, array($GLOBALS['whups_driver']->getCategories()));
        $scat->setDefault($info['category']);
    }

}

class DefaultStateForm extends Horde_Form {

    function DefaultStateForm(&$vars)
    {
        parent::Horde_Form($vars, _("Set Default State"));

        $this->setButtons(array(_("Set Default State")));

        $states = $GLOBALS['whups_driver']->getStates(
            $vars->get('type'),
            array('unconfirmed', 'new', 'assigned'));
        if ($states) {
            $stype = 'enum';
            $type_params = array($states);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no states to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $var = &$this->addVariable(_("State Name"), 'state', $stype, false,
                                   false, null, $type_params);
        $var->setDefault($GLOBALS['whups_driver']->getDefaultState($vars->get('type')));
    }

}

class DeleteStateForm extends Horde_Form {

    function DeleteStateForm(&$vars)
    {
        parent::Horde_Form($vars, _("Delete State Confirmation"));

        $state = $vars->get('state');
        $info = $GLOBALS['whups_driver']->getState($state);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'state', 'int', true, true);

        $sname = &$this->addVariable(_("State Name"), 'name', 'text', false, true);
        $sname->setDefault($info['name']);

        $sdesc = &$this->addVariable(_("State Description"), 'description', 'text', false, true);
        $sdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(_("Really delete this state? This may cause data problems!"), 'yesno', 'enum', true, false, null, $yesno);
    }

}
