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

class Whups_Form_Admin_CloneType extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);
        parent::__construct(
            $vars, sprintf(_("Make a clone of %s"), $info['name']));

        $this->setButtons(_("Clone"));
        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(
            _("Name of the cloned copy"), 'name', 'text', true);
        $tname->setDefault(sprintf(_("Copy of %s"), $info['name']));

        $tdesc = &$this->addVariable(
            _("Clone Description"), 'description', 'text', true);
        $tdesc->setDefault($info['description']);
    }

}
