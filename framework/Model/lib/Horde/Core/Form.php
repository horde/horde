<?php
/**
 * Horde_Core_Form Master Class.
 *
 * The Horde_Core_Form:: package provides form rendering, validation, and other
 * functionality for the Horde Application Framework.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Form
 */
class Horde_Core_Form
{
    protected $_name = '';
    protected $_title = '';
    protected $_vars;
    protected $_errors = array();
    protected $_submitted = null;
    protected $_sections = array();
    protected $_open_section = null;
    protected $_currentSection = array();
    protected $_variables = array();
    protected $_hiddenVariables = array();
    protected $_useFormToken = true;
    protected $_autofilled = false;
    protected $_help = false;

    public function __construct($vars, $title = '', $name = null)
    {
        if (is_null($name)) {
            $name = Horde_String::lower(get_class($this));
        }

        $this->_vars = $vars;
        $this->_title = $title;
        $this->_name = $name;
    }

    public function setVars($vars)
    {
        $this->_vars = $vars;
    }

    public function getVars()
    {
        return $this->_vars;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets or gets whether the form should be verified by tokens.
     * Tokens are used to verify that a form is only submitted once.
     *
     * @param boolean $token  If specified, sets whether to use form tokens.
     *
     * @return boolean  Whether form tokens are being used.
     */
    public function useToken($token = null)
    {
        if (!is_null($token)) {
            $this->_useFormToken = $token;
        }
        return $this->_useFormToken;
    }

    /**
     * Get the renderer for this form, either a custom renderer or the
     * standard one.
     *
     * To use a custom form renderer, your form class needs to
     * override this function:
     * <code>
     * function getRenderer()
     * {
     *     return new CustomFormRenderer();
     * }
     * </code>
     *
     * ... where CustomFormRenderer is the classname of the custom
     * renderer class, which should extend Horde_Core_Form_Renderer.
     *
     * @param array $params  A hash of renderer-specific parameters.
     *
     * @return object Horde_Core_Form_Renderer  The form renderer.
     */
    function getRenderer($params = array())
    {
        return new Horde_Core_Form_Renderer_Xhtml($params);
    }

    function getType($type, $params = array())
    {
        $type_class = 'Horde_Core_Form_Type_' . $type;
        if (!class_exists($type_class)) {
            throw new Horde_Exception(sprintf('Nonexistant class "%s" for field type "%s"', $type_class, $type));
        }
        $type_ob = new $type_class();
        call_user_func_array(array(&$type_ob, 'init'), $params);
        return $type_ob;
    }

    public function setSection($section = '', $desc = '', $image = '', $expanded = true)
    {
        $this->_currentSection = $section;
        if (!count($this->_sections) && !$this->getOpenSection()) {
            $this->setOpenSection($section);
        }
        $this->_sections[$section]['desc'] = $desc;
        $this->_sections[$section]['expanded'] = $expanded;
        $this->_sections[$section]['image'] = $image;
    }

    public function getSections()
    {
        return $this->_sections;
    }

    public function getSectionDesc($section)
    {
        return $this->_sections[$section]['desc'];
    }

    public function getSectionImage($section)
    {
        return $this->_sections[$section]['image'];
    }

    public function setOpenSection($section)
    {
        $this->_vars->set('__formOpenSection', $section);
    }

    public function getOpenSection()
    {
        return $this->_vars->get('__formOpenSection');
    }

    public function getSectionExpandedState($section, $boolean = false)
    {
        if ($boolean) {
            /* Only the boolean value is required. */
            return $this->_sections[$section]['expanded'];
        }

        /* Need to return the values for use in styles. */
        if ($this->_sections[$section]['expanded']) {
            return 'block';
        } else {
            return 'none';
        }
    }

    public function add($varName, $type, $humanName, $required, $readonly = false, $description = null, $params = array())
    {
        return $this->addVariable($humanName, $varName, $type, $required, $readonly, $description, $params);
    }

    /**
     * TODO
     */
    public function addVariable($humanName, $varName, $type, $required,
                                $readonly = false, $description = null,
                                $params = array())
    {
        return $this->insertVariableBefore(null, $humanName, $varName, $type,
                                           $required, $readonly, $description,
                                           $params);
    }

    /**
     * TODO
     */
    public function insertVariableBefore($before, $humanName, $varName, $type,
                                         $required, $readonly = false,
                                         $description = null, $params = array())
    {
        $type = $this->getType($type, $params);
        $var = new Horde_Core_Form_Variable($humanName, $varName, $type,
                                            $required, $readonly, $description);

        /* Set the form object reference in the var. */
        $var->setFormOb($this);

        if ($var->getType() instanceof Horde_Core_Form_Type_Enum &&
            count($var->getValues()) == 1) {
            $vals = array_keys($var->getValues());
            $this->_vars->add($var->varName, $vals[0]);
            $var->_autofilled = true;
        }
        if (empty($this->_currentSection)) {
            $this->_currentSection = '__base';
        }

        if (is_null($before)) {
            $this->_variables[$this->_currentSection][] = &$var;
        } else {
            $num = 0;
            while (isset($this->_variables[$this->_currentSection][$num]) &&
                   $this->_variables[$this->_currentSection][$num]->getVarName() != $before) {
                $num++;
            }
            if (!isset($this->_variables[$this->_currentSection][$num])) {
                $this->_variables[$this->_currentSection][] = &$var;
            } else {
                $this->_variables[$this->_currentSection] = array_merge(
                    array_slice($this->_variables[$this->_currentSection], 0, $num),
                    array(&$var),
                    array_slice($this->_variables[$this->_currentSection], $num));
            }
        }

        return $var;
    }

    /**
     * Removes a variable from the form.
     *
     * As only variables can be passed by reference, you need to call this
     * method this way if want to pass a variable name:
     * <code>
     * $form->removeVariable($var = 'varname');
     * </code>
     *
     * @param Horde_Core_Form_Variable|string $var  Either the variable's name or
     *                                         the variable to remove from the
     *                                         form.
     *
     * @return boolean  True if the variable was found (and deleted).
     */
    public function removeVariable(&$var)
    {
        foreach (array_keys($this->_variables) as $section) {
            foreach (array_keys($this->_variables[$section]) as $i) {
                if ((is_a($var, 'Horde_Core_Form_Variable') && $this->_variables[$section][$i] === $var) ||
                    ($this->_variables[$section][$i]->getVarName() == $var)) {
                    // Slice out the variable to be removed.
                    $this->_variables[$this->_currentSection] = array_merge(
                        array_slice($this->_variables[$this->_currentSection], 0, $i),
                        array_slice($this->_variables[$this->_currentSection], $i + 1));

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * TODO
     */
    public function addHidden($varName, $type, $required, $params = array())
    {
        $type = $this->getType($type, $params);
        $var = new Horde_Core_Form_Variable('', $varName, $type, $required);
        $var->hide();
        $this->_hiddenVariables[] = &$var;
        return $var;
    }

    public function getVariables($flat = true, $withHidden = false)
    {
        if ($flat) {
            $vars = array();
            foreach ($this->_variables as $section) {
                foreach ($section as $var) {
                    $vars[] = $var;
                }
            }
            if ($withHidden) {
                foreach ($this->_hiddenVariables as $var) {
                    $vars[] = $var;
                }
            }
            return $vars;
        } else {
            return $this->_variables;
        }
    }

    public function getHiddenVariables()
    {
        return $this->_hiddenVariables;
    }

    /**
     * Preserve the variables/values from another Horde_Core_Form object.
     */
    public function preserve(Horde_Core_Form $form)
    {
        /* OLD IMPLEMENTATION
        if ($this->_useFormToken) {
            $this->_preserveVarByPost($this->_name . '_formToken', Horde_Token::generateId($this->_name));
        }

        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $varname = $var->getVarName();

            switch (get_class($var->getType()) {
            case 'passwordconfirm':
            case 'emailconfirm':
                $this->preserveVarByPost($this->_vars, $varname . '[original]');
                $this->preserveVarByPost($this->_vars, $varname . '[confirm]');
                break;

            case 'monthyear':
                $this->preserveVarByPost($this->_vars, $varname . '[month]');
                $this->preserveVarByPost($this->_vars, $varname . '[year]');
                break;

            case 'monthdayyear':
                $this->preserveVarByPost($this->_vars, $varname . '[month]');
                $this->preserveVarByPost($this->_vars, $varname . '[day]');
                $this->preserveVarByPost($this->_vars, $varname . '[year]');
                break;
            }

            $this->preserveVarByPost($this->_vars, $varname);
        }
        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($this->_vars, $var->getVarName());
        }
        */
    }

    /**
     * Does the action of validating the form, checking if it really has been
     * submitted by calling isSubmitted() and if true does any onSubmit()
     * calls for var types in the form. The _submitted var is then rechecked.
     *
     * @param boolean         $canAutofill  Can the form be valid without
     *                                      being submitted?
     *
     * @return boolean  True if the form is valid.
     */
    public function validate($canAutoFill = false)
    {
        /* Get submitted status. */
        if ($this->isSubmitted() || $canAutoFill) {
            /* Form was submitted or can autofill; check for any variable
             * types' onSubmit(). */
            $this->onSubmit($this->_vars);

            /* Recheck submitted status. */
            if (!$this->isSubmitted() && !$canAutoFill) {
                return false;
            }
        } else {
            /* Form has not been submitted; return false. */
            return false;
        }

        $message = '';
        $this->_autofilled = true;

        if ($this->_useFormToken) {
            $tokenSource = $GLOBALS['injector']->getInstance('Horde_Token');
            if (!$tokenSource->verify($this->_vars->get($this->_name . '_formToken'))) {
                $this->_errors['_formToken'] = Horde_Model_Translation::t("This form has already been processed.");
            }
        }

        foreach ($this->getVariables() as $var) {
            $this->_autofilled = $var->_autofilled && $this->_autofilled;
            if (!$var->validate($this->_vars, $message)) {
                $this->_errors[$var->getVarName()] = $message;
            }
        }

        if ($this->_autofilled) {
            unset($this->_errors['_formToken']);
        }

        foreach ($this->_hiddenVariables as $var) {
            if (!$var->validate($this->_vars, $message)) {
                $this->_errors[$var->getVarName()] = $message;
            }
        }

        return $this->isValid();
    }

    public function clearValidation()
    {
        $this->_errors = array();
    }

    public function getError($var)
    {
        if (is_a($var, 'Horde_Core_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        return isset($this->_errors[$name]) ? $this->_errors[$name] : null;
    }

    public function setError($var, $message)
    {
        if (is_a($var, 'Horde_Core_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        $this->_errors[$name] = $message;
    }

    public function clearError($var)
    {
        if (is_a($var, 'Horde_Core_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        unset($this->_errors[$name]);
    }

    public function isValid()
    {
        return ($this->_autofilled || !count($this->_errors));
    }

    public function execute()
    {
        throw new Horde_Core_Form_Exception('Subclass must overide execute()');
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    public function getInfo(&$info)
    {
        $this->_getInfoFromVariables($this->getVariables(), $info);
        $this->_getInfoFromVariables($this->_hiddenVariables, $info);
    }

    /**
     * Fetch the field values from a given array of variables.
     *
     * @access private
     *
     * @param array  $variables  An array of Horde_Core_Form_Variable objects to
     *                           fetch from.
     * @param array  $info       The array to be filled with the submitted
     *                           field values.
     */
    protected function _getInfoFromVariables($variables, &$info)
    {
        foreach ($variables as $var) {
            if ($var->isArrayVal()) {
                $var->getInfo($this->_vars, $values);
                if (is_array($values)) {
                    $varName = str_replace('[]', '', $var->getVarName());
                    foreach ($values as $i => $val) {
                        $info[$i][$varName] = $val;
                    }
                }
            } else {
                if (Horde_Array::getArrayParts($var->getVarName(), $base, $keys)) {
                    if (!isset($info[$base])) {
                        $info[$base] = array();
                    }
                    $pointer = &$info[$base];
                    while (count($keys)) {
                        $key = array_shift($keys);
                        if (!isset($pointer[$key])) {
                            $pointer[$key] = array();
                        }
                        $pointer = &$pointer[$key];
                    }
                    $var->getInfo($this->_vars, $pointer);
                } else {
                    $var->getInfo($this->_vars, $info[$var->getVarName()]);
                }
            }
        }
    }

    public function hasHelp()
    {
        return $this->_help;
    }

    /**
     * Determines if this form has been submitted or not. If the class
     * var _submitted is null then it will check for the presence of
     * the formname in the form variables.
     *
     * Other events can explicitly set the _submitted variable to
     * false to indicate a form submit but not for actual posting of
     * data (eg. onChange events to update the display of fields).
     *
     * @return boolean  True or false indicating if the form has been
     *                  submitted.
     */
    public function isSubmitted()
    {
        if (is_null($this->_submitted)) {
            if ($this->_vars->get('formname') == $this->getName()) {
                $this->_submitted = true;
            } else {
                $this->_submitted = false;
            }
        }

        return $this->_submitted;
    }

    /**
     * Checks if there is anything to do on the submission of the form by
     * looping through each variable's onSubmit() function.
     */
    public function onSubmit()
    {
        /* Loop through all vars and check if there's anything to do on
         * submit. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $var->type->onSubmit($var, $this->_vars);
            /* If changes to var being tracked don't register the form as
             * submitted if old value and new value differ. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                if (!is_null($this->_vars->get('formname')) &&
                    $this->_vars->get($varname) != $this->_vars->get('__old_' . $varname)) {
                    $this->_submitted = false;
                }
            }
        }
    }

    /**
     * Explicitly sets the state of the form submit.
     *
     * An event can override the automatic determination of the submit state
     * in the isSubmitted() function.
     *
     * @param boolean $state  Whether to set the state of the form as being
     *                        submitted.
     */
    public function setSubmitted($state = true)
    {
        $this->_submitted = $state;
    }
}
