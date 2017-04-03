<?php
/**
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Robert E. Coyle <robertecoyle@hotmail.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Form
 */

require_once 'Horde/Form/Type.php';

/**
 * Horde_Form Master Class.
 *
 * @author    Robert E. Coyle <robertecoyle@hotmail.com>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2001-2007 Robert E. Coyle
 * @copyright 2001-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Form
 */
class Horde_Form {

    protected $_name = '';
    protected $_title = '';
    protected $_extra = '';
    protected $_vars;
    protected $_submit = array();
    protected $_reset = false;
    protected $_errors = array();
    protected $_submitted = null;
    public $_sections = array();
    protected $_open_section = null;
    protected $_currentSection = array();
    protected $_variables = array();
    protected $_hiddenVariables = array();
    protected $_useFormToken = true;
    protected $_autofilled = false;
    protected $_enctype = null;
    public $_help = false;

    function __construct($vars, $title = '', $name = null)
    {
        if (empty($name)) {
            $name = Horde_String::lower(get_class($this));
        }

        $this->_vars = &$vars;
        $this->_title = $title;
        $this->_name = $name;
    }

    function singleton($form, &$vars, $title = '', $name = null)
    {
        static $instances = array();

        $signature = serialize(array($form, $vars, $title, $name));
        if (!isset($instances[$signature])) {
            if (class_exists($form)) {
                $instances[$signature] = new $form($vars, $title, $name);
            } else {
                $instances[$signature] = new Horde_Form($vars, $title, $name);
            }
        }

        return $instances[$signature];
    }

    function setVars(&$vars)
    {
        $this->_vars = &$vars;
    }

    function getVars()
    {
        return $this->_vars;
    }

    function getTitle()
    {
        return $this->_title;
    }

    function setTitle($title)
    {
        $this->_title = $title;
    }

    function getExtra()
    {
        return $this->_extra;
    }

    function setExtra($extra)
    {
        $this->_extra = $extra;
    }

    function getName()
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
    function useToken($token = null)
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
     * renderer class, which should extend Horde_Form_Renderer.
     *
     * @param array $params  A hash of renderer-specific parameters.
     *
     * @return object Horde_Form_Renderer  The form renderer.
     */
    function getRenderer($params = array())
    {
        $renderer = new Horde_Form_Renderer($params);
        return $renderer;
    }

    /**
     * @throws Horde_Exception
     */
    function getType($type, $params = array())
    {
        if (strpos($type, ':') !== false) {
            list($app, $type) = explode(':', $type);
            $type_class = $app . '_Form_Type_' . $type;
        } else {
            $type_class = 'Horde_Form_Type_' . $type;
        }
        if (!class_exists($type_class)) {
            throw new Horde_Exception(sprintf('Nonexistant class "%s" for field type "%s"', $type_class, $type));
        }
        $type_ob = new $type_class();
        if (!$params) {
            $params = array();
        }
        call_user_func_array(array($type_ob, 'init'), $params);
        return $type_ob;
    }

    function setSection($section = '', $desc = '', $image = '', $expanded = true)
    {
        $this->_currentSection = $section;
        if (!count($this->_sections) && !$this->getOpenSection()) {
            $this->setOpenSection($section);
        }
        $this->_sections[$section]['desc'] = $desc;
        $this->_sections[$section]['expanded'] = $expanded;
        $this->_sections[$section]['image'] = $image;
    }

    function getSectionDesc($section)
    {
        return $this->_sections[$section]['desc'];
    }

    function getSectionImage($section)
    {
        return $this->_sections[$section]['image'];
    }

    function setOpenSection($section)
    {
        $this->_vars->set('__formOpenSection', $section);
    }

    function getOpenSection()
    {
        return $this->_vars->get('__formOpenSection');
    }

    function getSectionExpandedState($section, $boolean = false)
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

    /**
     * TODO
     */
    function addVariable($humanName, $varName, $type, $required,
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
    function insertVariableBefore($before, $humanName, $varName, $type,
                                  $required, $readonly = false,
                                  $description = null, $params = array())
    {
        $type = $this->getType($type, $params);
        $var = new Horde_Form_Variable($humanName, $varName, $type,
                                       $required, $readonly, $description);

        /* Set the form object reference in the var. */
        $var->setFormOb($this);

        if ($var->getTypeName() == 'enum' &&
            !strlen($type->getPrompt()) &&
            count($var->getValues()) == 1) {
            $vals = array_keys($var->getValues());
            $this->_vars->add($var->varName, $vals[0]);
            $var->_autofilled = true;
        } elseif ($var->getTypeName() == 'file' ||
                  $var->getTypeName() == 'image') {
            $this->_enctype = 'multipart/form-data';
        }
        if (empty($this->_currentSection) && $this->_currentSection !== 0) {
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
     * @param Horde_Form_Variable|string $var  Either the variable's name or
     *                                         the variable to remove from the
     *                                         form.
     *
     * @return boolean  True if the variable was found (and deleted).
     */
    function removeVariable(&$var)
    {
        foreach (array_keys($this->_variables) as $section) {
            foreach (array_keys($this->_variables[$section]) as $i) {
                if ((is_a($var, 'Horde_Form_Variable') && $this->_variables[$section][$i] === $var) ||
                    ($this->_variables[$section][$i]->getVarName() == $var)) {
                    // Slice out the variable to be removed.
                    $this->_variables[$section] = array_merge(
                        array_slice($this->_variables[$section], 0, $i),
                        array_slice($this->_variables[$section], $i + 1));

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * TODO
     *
     * @todo Remove $readonly parameter. Hidden fields are read-only by
     *       definition.
     */
    function addHidden($humanName, $varName, $type, $required,
                       $readonly = false, $description = null,
                       $params = array())
    {
        $type = $this->getType($type, $params);
        $var = new Horde_Form_Variable($humanName, $varName, $type,
                                       $required, $readonly, $description);
        $var->hide();
        $this->_hiddenVariables[] = &$var;
        return $var;
    }

    function getVariables($flat = true, $withHidden = false)
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

    function setButtons($submit, $reset = false)
    {
        if ($submit === true || is_null($submit) || empty($submit)) {
            /* Default to 'Submit'. */
            $submit = array(Horde_Form_Translation::t("Submit"));
        } elseif (!is_array($submit)) {
            /* Default to array if not passed. */
            $submit = array($submit);
        }
        /* Only if $reset is strictly true insert default 'Reset'. */
        if ($reset === true) {
            $reset = Horde_Form_Translation::t("Reset");
        }

        $this->_submit = $submit;
        $this->_reset = $reset;
    }

    function appendButtons($submit)
    {
        if (!is_array($submit)) {
            $submit = array($submit);
        }

        $this->_submit = array_merge($this->_submit, $submit);
    }

    function preserveVarByPost(&$vars, $varname, $alt_varname = '')
    {
        $value = $vars->getExists($varname, $wasset);

        /* If an alternate name is given under which to preserve use that. */
        if ($alt_varname) {
            $varname = $alt_varname;
        }

        if ($wasset) {
            $this->_preserveVarByPost($varname, $value);
        }
    }

    /**
     * @access private
     */
    function _preserveVarByPost($varname, $value)
    {
        if (is_array($value)) {
            foreach ($value as $id => $val) {
                $this->_preserveVarByPost($varname . '[' . $id . ']', $val);
            }
        } else {
            $varname = htmlspecialchars($varname);
            $value = htmlspecialchars($value);
            printf('<input type="hidden" name="%s" value="%s" />' . "\n",
                   $varname,
                   $value);
        }
    }

    function open(&$renderer, &$vars, $action, $method = 'get', $enctype = null)
    {
        if (is_null($enctype) && !is_null($this->_enctype)) {
            $enctype = $this->_enctype;
        }
        $renderer->open($action, $method, $this->_name, $enctype);

        if (!empty($this->_name)) {
            $this->_preserveVarByPost('formname', $this->_name);
        }

        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        /* Loop through vars and check for any special cases to preserve. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            /* Preserve value if change has to be tracked. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                $this->preserveVarByPost($vars, $varname, '__old_' . $varname);
            }
        }

        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }
    }

    function close($renderer)
    {
        $renderer->close();
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
    function renderActive($renderer = null, $vars = null, $action = '',
                          $method = 'get', $enctype = null, $focus = true)
    {
        if (is_null($renderer)) {
            $renderer = $this->getRenderer();
        }
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        if (is_null($enctype) && !is_null($this->_enctype)) {
            $enctype = $this->_enctype;
        }
        $renderer->open($action, $method, $this->getName(), $enctype);
        $renderer->listFormVars($this);

        if (!empty($this->_name)) {
            $this->_preserveVarByPost('formname', $this->_name);
        }

        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        if (count($this->_sections)) {
            $this->_preserveVarByPost('__formOpenSection', $this->getOpenSection());
        }

        /* Loop through vars and check for any special cases to
         * preserve. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            /* Preserve value if change has to be tracked. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                $this->preserveVarByPost($vars, $varname, '__old_' . $varname);
            }
        }

        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }

        $renderer->beginActive($this->getTitle(), $this->getExtra());
        $renderer->renderFormActive($this, $vars);
        $renderer->submit($this->_submit, $this->_reset);
        $renderer->end();
        $renderer->close($focus);
    }

    /**
     * Renders the form for displaying.
     *
     * @param Horde_Form_Renderer $renderer  A renderer instance, optional
     *                                       since Horde 3.2.
     * @param Variables $vars                A Variables instance, optional
     *                                       since Horde 3.2.
     */
    function renderInactive($renderer = null, $vars = null)
    {
        if (is_null($renderer)) {
            $renderer = $this->getRenderer();
        }
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        $renderer->_name = $this->_name;
        $renderer->beginInactive($this->getTitle(), $this->getExtra());
        $renderer->renderFormInactive($this, $vars);
        $renderer->end();
    }

    function preserve($vars)
    {
        if ($this->_useFormToken) {
            $token = Horde_Token::generateId($this->_name);
            $GLOBALS['session']->set('horde', 'form_secrets/' . $token, true);
            $this->_preserveVarByPost($this->_name . '_formToken', $token);
        }

        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $varname = $var->getVarName();

            /* Save value of individual components. */
            switch ($var->getTypeName()) {
            case 'passwordconfirm':
            case 'emailconfirm':
                $this->preserveVarByPost($vars, $varname . '[original]');
                $this->preserveVarByPost($vars, $varname . '[confirm]');
                break;

            case 'monthyear':
                $this->preserveVarByPost($vars, $varname . '[month]');
                $this->preserveVarByPost($vars, $varname . '[year]');
                break;

            case 'monthdayyear':
                $this->preserveVarByPost($vars, $varname . '[month]');
                $this->preserveVarByPost($vars, $varname . '[day]');
                $this->preserveVarByPost($vars, $varname . '[year]');
                break;
            }

            $this->preserveVarByPost($vars, $varname);
        }
        foreach ($this->_hiddenVariables as $var) {
            $this->preserveVarByPost($vars, $var->getVarName());
        }
    }

    function unsetVars(&$vars)
    {
        foreach ($this->getVariables() as $var) {
            $vars->remove($var->getVarName());
        }
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
    function validate($vars = null, $canAutoFill = false)
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }

        /* Get submitted status. */
        if ($this->isSubmitted() || $canAutoFill) {
            /* Form was submitted or can autofill; check for any variable
             * types' onSubmit(). */
            $this->onSubmit($vars);

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
            try {
                $tokenSource = $GLOBALS['injector']->getInstance('Horde_Token');
                $passedToken = $vars->get($this->_name . '_formToken');
                if (!empty($passedToken) &&
                    !$tokenSource->verify($passedToken)) {
                    $this->_errors['_formToken'] = Horde_Form_Translation::t("This form has already been processed.");
                }
            } catch (Horde_Exception $e) {
            }
            if (!$GLOBALS['session']->get('horde', 'form_secrets/' . $passedToken)) {
                $this->_errors['_formSecret'] = Horde_Form_Translation::t("Required secret is invalid - potentially malicious request.");
            }
        }

        foreach ($this->getVariables() as $var) {
            $this->_autofilled = $var->_autofilled && $this->_autofilled;
            if (!$var->validate($vars, $message)) {
                $this->_errors[$var->getVarName()] = $message;
            }
        }

        if ($this->_autofilled) {
            unset($this->_errors['_formToken']);
        }

        foreach ($this->_hiddenVariables as $var) {
            if (!$var->validate($vars, $message)) {
                $this->_errors[$var->getVarName()] = $message;
            }
        }

        return $this->isValid();
    }

    function clearValidation()
    {
        $this->_errors = array();
    }

    function getErrors()
    {
        return $this->_errors;
    }

    function getError($var)
    {
        if (is_a($var, 'Horde_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        return isset($this->_errors[$name]) ? $this->_errors[$name] : null;
    }

    function setError($var, $message)
    {
        if (is_a($var, 'Horde_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        $this->_errors[$name] = $message;
    }

    function clearError($var)
    {
        if (is_a($var, 'Horde_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        unset($this->_errors[$name]);
    }

    function isValid()
    {
        return ($this->_autofilled || count($this->_errors) == 0);
    }

    function execute()
    {
        Horde::log('Warning: Horde_Form::execute() called, should be overridden', 'DEBUG');
    }

    /**
     * Fetch the field values of the submitted form.
     *
     * @param Variables $vars  A Variables instance, optional since Horde 3.2.
     * @param array $info      Array to be filled with the submitted field
     *                         values.
     */
    function getInfo($vars, &$info)
    {
        if (is_null($vars)) {
            $vars = $this->_vars;
        }
        $this->_getInfoFromVariables($this->getVariables(), $vars, $info);
        $this->_getInfoFromVariables($this->_hiddenVariables, $vars, $info);
    }

    /**
     * Fetch the field values from a given array of variables.
     *
     * @access private
     *
     * @param array  $variables  An array of Horde_Form_Variable objects to
     *                           fetch from.
     * @param object $vars       The Variables object.
     * @param array  $info       The array to be filled with the submitted
     *                           field values.
     */
    function _getInfoFromVariables($variables, &$vars, &$info)
    {
        foreach ($variables as $var) {
            if ($var->isDisabled()) {
                // Disabled fields are not submitted by some browsers, so don't
                // pretend they were.
                continue;
            }
            if ($var->isArrayVal()) {
                $var->getInfo($vars, $values);
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
                    $var->getInfo($vars, $pointer);
                } else {
                    $var->getInfo($vars, $info[$var->getVarName()]);
                }
            }
        }
    }

    function hasHelp()
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
    function isSubmitted()
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
     *
     * @param Horde_Variables $vars
     */
    function onSubmit(&$vars)
    {
        /* Loop through all vars and check if there's anything to do on
         * submit. */
        $variables = $this->getVariables();
        foreach ($variables as $var) {
            $var->type->onSubmit($var, $vars);
            /* If changes to var being tracked don't register the form as
             * submitted if old value and new value differ. */
            if ($var->getOption('trackchange')) {
                $varname = $var->getVarName();
                if (!is_null($vars->get('formname')) &&
                    $vars->get($varname) != $vars->get('__old_' . $varname)) {
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
    function setSubmitted($state = true)
    {
        $this->_submitted = $state;
    }

}
