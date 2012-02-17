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

class Whups_Form_Admin_AddVersion extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Version"));

        $this->addHidden('', 'queue', 'int', true, true);
        $this->addVariable(_("Version Name"), 'name', 'text', true);
        $this->addVariable(_("Version Description"), 'description', 'text', true);
        $vactive = &$this->addVariable(_("Version Active?"), 'active', 'boolean', false);
        $vactive->setDefault(true);
    }

}