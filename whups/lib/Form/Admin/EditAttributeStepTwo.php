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

class Whups_Form_Admin_EditAttributeStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit Attribute"));

        $attribute = $vars->get('attribute');

        $info = $GLOBALS['whups_driver']->getAttributeDesc($attribute);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'attribute', 'int', true, true);
        $pname = &$this->addVariable(
            _("Attribute Name"), 'attribute_name', 'text', true);
        $pname->setDefault($info['name']);
        $pdesc = &$this->addVariable(
            _("Attribute Description"), 'attribute_description', 'text', true);
        $pdesc->setDefault($info['description']);
        $preq = &$this->addVariable(
            _("Required Attribute?"), 'attribute_required', 'boolean', false);
        $preq->setDefault($info['required']);

        $ptype = &$this->addVariable(
            _("Attribute Type"), 'attribute_type', 'enum', true, false, null,
            array(Whups::fieldTypeNames()));
        $ptype->setAction(
            Horde_Form_Action::factory(
                array('whups', 'whups_reload'),
                array('formname' => 'whups_form_admin_editattributesteptwo_reload')));
        $ptype->setDefault($info['type']);

        $type = $vars->get('attribute_type');
        if (empty($type)) {
            $type = $info['type'];
        }
        foreach (Whups::fieldTypeParams($type) as $param => $param_info) {
            $pparam = &$this->addVariable(
                $param_info['label'],
                'attribute_params[' . $param . ']',
                $param_info['type'],
                false);
            if (isset($info['params'][$param])) {
                $pparam->setDefault($info['params'][$param]);
            }
        }
    }

}