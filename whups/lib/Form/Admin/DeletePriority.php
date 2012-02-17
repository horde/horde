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
class Whups_Form_Admin_DeletePriority extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Delete Priority Confirmation"));

        $priority = $vars->get('priority');
        $info = $whups_driver->getPriority($priority);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'priority', 'int', true, true);

        $pname = &$this->addVariable(_("Priority Name"), 'name', 'text', false, true);
        $pname->setDefault($info['name']);

        $pdesc = &$this->addVariable(_("Priority Description"), 'description', 'text', false, true);
        $pdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(_("Really delete this priority? This may cause data problems!"), 'yesno', 'enum', true, false, null, $yesno);
    }

}
