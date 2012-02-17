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

class Whups_Form_Admin_AddPriority extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Priority"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Priority Name"), 'name', 'text', true);
        $this->addVariable(_("Priority Description"), 'description', 'text', true);
    }

}