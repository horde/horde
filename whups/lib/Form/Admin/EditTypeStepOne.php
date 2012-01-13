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
class Whups_Form_Admin_EditTypeStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Edit or Delete Types"));
        $this->setButtons(
            array(
                _("Edit Type"),
                _("Clone Type"),
                _("Delete Type")));

        $types = $whups_driver->getAllTypes();
        if ($types) {
            $ttype = 'enum';
            $type_params = array($types);
        } else {
            $ttype = 'invalid';
            $type_params = array(_("There are no types to edit"));
        }

        $this->addVariable(
            _("Type Name"), 'type', $ttype, true, false, null, $type_params);
    }

}