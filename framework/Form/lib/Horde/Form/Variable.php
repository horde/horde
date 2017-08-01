<?php
/**
 * Copyright 2001-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */

/**
 * This class represents a single form variable that may be rendered as one or
 * more form fields.
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @category  Horde
 * @copyright 2001-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 */
class Horde_Form_Variable {

    /**
     * The form instance this variable is assigned to.
     *
     * @var Horde_Form
     */
    var $form;

    /**
     * A short description of this variable's purpose.
     *
     * @var string
     */
    var $humanName;

    /**
     * The internally used name.
     *
     * @var string
     */
    var $varName;

    /**
     * A {@link Horde_Form_Type} instance.
     *
     * @var Horde_Form_Type
     */
    var $type;

    /**
     * Whether this is a required variable.
     *
     * @var boolean
     */
    var $required;

    /**
     * Whether this is a readonly variable.
     *
     * @var boolean
     */
    var $readonly;

    /**
     * A long description of the variable's purpose, special instructions, etc.
     *
     * @var string
     */
    var $description;

    /**
     * The variable help text.
     *
     * @var string
     */
    var $help;

    /**
     * Whether this is an array variable.
     *
     * @var boolean
     */
    var $_arrayVal;

    /**
     * The default value.
     *
     * @var mixed
     */
    var $_defValue = null;

    /**
     * A {@link Horde_Form_Action} instance.
     *
     * @var Horde_Form_Action
     */
    var $_action;

    /**
     * Whether this variable is disabled.
     *
     * @var boolean
     */
    var $_disabled = false;

    /**
     * TODO
     *
     * @var boolean
     */
    var $_autofilled = false;

    /**
     * Whether this is a hidden variable.
     *
     * @var boolean
     */
    var $_hidden = false;

    /**
     * TODO
     *
     * @var array
     */
    var $_options = array();

    /**
     * Variable constructor.
     *
     * @param string $humanName      A short description of the variable's
     *                               purpose.
     * @param string $varName        The internally used name.
     * @param Horde_Form_Type $type  A {@link Horde_Form_Type} instance.
     * @param boolean $required      Whether this is a required variable.
     * @param boolean $readonly      Whether this is a readonly variable.
     * @param string $description    A long description of the variable's
     *                               purpose, special instructions, etc.
     */
    function Horde_Form_Variable($humanName, $varName, $type, $required,
                                 $readonly = false, $description = null)
    {
        $this->humanName   = $humanName;
        $this->varName     = $varName;
        $this->type        = $type;
        $this->required    = $required;
        $this->readonly    = $readonly;
        $this->description = $description;
        $this->_arrayVal   = (strpos($varName, '[]') !== false);
    }

    /**
     * Assign this variable to the specified form.
     *
     * @param Horde_Form $form  The form instance to assign this variable to.
     */
    function setFormOb(&$form)
    {
        $this->form = &$form;
    }

    /**
     * Sets a default value for this variable.
     *
     * @param mixed $value  A variable value.
     */
    function setDefault($value)
    {
        $this->_defValue = $value;
    }

    /**
     * Returns this variable's default value.
     *
     * @return mixed  This variable's default value.
     */
    function getDefault()
    {
        return $this->_defValue;
    }

    /**
     * Assigns an action to this variable.
     *
     * Example:
     * <code>
     * $v = $form->addVariable('My Variable', 'var1', 'text', false);
     * $v->setAction(Horde_Form_Action::factory('submit'));
     * </code>
     *
     * @param Horde_Form_Action $action  A {@link Horde_Form_Action} instance.
     */
    function setAction($action)
    {
        $this->_action = $action;
    }

    /**
     * Returns whether this variable has an attached action.
     *
     * @return boolean  True if this variable has an attached action.
     */
    function hasAction()
    {
        return !is_null($this->_action);
    }

    /**
     * Makes this a hidden variable.
     */
    function hide()
    {
        $this->_hidden = true;
    }

    /**
     * Returns whether this is a hidden variable.
     *
     * @return boolean  True if this a hidden variable.
     */
    function isHidden()
    {
        return $this->_hidden;
    }

    /**
     * Disables this variable.
     */
    function disable()
    {
        $this->_disabled = true;
    }

    /**
     * Returns whether this variable is disabled.
     *
     * @return boolean  True if this variable is disabled.
     */
    function isDisabled()
    {
        return $this->_disabled;
    }

    /**
     * Return the short description of this variable.
     *
     * @return string  A short description
     */
    function getHumanName()
    {
        return $this->humanName;
    }

    /**
     * Returns the internally used variable name.
     *
     * @return string  This variable's internal name.
     */
    function getVarName()
    {
        return $this->varName;
    }

    /**
     * Returns this variable's type.
     *
     * @return Horde_Form_Type  This variable's {@link Horde_Form_Type}
     *                          instance.
     */
    function &getType()
    {
        return $this->type;
    }

    /**
     * Returns the name of this variable's type.
     *
     * @return string  This variable's {@link Horde_Form_Type} name.
     */
    function getTypeName()
    {
        return $this->type->getTypeName();
    }

    /**
     * Returns whether this is a required variable.
     *
     * @return boolean  True if this is a required variable.
     */
    function isRequired()
    {
        return $this->required;
    }

    /**
     * Returns whether this is a readonly variable.
     *
     * @return boolean  True if this a readonly variable.
     */
    function isReadonly()
    {
        return $this->readonly;
    }

    /**
     * Returns the possible values of this variable.
     *
     * @return array  The possible values of this variable or null.
     */
    function getValues()
    {
        return $this->type->getValues();
    }

    /**
     * Returns whether this variable has a long description.
     *
     * @return boolean  True if this variable has a long description.
     */
    function hasDescription()
    {
        return !empty($this->description);
    }

    /**
     * Returns this variable's long description.
     *
     * @return string  This variable's long description.
     */
    function getDescription()
    {
        return $this->description;
    }

    /**
     * Returns whether this is an array variable.
     *
     * @return boolean  True if this an array variable.
     */
    function isArrayVal()
    {
        return $this->_arrayVal;
    }

    /**
     * Returns whether this variable is to upload a file.
     *
     * @return boolean  True if variable is to upload a file.
     */
    function isUpload()
    {
        return ($this->type->getTypeName() == 'file');
    }

    /**
     * Assigns a help text to this variable.
     *
     * @param string $help  The variable help text.
     */
    function setHelp($help)
    {
        $this->form->_help = true;
        $this->help = $help;
    }

    /**
     * Returns whether this variable has some help text assigned.
     *
     * @return boolean  True if this variable has a help text.
     */
    function hasHelp()
    {
        return !empty($this->help);
    }

    /**
     * Returns the help text of this variable.
     *
     * @return string  This variable's help text.
     */
    function getHelp()
    {
        return $this->help;
    }

    /**
     * Sets a variable option.
     *
     * @param string $option  The option name.
     * @param mixed $val      The option's value.
     */
    function setOption($option, $val)
    {
        $this->_options[$option] = $val;
    }

    /**
     * Returns a variable option's value.
     *
     * @param string $option  The option name.
     *
     * @return mixed          The option's value.
     */
    function getOption($option)
    {
        return isset($this->_options[$option]) ? $this->_options[$option] : null;
    }

    /**
     * Processes the submitted value of this variable according to the rules of
     * the variable type.
     *
     * @param Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     * @param mixed $info      A variable passed by reference that will be
     *                         assigned the processed value of the submitted
     *                         variable value.
     *
     * @return mixed  Depending on the variable type.
     */
    function getInfo(&$vars, &$info)
    {
        return $this->type->getInfo($vars, $this, $info);
    }

    /**
     * Returns whether this variable if it had the "trackchange" option set
     * has actually been changed.
     *
     * @param Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     *
     * @return boolean  Null if this variable doesn't have the "trackchange"
     *                  option set or the form wasn't submitted yet. A boolean
     *                  indicating whether the variable was changed otherwise.
     */
    function wasChanged(&$vars)
    {
        if (!$this->getOption('trackchange')) {
            return null;
        }
        $old = $vars->get('__old_' . $this->getVarName());
        if (is_null($old)) {
            return null;
        }
        return $old != $vars->get($this->getVarName());
    }

    /**
     * Validates this variable.
     *
     * @param Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     * @param string $message  A variable passed by reference that will be
     *                         assigned a descriptive error message if
     *                         validation failed.
     *
     * @return boolean  True if the variable validated.
     */
    function validate(&$vars, &$message)
    {
        if ($this->_arrayVal) {
            $vals = $this->getValue($vars);
            if (!is_array($vals)) {
                if ($this->required) {
                    $message = Horde_Form_Translation::t("This field is required.");
                    return false;
                } else {
                    return true;
                }
            }
            foreach ($vals as $i => $value) {
                if ($value === null && $this->required) {
                    $message = Horde_Form_Translation::t("This field is required.");
                    return false;
                } else {
                    if (!$this->type->isValid($this, $vars, $value, $message)) {
                        return false;
                    }
                }
            }
        } else {
            $value = $this->getValue($vars);
            return $this->type->isValid($this, $vars, $value, $message);
        }

        return true;
    }

    /**
     * Returns the submitted or default value of this variable.
     * If an action is attached to this variable, the value will get passed to
     * the action object.
     *
     * @param Variables $vars  The {@link Variables} instance of the submitted
     *                         form.
     * @param integer $index   If the variable is an array variable, this
     *                         specifies the array element to return.
     *
     * @return mixed  The variable or element value.
     */
    function getValue(&$vars, $index = null)
    {
        if ($this->_arrayVal) {
            $name = str_replace('[]', '', $this->varName);
        } else {
            $name = $this->varName;
        }

        $value = $vars->get($name);
        $wasset = $vars->exists($name);
        if (!$wasset) {
            $value = $this->getDefault();
        }

        if ($this->_arrayVal && !is_null($index)) {
            if (!$wasset && !is_array($value)) {
                $return = $value;
            } else {
                $return = isset($value[$index]) ? $value[$index] : null;
            }
        } else {
            $return = $value;
        }

        if ($this->hasAction()) {
            $this->_action->setValues($vars, $return, $this->_arrayVal);
        }

        return $return;
    }

}
