<?php
/**
 * This file contains all Horde_Form classes for attribute administration.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddAttributeDescForm extends Horde_Form {

    function AddAttributeDescForm(&$vars)
    {
        require_once dirname(__FILE__) . '/../Action.php';

        parent::Horde_Form($vars, _("Add Attribute"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Attribute Name"), 'attribute_name', 'text', true);
        $this->addVariable(_("Attribute Description"), 'attribute_description',
                           'text', true);
        $this->addVariable(_("Required Attribute?"), 'attribute_required',
                           'boolean', false);

        $v = &$this->addVariable(_("Attribute Type"), 'attribute_type', 'enum',
                                 true, false, null,
                                 array(Whups::fieldTypeNames()));
        $v->setDefault('text');
        $v->setAction(Horde_Form_Action::factory(
                          'whups_reload',
                          array('formname' => 'addattributedescform_reload')));

        $type = $vars->get('attribute_type');
        if (empty($type)) {
            $type = 'text';
        }
        foreach (Whups::fieldTypeParams($type) as $param => $info) {
            $this->addVariable($info['label'],
                               'attribute_params[' . $param . ']',
                               $info['type'], false);
        }
    }

}

class EditAttributeDescStep1Form extends Horde_Form {

    function EditAttributeDescStep1Form(&$vars)
    {
        parent::Horde_Form($vars, _("Edit or Delete Attributes"));
        $this->setButtons(array(_("Edit Attribute"), _("Delete Attribute")));

        $attributes = $GLOBALS['whups_driver']->getAttributesForType(
            $vars->get('type'));
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
        $this->addVariable(_("Attribute Name"), 'attribute', $stype, true,
                           false, null, $type_params);
    }

}

class EditAttributeDescStep2Form extends Horde_Form {

    function EditAttributeDescStep2Form(&$vars)
    {
        require_once dirname(__FILE__) . '/../Action.php';

        parent::Horde_Form($vars, _("Edit Attribute"));

        $attribute = $vars->get('attribute');
        $info = $GLOBALS['whups_driver']->getAttributeDesc($attribute);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'attribute', 'int', true, true);
        $pname = &$this->addVariable(_("Attribute Name"), 'attribute_name',
                                     'text', true);
        $pname->setDefault($info['attribute_name']);
        $pdesc = &$this->addVariable(_("Attribute Description"),
                                     'attribute_description', 'text', true);
        $pdesc->setDefault($info['attribute_description']);
        $preq = &$this->addVariable(_("Required Attribute?"),
                                    'attribute_required', 'boolean', false);
        $preq->setDefault($info['attribute_required']);

        $ptype = &$this->addVariable(_("Attribute Type"), 'attribute_type',
                                     'enum', true, false, null,
                                     array(Whups::fieldTypeNames()));
        $ptype->setAction(
            Horde_Form_Action::factory(
                'whups_reload',
                array('formname' => 'editattributedescstep2form_reload')));
        $ptype->setDefault($info['attribute_type']);

        $type = $vars->get('attribute_type');
        if (empty($type)) {
            $type = $info['attribute_type'];
        }
        foreach (Whups::fieldTypeParams($type) as $param => $param_info) {
            $pparam = &$this->addVariable($param_info['label'],
                                          'attribute_params[' . $param . ']',
                                          $param_info['type'], false);
            if (isset($info['attribute_params'][$param])) {
                $pparam->setDefault($info['attribute_params'][$param]);
            }
        }
    }

}

class DeleteAttributeDescForm extends Horde_Form {

    function DeleteAttributeDescForm(&$vars)
    {
        parent::Horde_Form($vars, _("Delete Attribute Confirmation"));

        $attribute = $vars->get('attribute');
        $info = $GLOBALS['whups_driver']->getAttributeDesc($attribute);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'attribute', 'int', true, true);
        $pname = &$this->addVariable(_("Attribute Name"), 'attribute_name',
                                     'text', false, true);
        $pname->setDefault($info['attribute_name']);
        $pdesc = &$this->addVariable(_("Attribute Description"),
                                     'attribute_description', 'text', false,
                                     true);
        $pdesc->setDefault($info['attribute_description']);
        $this->addVariable(
            _("Really delete this attribute? This may cause data problems!"),
            'yesno', 'enum', true, false, null,
            array(array(0 => _("No"), 1 => _("Yes"))));
    }

}
