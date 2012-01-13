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

class Whups_Form_Admin_DeleteState extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Delete State Confirmation"));

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
