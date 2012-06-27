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
class Sesha_Form_Property extends Horde_Form
{
    public function __construct($vars)
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

    /**
     * Validates the form, checking if it really has been submitted by calling
     * isSubmitted() and if true does any onSubmit() calls for variable types
     * in the form. The _submitted variable is then rechecked.
     *
     * @param Variables $vars       A Variables instance, optional since Horde
     *                              3.2.
     * @param boolean $canAutofill  Can the form be valid without being
     *                              submitted?
     *
     * @return boolean  True if the form is valid.
     */

    public function validate($vars, $canAutoFill = false)
    {
        $this->_addParameters($vars);
        return parent::validate($vars, $canAutoFill);
    }

    /**
     * Renders the form for editing.
     *
     * @param Horde_Form_Renderer $renderer  A renderer instance, optional
     *                                       since Horde 3.2.
     * @param Variables $vars                A Variables instance, optional
     *                                       since Horde 3.2.
     * @param string $action                 The form action (url).
     * @param string $method                 The form method, usually either
     *                                       'get' or 'post'.
     * @param string $enctype                The form encoding type. Determined
     *                                       automatically if null.
     * @param boolean $focus                 Focus the first form field?
     */

    public function renderActive($renderer, $vars, $action, $method = 'get', $enctype = null, $focus = true)
    {
        if ($vars->get('old_datatype') === null) {
            $this->_addParameters($vars);
        }
        parent::renderActive($renderer, $vars, $action, $method, $enctype, $focus);
    }

    protected function _addParameters($vars)
    {
        $dataType = $vars->get('datatype');
        $className = $this->_buildTypeClassname($dataType);
        if (empty($dataType)) {
            // Noop.
        } elseif (!$className) {
            $GLOBALS['notification']->push(sprintf(_("The form field type \"%s\" doesn't exist."), $dataType), 'horde.error');
        } else {
            $params = call_user_func(array($className, 'about'));
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
                    $vars->set('old_datatype', $dataType);
                }
            }
        }
    }

   /**
    * Helper method to build either h3 style class names as seen in Horde_Form_Type_ccc
    * or autoloadable class names used in Sesha
    *
    * @param string $dataType  The type identifier to turn into a class name
    *
    * @return string  A class name or an empty string
    *
    */

    protected function _buildTypeClassname($dataType)
    {
        if (class_exists('Horde_Form_Type_' . $dataType)) {
            return 'Horde_Form_Type_' . $dataType;
        } elseif (class_exists('Sesha_Form_Type_' . ucfirst($dataType))) {
            return 'Sesha_Form_Type_' . ucfirst($dataType);
        } else {
            return '';
        }
    }
}
