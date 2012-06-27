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

class Sesha_Form_PropertyList extends Horde_Form
{
    public function __construct($vars)
    {
        parent::Horde_Form($vars);
        // This is probably wrong. The library should get the driver 
        // or the properties passed
        $sesha_driver = $GLOBALS['injector']->getInstance('Sesha_Factory_Driver')->create();
        $this->setButtons(array(_("Edit Property"), _("Delete Property")));
        $properties = $sesha_driver->getProperties();
        $params = array();
        foreach ($properties as $property) {
            $params[$property['property_id']] = $property['property'];
        }
        $title = !empty($title) ? $title : _("Edit a property");
        $this->setTitle($title);

        $this->addHidden('', 'actionID', 'text', false, false, null, array('edit_property'));
        if (!count($params)) {
            $fieldtype = 'invalid';
            $params = _("No properties are currently configured. Use the form below to add one.");
        } else {
            $fieldtype = 'enum';
        }
        $this->addVariable(_("Property"), 'property_id', $fieldtype, true, false, null, array($params));
    }

}
