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

class Whups_Form_Admin_AddState extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add State"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("State Name"), 'name', 'text', true);
        $this->addVariable(_("State Description"), 'description', 'text', true);
        $this->addVariable(_("State Category"), 'category', 'enum', false, false, null, array($GLOBALS['whups_driver']->getCategories()));
    }

}