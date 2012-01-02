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

class Whups_Form_Admin_EditVersionStepOne extends Horde_Form
{

    public function __construct(&$vars)
    {
        global $whups_driver;

        parent::__construct($vars, _("Edit or Delete Versions"));
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
