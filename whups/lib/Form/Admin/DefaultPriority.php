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

class Whups_Form_Admin_DefaultPriority extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Set Default Priority"));

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