<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Bo Daley <bo@darkwork.net>
 * @package Sesha
 */
class PropertyForm extends Horde_Form
{
    function PropertyForm(&$vars)
    {
        parent::Horde_Form($vars);

        $this->appendButtons(_("Save Property"));

        $types = array();
        $datatypes = $GLOBALS['conf']['datatypes']['types'];
        foreach ($datatypes as $d) {
            $types[$d] = $d;
        }

        $priorities = array();
        for ($i = 0; $i < 100; $i++) {
            $priorities[] = $i;
        }

        $this->addHidden('', 'actionID', 'text', false, false, null);
        $this->addHidden('', 'property_id', 'text', false, false, null);
        $this->addVariable(_("Property Name"), 'property', 'text', true);

        require_once 'Horde/Form/Action.php';
        $action = Horde_Form_Action::factory('submit');
        $v = &$this->addVariable(_("Data Type"), 'datatype', 'enum', true, false, null, array($types, true));
        $v->setAction($action);
        $v->setOption('trackchange', true);

        $this->addVariable(_("Unit"), 'unit', 'text', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false);
        $this->addVariable(_("Sort Weight"), 'priority', 'enum', false, false, _("When properties are displayed, they will be shown in weight order from highest to lowest"), array($priorities));
    }

    function validate(&$vars)
    {
        $this->_addParameters($vars);
        return parent::validate($vars);
    }

    function renderActive(&$renderer, &$vars, $action, $method = 'get', $enctype = null, $focus = true)
    {
        if ($vars->get('old_datatype') === null) {
            $this->_addParameters($vars);
        }
        parent::renderActive($renderer, $vars, $action, $method, $enctype, $focus);
    }

    function _addParameters(&$vars)
    {
        $data_type = $vars->get('datatype');
        if (empty($data_type)) {
            // Noop.
        } elseif (!class_exists('Horde_Form_Type_' . $data_type)) {
            $GLOBALS['notification']->push(sprintf(_("The form field type \"%s\" doesn't exist."), $data_type), 'horde.error');
        } else {
            $params = call_user_func(array('Horde_Form_Type_' . $data_type, 'about'));
            if (isset($params['params'])) {
                foreach ($params['params'] as $name => $param) {
                    $field_id = 'parameters[' . $name . ']';
                    $param['required'] = isset($param['required'])
                        ? $param['required']
                        : null;
                    $param['readonly'] = isset($param['readonly'])
                        ? $param['readonly']
                        : null;
                    $param['desc'] = isset($param['desc'])
                        ? $param['desc']
                        : null;
                    $this->insertVariableBefore('unit', $param['label'],
                                                $field_id, $param['type'],
                                                $param['required'],
                                                $param['readonly'],
                                                $param['desc']);
                    $vars->set('old_datatype', $data_type);
                }
            }
        }
    }
}

class PropertyListForm extends Horde_Form
{
    function PropertyListForm(&$vars)
    {
        parent::Horde_Form($vars);
        $this->setButtons(array(_("Edit Property"), _("Delete Property")));
        $properties = $GLOBALS['backend']->getProperties();
        $params = array();
        foreach ($properties as $property_id => $property) {
            $params[$property_id] = $property['property'];
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

class PropertyDeleteForm extends Horde_Form
{
    function PropertyDeleteForm(&$vars)
    {
        parent::Horde_Form($vars);

        $this->appendButtons(_("Delete Property"));
        $params = array('yes' => _("Yes"),
                        'no' => _("No"));
        $desc = _("Really delete this property?");

        $this->addHidden('', 'actionID', 'text', false, false, null, array('delete_property'));
        $this->addHidden('', 'property_id', 'text', false, false, null);
        $this->addVariable(_("Confirm"), 'confirm', 'enum', true, false, $desc, array($params));
    }

}
