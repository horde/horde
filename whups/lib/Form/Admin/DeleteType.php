<?php
/**
 * This file contains all Horde_Form classes for ticket type administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_DeleteType extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Delete Type Confirmation"));

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);

        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(
            _("Type Name"), 'name', 'text', false, true);
        $tname->setDefault($info['name']);

        $tdesc = &$this->addVariable(
            _("Type Description"), 'description', 'text', false, true);
        $tdesc->setDefault($info['description']);

        $states = $whups_driver->getStates($type);
        $tstates = &$this->addVariable(
            _("States for this Type"), 'state', 'set', false, true, null, array($states));
        $tstates->setDefault(array_keys($states));

        $priorities = $whups_driver->getPriorities($type);
        $tpriorities = &$this->addVariable(
            _("Priorities for this Type"), 'priority', 'set', false, true, null,
            array($priorities));
        $tpriorities->setDefault(array_keys($priorities));

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(
            _("Really delete this type? This may cause data problems!"),
            'yesno', 'enum', true, false, null, $yesno);
    }

}