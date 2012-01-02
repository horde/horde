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

class Whups_Form_Admin_AddAttribute extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Attribute"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Attribute Name"), 'attribute_name', 'text', true);
        $this->addVariable(
          _("Attribute Description"), 'attribute_description', 'text', true);
        $this->addVariable(
          _("Required Attribute?"), 'attribute_required', 'boolean', false);

        $v = &$this->addVariable(
          _("Attribute Type"), 'attribute_type', 'enum', true, false, null,
          array(Whups::fieldTypeNames()));
        $v->setDefault('text');
        $v->setAction(
          Horde_Form_Action::factory(
            array('whups', 'whups_reload'),
            array('formname' => 'whups_form_admin_addattribute_reload')));

        $type = $vars->get('attribute_type');
        if (empty($type)) {
            $type = 'text';
        }
        foreach (Whups::fieldTypeParams($type) as $param => $info) {
            $this->addVariable(
              $info['label'], 'attribute_params[' . $param . ']', $info['type'],
              false);
        }
    }

}