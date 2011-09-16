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
class Sesha_Forms_Property extends Horde_Form
{
    function __construct($vars)
    {
        parent::__construct($vars);

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

        $action = Horde_Form_Action::factory('submit');
        $v = $this->addVariable(_("Data Type"), 'datatype', 'enum', true, false, null, array($types, true));
        $v->setAction($action);
        $v->setOption('trackchange', true);

        $this->addVariable(_("Unit"), 'unit', 'text', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false);
        $this->addVariable(_("Sort Weight"), 'priority', 'enum', false, false, _("When properties are displayed, they will be shown in weight order from highest to lowest"), array($priorities));
    }

    function validate($vars)
    {
        $this->_addParameters($vars);
        return parent::validate($vars);
    }

    function renderActive($renderer, &$vars, $action, $method = 'get', $enctype = null, $focus = true)
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

