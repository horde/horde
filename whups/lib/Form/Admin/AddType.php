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

class Whups_Form_Admin_AddType extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Type"));
        $this->appendButtons(_("Add Type"));
        $this->addVariable(_("Type Name"), 'name', 'text', true);
        $this->addVariable(_("Type Description"), 'description', 'text', true);
    }

}