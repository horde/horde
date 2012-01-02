<?php
/**
 * This file contains all Horde_Form classes for ticket state administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditStateStepOne extends Horde_Form
{
    function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit or Delete States"));
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