<?php
/**
 * This file contains all Horde_Form classes for priority administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditPriorityStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Edit or Delete Priorities"));
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