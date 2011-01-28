<?php
/**
 * This file contains all Horde_Form classes for version administration.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddVersionForm extends Horde_Form {

    function AddVersionForm(&$vars)
    {
        parent::Horde_Form($vars, _("Add Version"));

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addVariable(_("Version Name"), 'name', 'text', true);
        $this->addVariable(_("Version Description"), 'description', 'text', true);
        $vactive = &$this->addVariable(_("Version Active?"), 'active', 'boolean', false);
        $vactive->setDefault(true);
    }

}

class EditVersionStep1Form extends Horde_Form {

    function EditVersionStep1Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Edit or Delete Versions"));
        $this->setButtons(array(_("Edit Version"), _("Delete Version")));

        $versions = $whups_driver->getVersions($vars->get('queue'), true);
        if ($versions) {
            $vtype = 'enum';
            $type_params = array($versions);
        } else {
            $vtype = 'invalid';
            $type_params = array(_("There are no versions to edit"));
        }

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addVariable(_("Version Name"), 'version', $vtype, true, false, null, $type_params);
    }

}

class EditVersionStep2Form extends Horde_Form {

    function EditVersionStep2Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Edit Version"));

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

class DeleteVersionForm extends Horde_Form {

    function DeleteVersionForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Delete Version Confirmation"));

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
