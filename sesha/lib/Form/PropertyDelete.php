<?php
/**
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */

class Sesha_Form_PropertyDelete extends Horde_Form
{
    public function __construct($vars)
    {
        parent::__construct($vars);

        $this->appendButtons(_("Delete Property"));
        $params = array('yes' => _("Yes"),
                        'no' => _("No"));
        $desc = _("Really delete this property?");

        $this->addHidden('', 'actionID', 'text', false, false, null, array('delete_property'));
        $this->addHidden('', 'property_id', 'text', false, false, null);
        $this->addVariable(_("Confirm"), 'confirm', 'enum', true, false, $desc, array($params));
    }

}
