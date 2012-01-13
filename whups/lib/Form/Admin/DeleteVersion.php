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

class Whups_Form_Admin_DeleteVersion extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Delete Version Confirmation"));

        $version = $vars->get('version');
        $info = $whups_driver->getVersion($version);

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addHidden('', 'version', 'int', true, true);

        $vname = &$this->addVariable(_("Version Name"), 'name', 'text', false, true);
        $vname->setDefault($info['name']);

        $vdesc = &$this->addVariable(_("Version Description"), 'description', 'text', false, true);
        $vdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(_("Really delete this version? This may cause data problems!"), 'yesno', 'enum', true, false, null, $yesno);
    }

}
