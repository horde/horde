<?php
/**
 * This file contains all Horde_Form classes for attribute administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditAttributeStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit or Delete Attributes"));
        $this->setButtons(array(_("Edit Attribute"), _("Delete Attribute")));

        $attributes = $GLOBALS['whups_driver']->getAttributesForType($vars->get('type'));
        if ($attributes) {
            $params = array();
            foreach ($attributes as $key => $attribute) {
                $params[$key] = $attribute['human_name'];
            }
            $stype = 'enum';
            $type_params = array($params);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no attribute types to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(
            _("Attribute Name"), 'attribute', $stype, true, false, null, $type_params);
    }

}