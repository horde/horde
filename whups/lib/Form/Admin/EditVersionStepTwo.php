<?php
/**
 * This file contains all Horde_Form classes for version administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditVersionStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Edit Version"));

        $version = $vars->get('version');
        $info = $whups_driver->getVersion($version);

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'version', 'int', true, true);

        $vname = &$this->addVariable(_("Version Name"), 'name', 'text', true);
        $vname->setDefault($info['name']);

        $vdesc = &$this->addVariable(_("Version Description"), 'description', 'text', true);
        $vdesc->setDefault($info['description']);
        $vactive = &$this->addVariable(_("Version Active?"), 'active', 'boolean', false);
        $vactive->setDefault($info['active']);
    }

}
