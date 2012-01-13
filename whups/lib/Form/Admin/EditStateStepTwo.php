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

class Whups_Form_Admin_EditStateStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit State"));

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
