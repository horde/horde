<?php
/**
 * Horde_Form Master Class.
 *
 * The Horde_Form:: package provides form rendering, validation, and
 * other functionality for the Horde Application Framework.
 *
 * Copyright 2001-2007 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Form
 */
class Horde_Form {

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
     * renderer class, which should extend Horde_Form_Renderer.
     *
     * @param array $params  A hash of renderer-specific parameters.
     *
     * @return object Horde_Form_Renderer  The form renderer.
     */
    function getRenderer($params = array())
    {
        return new Horde_Form_Renderer_Xhtml($params);
    }

    function getType($type, $params = array())
    {
        $type_class = 'Horde_Form_Type_' . $type;
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
        $var = new Horde_Form_Variable($humanName, $varName, $type,
                                       $required, $readonly, $description);

        /* Set the form object reference in the var. */
        $var->setFormOb($this);

        if ($var->getType() instanceof Horde_Form_Type_enum &&
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
     * @param Horde_Form_Variable|string $var  Either the variable's name or
     *                                         the variable to remove from the
     *                                         form.
     *
     * @return boolean  True if the variable was found (and deleted).
     */
    public function removeVariable(&$var)
    {
        foreach (array_keys($this->_variables) as $section) {
            foreach (array_keys($this->_variables[$section]) as $i) {
                if ((is_a($var, 'Horde_Form_Variable') && $this->_variables[$section][$i] === $var) ||
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
        $var = new Horde_Form_Variable('', $varName, $type, $required);
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
     * Preserve the variables/values from another Horde_Form object.
     */
    public function preserve(Horde_Form $form)
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
                $this->_errors['_formToken'] = _("This form has already been processed.");
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
        if (is_a($var, 'Horde_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        return isset($this->_errors[$name]) ? $this->_errors[$name] : null;
    }

    public function setError($var, $message)
    {
        if (is_a($var, 'Horde_Form_Variable')) {
            $name = $var->getVarName();
        } else {
            $name = $var;
        }
        $this->_errors[$name] = $message;
    }

    public function clearError($var)
    {
        if (is_a($var, 'Horde_Form_Variable')) {
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
        throw new Horde_Form_Exception('Subclass must overide execute()');
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
     * @param array  $variables  An array of Horde_Form_Variable objects to
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

/**
 * Horde_Form_Type Class
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Horde_Form
 */
class Horde_Form_Type
{
    public function __get($property)
    {
        $prop = '_' . $property;
        return isset($this->$prop) ? $this->$prop : null;
    }

    public function __set($property, $value)
    {
        $prop = '_' . $property;
        $this->$prop = $value;
    }

    public function __isset($property)
    {
        $prop = '_' . $property;
        return isset($this->$prop);
    }

    public function __unset($property)
    {
        $prop = '_' . $property;
        unset($this->$prop);
    }

    public function init()
    {
    }

    public function onSubmit()
    {
    }

    public function isValid($var, $vars, $value, &$message)
    {
        $message = '<strong>Error:</strong> Horde_Form_Type::isValid() called - should be overridden<br />';
        return false;
    }

    function getInfo($vars, $var, &$info)
    {
        $info = $var->getValue($vars);
    }
}

class Horde_Form_Type_number extends Horde_Form_Type
{
    var $_fraction;

    function init($fraction = null)
    {
        $this->_fraction = $fraction;
    }

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value) && ((string)(double)$value !== $value)) {
            $message = _("This field is required.");
            return false;
        } elseif (empty($value)) {
            return true;
        }

        /* If matched, then this is a correct numeric value. */
        if (preg_match($this->_getValidationPattern(), $value)) {
            return true;
        }

        $message = _("This field must be a valid number.");
        return false;
    }

    function _getValidationPattern()
    {
        static $pattern = '';
        if (!empty($pattern)) {
            return $pattern;
        }

        /* Get current locale information. */
        $linfo = Horde_Nls::getLocaleInfo();

        /* Build the pattern. */
        $pattern = '(-)?';

        /* Only check thousands separators if locale has any. */
        if (!empty($linfo['mon_thousands_sep'])) {
            /* Regex to check for correct thousands separators (if any). */
            $pattern .= '((\d+)|((\d{0,3}?)([' . $linfo['mon_thousands_sep'] . ']\d{3})*?))';
        } else {
            /* No locale thousands separator, check for only digits. */
            $pattern .= '(\d+)';
        }
        /* If no decimal point specified default to dot. */
        if (empty($linfo['mon_decimal_point'])) {
            $linfo['mon_decimal_point'] = '.';
        }
        /* Regex to check for correct decimals (if any). */
        if (empty($this->_fraction)) {
            $fraction = '*';
        } else {
            $fraction = '{0,' . $this->_fraction . '}';
        }
        $pattern .= '([' . $linfo['mon_decimal_point'] . '](\d' . $fraction . '))?';

        /* Put together the whole regex pattern. */
        $pattern = '/^' . $pattern . '$/';

        return $pattern;
    }

    function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->getVarName());
        $linfo = Horde_Nls::getLocaleInfo();
        $value = str_replace($linfo['mon_thousands_sep'], '', $value);
        $info = str_replace($linfo['mon_decimal_point'], '.', $value);
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Number"));
    }
}

class Horde_Form_Type_int extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value) && ((string)(int)$value !== $value)) {
            $message = _("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-9]+$/', $value)) {
            return true;
        }

        $message = _("This field may only contain integers.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Integer"));
    }

}

class Horde_Form_Type_octal extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value) && ((string)(int)$value !== $value)) {
            $message = _("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-7]+$/', $value)) {
            return true;
        }

        $message = _("This field may only contain octal values.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Octal"));
    }

}

class Horde_Form_Type_intlist extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if (empty($value) && $var->isRequired()) {
            $message = _("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-9 ,]+$/', $value)) {
            return true;
        }

        $message = _("This field must be a comma or space separated list of integers");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Integer list"));
    }

}

class Horde_Form_Type_text extends Horde_Form_Type {

    var $_regex;
    var $_size;
    var $_maxlength;

    /**
     * The initialisation function for the text variable type.
     *
     * @access private
     *
     * @param string $regex       Any valid PHP PCRE pattern syntax that
     *                            needs to be matched for the field to be
     *                            considered valid. If left empty validity
     *                            will be checked only for required fields
     *                            whether they are empty or not.
     *                            If using this regex test it is advisable
     *                            to enter a description for this field to
     *                            warn the user what is expected, as the
     *                            generated error message is quite generic
     *                            and will not give any indication where
     *                            the regex failed.
     * @param integer $size       The size of the input field.
     * @param integer $maxlength  The max number of characters.
     */
    function init($regex = '', $size = 40, $maxlength = null)
    {
        $this->_regex     = $regex;
        $this->_size      = $size;
        $this->_maxlength = $maxlength;
    }

    public function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if (!empty($this->_maxlength) && Horde_String::length($value) > $this->_maxlength) {
            $valid = false;
            $message = sprintf(_("Value is over the maximum length of %s."), $this->_maxlength);
        } elseif ($var->isRequired() && empty($this->_regex)) {
            if (!($valid = strlen(trim($value)) > 0)) {
                $message = _("This field is required.");
            }
        } elseif (strlen($this->_regex)) {
            if (!($valid = preg_match($this->_regex, $value))) {
                $message = _("You must enter a valid value.");
            }
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Text"),
            'params' => array(
                'regex'     => array('label' => _("Regex"),
                                     'type'  => 'text'),
                'size'      => array('label' => _("Size"),
                                     'type'  => 'int'),
                'maxlength' => array('label' => _("Maximum length"),
                                     'type'  => 'int')));
    }

}

class Horde_Form_Type_stringlist extends Horde_Form_Type_text {

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("String list"),
            'params' => array(
                'regex'     => array('label' => _("Regex"),
                                     'type'  => 'text'),
                'size'      => array('label' => _("Size"),
                                     'type'  => 'int'),
                'maxlength' => array('label' => _("Maximum length"),
                                     'type'  => 'int')),
        );
    }

}

class Horde_Form_Type_phone extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->isRequired()) {
            $valid = strlen(trim($value)) > 0;
            if (!$valid) {
                $message = _("This field is required.");
            }
        } else {
            $valid = preg_match('/^\+?[\d()\-\/ ]*$/', $value);
            if (!$valid) {
                $message = _("You must enter a valid phone number, digits only with an optional '+' for the international dialing prefix.");
            }
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Phone number"));
    }

}

class Horde_Form_Type_cellphone extends Horde_Form_Type_phone {

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Mobile phone number"));
    }

}

class Horde_Form_Type_ipaddress extends Horde_Form_Type_text {

    function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if (strlen(trim($value)) > 0) {
            $ip = explode('.', $value);
            $valid = (count($ip) == 4);
            if ($valid) {
                foreach ($ip as $part) {
                    if (!is_numeric($part) ||
                        $part > 255 ||
                        $part < 0) {
                        $valid = false;
                        break;
                    }
                }
            }

            if (!$valid) {
                $message = _("Please enter a valid IP address.");
            }
        } elseif ($var->isRequired()) {
            $valid = false;
            $message = _("This field is required.");
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("IP address"));
    }

}

class Horde_Form_Type_longtext extends Horde_Form_Type_text {

    var $_rows;
    var $_cols;
    var $_helper = array();

    function init($rows = 8, $cols = 80, $helper = array())
    {
        if (!is_array($helper)) {
            $helper = array($helper);
        }

        $this->_rows = $rows;
        $this->_cols = $cols;
        $this->_helper = $helper;
    }

    function hasHelper($option = '')
    {
        if (empty($option)) {
            /* No option specified, check if any helpers have been
             * activated. */
            return !empty($this->_helper);
        } elseif (empty($this->_helper)) {
            /* No helpers activated at all, return false. */
            return false;
        } else {
            /* Check if given helper has been activated. */
            return in_array($option, $this->_helper);
        }
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Long text"),
            'params' => array(
                'rows'   => array('label' => _("Number of rows"),
                                  'type'  => 'int'),
                'cols'   => array('label' => _("Number of columns"),
                                  'type'  => 'int'),
                'helper' => array('label' => _("Helper?"),
                                  'type'  => 'boolean')));
    }

}

class Horde_Form_Type_countedtext extends Horde_Form_Type_longtext {

    var $_chars;

    function init($rows = null, $cols = null, $chars = 1000)
    {
        parent::init($rows, $cols);
        $this->_chars = $chars;
    }

    function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        $length = Horde_String::length(trim($value));

        if ($var->isRequired() && $length <= 0) {
            $valid = false;
            $message = _("This field is required.");
        } elseif ($length > $this->_chars) {
            $valid = false;
            $message = sprintf(_("There are too many characters in this field. You have entered %s characters; you must enter less than %s."), Horde_String::length(trim($value)), $this->_chars);
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Counted text"),
            'params' => array(
                'rows'  => array('label' => _("Number of rows"),
                                 'type'  => 'int'),
                'cols'  => array('label' => _("Number of columns"),
                                 'type'  => 'int'),
                'chars' => array('label' => _("Number of characters"),
                                 'type'  => 'int')));
    }

}

class Horde_Form_Type_address extends Horde_Form_Type_longtext {

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Address"),
            'params' => array(
                'rows' => array('label' => _("Number of rows"),
                                'type'  => 'int'),
                'cols' => array('label' => _("Number of columns"),
                                'type'  => 'int')));
    }

}

class Horde_Form_Type_addresslink extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

}

class Horde_Form_Type_file extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired()) {
            try {
                $GLOBALS['browser']->wasFileUploaded($var->getVarName());
            } catch (Horde_Browser_Exception $e) {
                $message = $e->getMessage();
                return false;
            }
        }

        return true;
    }

    function getInfo($vars, $var, &$info)
    {
        $name = $var->getVarName();
        try {
            $GLOBALS['browser']->wasFileUploaded($name);
            $info['name'] = $_FILES[$name]['name'];
            $info['type'] = $_FILES[$name]['type'];
            $info['tmp_name'] = $_FILES[$name]['tmp_name'];
            $info['file'] = $_FILES[$name]['tmp_name'];
            $info['error'] = $_FILES[$name]['error'];
            $info['size'] = $_FILES[$name]['size'];
        } catch (Horde_Browser_Exception $e) {}
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("File upload"));
    }

}

class Horde_Form_Type_boolean extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    function getInfo($vars, $var, &$info)
    {
        $info = Horde_String::lower($vars->get($var->getVarName())) == 'on';
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("True or false"));
    }

}

class Horde_Form_Type_link extends Horde_Form_Type {

    /**
     * List of hashes containing link parameters. Possible keys: 'url', 'text',
     * 'target', 'onclick', 'title', 'accesskey'.
     *
     * @var array
     */
    var $values;

    function init($values)
    {
        $this->values = $values;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Link"),
            'params' => array(
                'url' => array(
                    'label' => _("Link URL"),
                    'type' => 'text'),
                'text' => array(
                    'label' => _("Link text"),
                    'type' => 'text'),
                'target' => array(
                    'label' => _("Link target"),
                    'type' => 'text'),
                'onclick' => array(
                    'label' => _("Onclick event"),
                    'type' => 'text'),
                'title' => array(
                    'label' => _("Link title attribute"),
                    'type' => 'text'),
                'accesskey' => array(
                    'label' => _("Link access key"),
                    'type' => 'text')));
    }

}

class Horde_Form_Type_email extends Horde_Form_Type {

    var $_allow_multi = false;
    var $_strip_domain = false;
    var $_link_compose = false;
    var $_check_smtp = false;
    var $_link_name;

    /**
     * A string containing valid delimiters (default is just comma).
     *
     * @var string
     */
    var $_delimiters = ',';

    function init($allow_multi = false, $strip_domain = false,
                  $link_compose = false, $link_name = null,
                  $delimiters = ',')
    {
        $this->_allow_multi = $allow_multi;
        $this->_strip_domain = $strip_domain;
        $this->_link_compose = $link_compose;
        $this->_link_name = $link_name;
        $this->_delimiters = $delimiters;
    }

    /**
     */
    function isValid($var, $vars, $value, &$message)
    {
        // Split into individual addresses.
        $emails = $this->splitEmailAddresses($value);

        // Check for too many.
        if (!$this->_allow_multi && count($emails) > 1) {
            $message = _("Only one email address is allowed.");
            return false;
        }

        // Check for all valid and at least one non-empty.
        $nonEmpty = 0;
        foreach ($emails as $email) {
            if (!strlen($email)) {
                continue;
            }
            if (!$this->validateEmailAddress($email)) {
                $message = sprintf(_("\"%s\" is not a valid email address."), $email);
                return false;
            }
            ++$nonEmpty;
        }

        if (!$nonEmpty && $var->isRequired()) {
            if ($this->_allow_multi) {
                $message = _("You must enter at least one email address.");
            } else {
                $message = _("You must enter an email address.");
            }
            return false;
        }

        return true;
    }

    /**
     * Explodes an RFC 2822 string, ignoring a delimiter if preceded
     * by a "\" character, or if the delimiter is inside single or
     * double quotes.
     *
     * @param string $string     The RFC 822 string.
     *
     * @return array  The exploded string in an array.
     */
    function splitEmailAddresses($string)
    {
        $quotes = array('"', "'");
        $emails = array();
        $pos = 0;
        $in_quote = null;
        $in_group = false;
        $prev = null;

        if (!strlen($string)) {
            return array();
        }

        $char = $string[0];
        if (in_array($char, $quotes)) {
            $in_quote = $char;
        } elseif ($char == ':') {
            $in_group = true;
        } elseif (strpos($this->_delimiters, $char) !== false) {
            $emails[] = '';
            $pos = 1;
        }

        for ($i = 1, $iMax = strlen($string); $i < $iMax; ++$i) {
            $char = $string[$i];
            if (in_array($char, $quotes)) {
                if ($prev !== '\\') {
                    if ($in_quote === $char) {
                        $in_quote = null;
                    } elseif (is_null($in_quote)) {
                        $in_quote = $char;
                    }
                }
            } elseif ($in_group) {
                if ($char == ';') {
                    $emails[] = substr($string, $pos, $i - $pos + 1);
                    $pos = $i + 1;
                    $in_group = false;
                }
            } elseif ($char == ':') {
                $in_group = true;
            } elseif (strpos($this->_delimiters, $char) !== false &&
                      $prev !== '\\' &&
                      is_null($in_quote)) {
                $emails[] = substr($string, $pos, $i - $pos);
                $pos = $i + 1;
            }
            $prev = $char;
        }

        if ($pos != $i) {
            /* The string ended without a delimiter. */
            $emails[] = substr($string, $pos, $i - $pos);
        }

        return $emails;
    }

    /**
     * RFC(2)822 Email Parser.
     *
     * By Cal Henderson <cal@iamcal.com>
     * This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     * http://creativecommons.org/licenses/by-sa/2.5/
     *
     * http://code.iamcal.com/php/rfc822/
     *
     * http://iamcal.com/publish/articles/php/parsing_email
     *
     * Revision 4
     *
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    function validateEmailAddress($email)
    {
        static $comment_regexp, $email_regexp;
        if ($comment_regexp === null) {
            $this->_defineValidationRegexps($comment_regexp, $email_regexp);
        }

        // We need to strip comments first (repeat until we can't find
        // any more).
        while (true) {
            $new = preg_replace("!$comment_regexp!", '', $email);
            if (strlen($new) == strlen($email)){
                break;
            }
            $email = $new;
        }

        // Now match what's left.
        $result = (bool)preg_match("!^$email_regexp$!", $email);
        if ($result && $this->_check_smtp) {
            $result = $this->validateEmailAddressSmtp($email);
        }

        return $result;
    }

    /**
     * Attempt partial delivery of mail to an address to validate it.
     *
     * @param string $email An individual email address to validate.
     *
     * @return boolean
     */
    function validateEmailAddressSmtp($email)
    {
        list(, $maildomain) = explode('@', $email, 2);

        // Try to get the real mailserver from MX records.
        if (function_exists('getmxrr') &&
            @getmxrr($maildomain, $mxhosts, $mxpriorities)) {
            // MX record found.
            array_multisort($mxpriorities, $mxhosts);
            $mailhost = $mxhosts[0];
        } else {
            // No MX record found, try the root domain as the mail
            // server.
            $mailhost = $maildomain;
        }

        $fp = @fsockopen($mailhost, 25, $errno, $errstr, 5);
        if (!$fp) {
            return false;
        }

        // Read initial response.
        fgets($fp, 4096);

        // HELO
        fputs($fp, "HELO $mailhost\r\n");
        fgets($fp, 4096);

        // MAIL FROM
        fputs($fp, "MAIL FROM: <root@example.com>\r\n");
        fgets($fp, 4096);

        // RCPT TO - gets the result we want.
        fputs($fp, "RCPT TO: <$email>\r\n");
        $result = trim(fgets($fp, 4096));

        // QUIT
        fputs($fp, "QUIT\r\n");
        fgets($fp, 4096);
        fclose($fp);

        return substr($result, 0, 1) == '2';
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Email"),
            'params' => array(
                'allow_multi' => array('label' => _("Allow multiple addresses?"),
                                       'type'  => 'boolean'),
                'strip_domain' => array('label' => _("Protect address from spammers?"),
                                        'type' => 'boolean'),
                'link_compose' => array('label' => _("Link the email address to the compose page when displaying?"),
                                        'type' => 'boolean'),
                'link_name' => array('label' => _("The name to use when linking to the compose page"),
                                     'type' => 'text'),
                'delimiters' => array('label' => _("Character to split multiple addresses with"),
                                      'type' => 'text'),
            ),
        );
    }

    /**
     * RFC(2)822 Email Parser.
     *
     * By Cal Henderson <cal@iamcal.com>
     * This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
     * http://creativecommons.org/licenses/by-sa/2.5/
     *
     * http://code.iamcal.com/php/rfc822/
     *
     * http://iamcal.com/publish/articles/php/parsing_email
     *
     * Revision 4
     *
     * @param string &$comment The regexp for comments.
     * @param string &$addr_spec The regexp for email addresses.
     */
    function _defineValidationRegexps(&$comment, &$addr_spec)
    {
        /**
         * NO-WS-CTL       =       %d1-8 /         ; US-ASCII control characters
         *                         %d11 /          ;  that do not include the
         *                         %d12 /          ;  carriage return, line feed,
         *                         %d14-31 /       ;  and white space characters
         *                         %d127
         * ALPHA          =  %x41-5A / %x61-7A   ; A-Z / a-z
         * DIGIT          =  %x30-39
         */
        $no_ws_ctl  = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
        $alpha      = "[\\x41-\\x5a\\x61-\\x7a]";
        $digit      = "[\\x30-\\x39]";
        $cr         = "\\x0d";
        $lf         = "\\x0a";
        $crlf       = "($cr$lf)";

        /**
         * obs-char        =       %d0-9 / %d11 /          ; %d0-127 except CR and
         *                         %d12 / %d14-127         ;  LF
         * obs-text        =       *LF *CR *(obs-char *LF *CR)
         * text            =       %d1-9 /         ; Characters excluding CR and LF
         *                         %d11 /
         *                         %d12 /
         *                         %d14-127 /
         *                         obs-text
         * obs-qp          =       "\" (%d0-127)
         * quoted-pair     =       ("\" text) / obs-qp
         */
        $obs_char       = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
        $obs_text       = "($lf*$cr*($obs_char$lf*$cr*)*)";
        $text           = "([\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";
        $obs_qp         = "(\\x5c[\\x00-\\x7f])";
        $quoted_pair    = "(\\x5c$text|$obs_qp)";

        /**
         * obs-FWS         =       1*WSP *(CRLF 1*WSP)
         * FWS             =       ([*WSP CRLF] 1*WSP) /   ; Folding white space
         *                         obs-FWS
         * ctext           =       NO-WS-CTL /     ; Non white space controls
         *                         %d33-39 /       ; The rest of the US-ASCII
         *                         %d42-91 /       ;  characters not including "(",
         *                         %d93-126        ;  ")", or "\"
         * ccontent        =       ctext / quoted-pair / comment
         * comment         =       "(" *([FWS] ccontent) [FWS] ")"
         * CFWS            =       *([FWS] comment) (([FWS] comment) / FWS)
         *
         * @note: We translate ccontent only partially to avoid an
         * infinite loop. Instead, we'll recursively strip comments
         * before processing the input.
         */
        $wsp        = "[\\x20\\x09]";
        $obs_fws    = "($wsp+($crlf$wsp+)*)";
        $fws        = "((($wsp*$crlf)?$wsp+)|$obs_fws)";
        $ctext      = "($no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
        $ccontent   = "($ctext|$quoted_pair)";
        $comment    = "(\\x28($fws?$ccontent)*$fws?\\x29)";
        $cfws       = "(($fws?$comment)*($fws?$comment|$fws))";
        $cfws       = "$fws*";

        /**
         * atext           =       ALPHA / DIGIT / ; Any character except controls,
         *                         "!" / "#" /     ;  SP, and specials.
         *                         "$" / "%" /     ;  Used for atoms
         *                         "&" / "'" /
         *                         "*" / "+" /
         *                         "-" / "/" /
         *                         "=" / "?" /
         *                         "^" / "_" /
         *                         "`" / "{" /
         *                         "|" / "}" /
         *                         "~"
         * atom            =       [CFWS] 1*atext [CFWS]
         */
        $atext      = "($alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2e\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
        $atom       = "($cfws?$atext+$cfws?)";

        /**
         * qtext           =       NO-WS-CTL /     ; Non white space controls
         *                         %d33 /          ; The rest of the US-ASCII
         *                         %d35-91 /       ;  characters not including "\"
         *                         %d93-126        ;  or the quote character
         * qcontent        =       qtext / quoted-pair
         * quoted-string   =       [CFWS]
         *                         DQUOTE *([FWS] qcontent) [FWS] DQUOTE
         *                         [CFWS]
         * word            =       atom / quoted-string
         */
        $qtext      = "($no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
        $qcontent   = "($qtext|$quoted_pair)";
        $quoted_string  = "($cfws?\\x22($fws?$qcontent)*$fws?\\x22$cfws?)";
        $word       = "($atom|$quoted_string)";

        /**
         * obs-local-part  =       word *("." word)
         * obs-domain      =       atom *("." atom)
         */
        $obs_local_part = "($word(\\x2e$word)*)";
        $obs_domain = "($atom(\\x2e$atom)*)";

        /**
         * dot-atom-text   =       1*atext *("." 1*atext)
         * dot-atom        =       [CFWS] dot-atom-text [CFWS]
         */
        $dot_atom_text  = "($atext+(\\x2e$atext+)*)";
        $dot_atom   = "($cfws?$dot_atom_text$cfws?)";

        /**
         * domain-literal  =       [CFWS] "[" *([FWS] dcontent) [FWS] "]" [CFWS]
         * dcontent        =       dtext / quoted-pair
         * dtext           =       NO-WS-CTL /     ; Non white space controls
         *
         *                         %d33-90 /       ; The rest of the US-ASCII
         *                         %d94-126        ;  characters not including "[",
         *                                         ;  "]", or "\"
         */
        $dtext      = "($no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
        $dcontent   = "($dtext|$quoted_pair)";
        $domain_literal = "($cfws?\\x5b($fws?$dcontent)*$fws?\\x5d$cfws?)";

        /**
         * local-part      =       dot-atom / quoted-string / obs-local-part
         * domain          =       dot-atom / domain-literal / obs-domain
         * addr-spec       =       local-part "@" domain
         */
        $local_part = "($dot_atom|$quoted_string|$obs_local_part)";
        $domain     = "($dot_atom|$domain_literal|$obs_domain)";
        $addr_spec  = "($local_part\\x40$domain)";
    }

}

class Horde_Form_Type_matrix extends Horde_Form_Type {

    var $_cols;
    var $_rows;
    var $_matrix;
    var $_new_input;

    /**
     * Initializes the variable.
     *
     * @example
     * init(array('Column A', 'Column B'),
     *      array(1 => 'Row One', 2 => 'Row 2', 3 => 'Row 3'),
     *      array(array(true, true, false),
     *            array(true, false, true),
     *            array(fasle, true, false)),
     *      array('Row 4', 'Row 5'));
     *
     * @param array $cols               A list of column headers.
     * @param array $rows               A hash with row IDs as the keys and row
     *                                  labels as the values.
     * @param array $matrix             A two dimensional hash with the field
     *                                  values.
     * @param boolean|array $new_input  If true, a free text field to add a new
     *                                  row is displayed on the top, a select
     *                                  box if this parameter is a value.
     */
    function init($cols, $rows = array(), $matrix = array(), $new_input = false)
    {
        $this->_cols       = $cols;
        $this->_rows       = $rows;
        $this->_matrix     = $matrix;
        $this->_new_input  = $new_input;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    function getInfo($vars, $var, &$info)
    {
        $values = $vars->get($var->getVarName());
        if (!empty($values['n']['r']) && isset($values['n']['v'])) {
            $new_row = $values['n']['r'];
            $values['r'][$new_row] = $values['n']['v'];
            unset($values['n']);
        }

        $info = (isset($values['r']) ? $values['r'] : array());
    }

    function about()
    {
        return array(
            'name' => _("Field matrix"),
            'params' => array(
                'cols' => array('label' => _("Column titles"),
                                'type'  => 'stringlist')));
    }

}

class Horde_Form_Type_emailConfirm extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value['original'])) {
            $message = _("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = _("Email addresses must match.");
            return false;
        } else {
            $parser = new Horde_Mail_Rfc822();
            $parsed_email = $parser->parseAddressList($value['original'], array(
                'default_domain' => false
            ));

            if (count($parsed_email) > 1) {
                $message = _("Only one email address allowed.");
                return false;
            }
            if (empty($parsed_email[0]->mailbox)) {
                $message = _("You did not enter a valid email address.");
                return false;
            }
        }

        return true;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Email with confirmation"));
    }

}

class Horde_Form_Type_password extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->isRequired()) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = _("This field is required.");
            }
        }

        return $valid;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Password"));
    }

}

class Horde_Form_Type_passwordconfirm extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value['original'])) {
            $message = _("This field is required.");
            return false;
        }

        if ($value['original'] != $value['confirm']) {
            $message = _("Passwords must match.");
            return false;
        }

        return true;
    }

    function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->getVarName());
        $info = $value['original'];
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Password with confirmation"));
    }

}

class Horde_Form_Type_enum extends Horde_Form_Type {

    var $_values;
    var $_prompt;

    function init($values, $prompt = null)
    {
        $this->_values = $values;

        if ($prompt === true) {
            $this->_prompt = _("-- select --");
        } else {
            $this->_prompt = $prompt;
        }
    }

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && $value == '' && !isset($this->_values[$value])) {
            $message = _("This field is required.");
            return false;
        }

        if (count($this->_values) == 0 || isset($this->_values[$value]) ||
            ($this->_prompt && empty($value))) {
            return true;
        }

        $message = _("Invalid data.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Drop down list"),
            'params' => array(
                'values' => array('label' => _("Values to select from"),
                                  'type'  => 'stringlist'),
                'prompt' => array('label' => _("Prompt text"),
                                  'type'  => 'text')));
    }

}

class Horde_Form_Type_mlenum extends Horde_Form_Type {

    var $_values;
    var $_prompts;

    function init(&$values, $prompts = null)
    {
        $this->_values = &$values;

        if ($prompts === true) {
            $this->_prompts = array(_("-- select --"), _("-- select --"));
        } elseif (!is_array($prompts)) {
            $this->_prompts = array($prompts, $prompts);
        } else {
            $this->_prompts = $prompts;
        }
    }

    function onSubmit($var, $vars)
    {
        $varname = $var->getVarName();
        $value = $vars->get($varname);

        if ($value['1'] != $value['old']) {
            $var->form->setSubmitted(false);
        }
    }

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && (empty($value['1']) || empty($value['2']))) {
            $message = _("This field is required.");
            return false;
        }

        if (!count($this->_values) || isset($this->_values[$value['1']]) ||
            (!empty($this->_prompts) && empty($value['1']))) {
            return true;
        }

        $message = _("Invalid data.");
        return false;
    }

    function getInfo($vars, &$var, &$info)
    {
        $info = $vars->get($var->getVarName());
        return $info['2'];
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Multi-level drop down lists"),
            'params' => array(
                'values' => array('label' => _("Values to select from"),
                                  'type'  => 'stringlist'),
                'prompt' => array('label' => _("Prompt text"),
                                  'type'  => 'text')));
    }

}

class Horde_Form_Type_multienum extends Horde_Form_Type_enum {

    var $size = 5;

    function init($values, $size = null)
    {
        if (!is_null($size)) {
            $this->size = (int)$size;
        }

        parent::init($values);
    }

    function isValid($var, $vars, $value, &$message)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                if (!$this->isValid($var, $vars, $val, $message)) {
                    return false;
                }
            }
            return true;
        }

        if (empty($value) && ((string)(int)$value !== $value)) {
            if ($var->isRequired()) {
                $message = _("This field is required.");
                return false;
            } else {
                return true;
            }
        }

        if (count($this->_values) == 0 || isset($this->_values[$value])) {
            return true;
        }

        $message = _("Invalid data.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Multiple selection"),
            'params' => array(
                'values' => array('label' => _("Values"),
                                  'type'  => 'stringlist'),
                'size'   => array('label' => _("Size"),
                                  'type'  => 'int'))
        );
    }

}

class Horde_Form_Type_keyval_multienum extends Horde_Form_Type_multienum {

    function getInfo($vars, $var, &$info)
    {
        $value = $vars->get($var->getVarName());
        $info = array();
        foreach ($value as $key) {
            $info[$key] = $this->_values[$key];
        }
    }

}

class Horde_Form_Type_radio extends Horde_Form_Type_enum {

    /* Entirely implemented by Horde_Form_Type_enum; just a different
     * view. */

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Radio selection"),
            'params' => array(
                'values' => array('label' => _("Values"),
                                  'type'  => 'stringlist')));
    }

}

class Horde_Form_Type_set extends Horde_Form_Type {

    var $_values;
    var $_checkAll = false;

    function init(&$values, $checkAll = false)
    {
        $this->_values = $values;
        $this->_checkAll = $checkAll;
    }

    function isValid($var, $vars, $value, &$message)
    {
        if (count($this->_values) == 0 || count($value) == 0) {
            return true;
        }
        foreach ($value as $item) {
            if (!isset($this->_values[$item])) {
                $error = true;
                break;
            }
        }
        if (!isset($error)) {
            return true;
        }

        $message = _("Invalid data.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Set"),
            'params' => array(
                'values' => array('label' => _("Values"),
                                  'type'  => 'stringlist')));
    }

}

class Horde_Form_Type_date extends Horde_Form_Type {

    var $_format;

    function init($format = '%a %d %B')
    {
        $this->_format = $format;
    }

    function isValid($var, $vars, $value, &$message)
    {
        $valid = true;

        if ($var->isRequired()) {
            $valid = strlen(trim($value)) > 0;

            if (!$valid) {
                $message = sprintf(_("%s is required"), $var->getHumanName());
            }
        }

        return $valid;
    }

    /**
     * @static
     */
    function getAgo($timestamp)
    {
        if ($timestamp === null) {
            return '';
        }

        $diffdays = Date_Calc::dateDiff(date('j', $timestamp),
                                        date('n', $timestamp),
                                        date('Y', $timestamp),
                                        date('j'), date('n'), date('Y'));

        /* An error occured. */
        if ($diffdays == -1) {
            return;
        }

        $ago = $diffdays * Date_Calc::compareDates(date('j', $timestamp),
                                                   date('n', $timestamp),
                                                   date('Y', $timestamp),
                                                   date('j'), date('n'),
                                                   date('Y'));
        if ($ago < -1) {
            return sprintf(_(" (%s days ago)"), $diffdays);
        } elseif ($ago == -1) {
            return _(" (yesterday)");
        } elseif ($ago == 0) {
            return _(" (today)");
        } elseif ($ago == 1) {
            return _(" (tomorrow)");
        } else {
            return sprintf(_(" (in %s days)"), $diffdays);
        }
    }

    function getFormattedTime($timestamp, $format = null, $showago = true)
    {
        if (empty($format)) {
            $format = $this->_format;
        }
        if (!empty($timestamp)) {
            return strftime($format, $timestamp) . ($showago ? Horde_Form_Type_date::getAgo($timestamp) : '');
        } else {
            return '';
        }
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Date"));
    }

}

class Horde_Form_Type_time extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value) && ((string)(double)$value !== $value)) {
            $message = _("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^[0-2]?[0-9]:[0-5][0-9]$/', $value)) {
            return true;
        }

        $message = _("This field may only contain numbers and the colon.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Time"));
    }

}

class Horde_Form_Type_hourminutesecond extends Horde_Form_Type {

    var $_show_seconds;

    function init($show_seconds = false)
    {
        $this->_show_seconds = $show_seconds;
    }

    function isValid($var, $vars, $value, &$message)
    {
        $time = $vars->get($var->getVarName());
        if (!$this->_show_seconds && !isset($time['second'])) {
            $time['second'] = 0;
        }

        if (!$this->emptyTimeArray($time) && !$this->checktime($time['hour'], $time['minute'], $time['second'])) {
            $message = _("Please enter a valid time.");
            return false;
        } elseif ($this->emptyTimeArray($time) && $var->isRequired()) {
            $message = _("This field is required.");
            return false;
        }

        return true;
    }

    function checktime($hour, $minute, $second)
    {
        if (!isset($hour) || $hour == '' || ($hour < 0 || $hour > 23)) {
            return false;
        }
        if (!isset($minute) || $minute == '' || ($minute < 0 || $minute > 60)) {
            return false;
        }
        if (!isset($second) || $second === '' || ($second < 0 || $second > 60)) {
            return false;
        }

        return true;
    }

    /**
     * Return the time supplied as a Horde_Date object.
     *
     * @param string $time_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                         UNIX epoch).
     *
     * @return Date  The time object.
     */
    function getTimeOb($time_in)
    {
        if (is_array($time_in)) {
            if (!$this->emptyTimeArray($time_in)) {
                $time_in = sprintf('1970-01-01 %02d:%02d:%02d', $time_in['hour'], $time_in['minute'], $this->_show_seconds ? $time_in['second'] : 0);
            }
        }

        return new Horde_Date($time_in);
    }

    /**
     * Return the time supplied split up into an array.
     *
     * @param string $time_in  Time in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                         UNIX epoch).
     *
     * @return array  Array with three elements - hour, minute and seconds.
     */
    function getTimeParts($time_in)
    {
        if (is_array($time_in)) {
            /* This is probably a failed isValid input so just return the
             * parts as they are. */
            return $time_in;
        } elseif (empty($time_in)) {
            /* This is just an empty field so return empty parts. */
            return array('hour' => '', 'minute' => '', 'second' => '');
        }
        $time = $this->getTimeOb($time_in);
        return array('hour' => $time->hour,
                     'minute' => $time->min,
                     'second' => $time->sec);
    }

    function emptyTimeArray($time)
    {
        return (is_array($time) && empty($time['hour']) && empty($time['minute']) && empty($time['second']));
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Time selection"),
            'params' => array(
                'seconds' => array('label' => _("Show seconds?"),
                                  'type'  => 'boolean')));
    }

}

class Horde_Form_Type_monthyear extends Horde_Form_Type {

    var $_start_year;
    var $_end_year;

    function init($start_year = null, $end_year = null)
    {
        if (empty($start_year)) {
            $start_year = 1920;
        }
        if (empty($end_year)) {
            $end_year = date('Y');
        }

        $this->_start_year = $start_year;
        $this->_end_year = $end_year;
    }

    function isValid($var, $vars, $value, &$message)
    {
        if (!$var->isRequired()) {
            return true;
        }

        if (!$vars->get($this->getMonthVar($var)) ||
            !$vars->get($this->getYearVar($var))) {
            $message = _("Please enter a month and a year.");
            return false;
        }

        return true;
    }

    function getMonthVar($var)
    {
        return $var->getVarName() . '[month]';
    }

    function getYearVar($var)
    {
        return $var->getVarName() . '[year]';
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Month and year"),
                     'params' => array(
                         'start_year' => array('label' => _("Start year"),
                                               'type'  => 'int'),
                         'end_year'   => array('label' => _("End year"),
                                               'type'  => 'int')));
    }

}

class Horde_Form_Type_monthdayyear extends Horde_Form_Type {

    var $_start_year;
    var $_end_year;
    var $_picker;
    var $_format_in = null;
    var $_format_out = '%x';

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param integer $start_year  The first available year for input.
     * @param integer $end_year    The last available year for input.
     * @param boolean $picker      Do we show the DHTML calendar?
     * @param integer $format_in   The format to use when sending the date
     *                             for storage. Defaults to Unix epoch.
     *                             Similar to the strftime() function.
     * @param integer $format_out  The format to use when displaying the
     *                             date. Similar to the strftime() function.
     */
    function init($start_year = '', $end_year = '', $picker = true,
                  $format_in = null, $format_out = '%x')
    {
        if (empty($start_year)) {
            $start_year = date('Y');
        }
        if (empty($end_year)) {
            $end_year = date('Y') + 10;
        }

        $this->_start_year = $start_year;
        $this->_end_year = $end_year;
        $this->_picker = $picker;
        $this->_format_in = $format_in;
        $this->_format_out = $format_out;
    }

    function isValid($var, $vars, $value, &$message)
    {
        $date = $vars->get($var->getVarName());
        $empty = $this->emptyDateArray($date);

        if ($empty == 1 && $var->isRequired()) {
            $message = _("This field is required.");
            return false;
        } elseif ($empty == 0 && !checkdate($date['month'], $date['day'], $date['year'])) {
            $message = _("Please enter a valid date, check the number of days in the month.");
            return false;
        } elseif ($empty == -1) {
            $message = _("Select all date components.");
            return false;
        }

        return true;
    }

    function emptyDateArray($date)
    {
        if (!is_array($date)) {
            return empty($date);
        }

        $empty = 0;
        /* Check each date array component. */
        foreach ($date as $key => $val) {
            if (empty($val)) {
                $empty++;
            }
        }

        /* Check state of empty. */
        if ($empty == 0) {
            /* If no empty parts return 0. */
            return 0;
        } elseif ($empty == count($date)) {
            /* If all empty parts return 1. */
            return 1;
        } else {
            /* If some empty parts return -1. */
            return -1;
        }
    }

    /**
     * Return the date supplied split up into an array.
     *
     * @param string $date_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS
     *                         and UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return array  Array with three elements - year, month and day.
     */
    function getDateParts($date_in)
    {
        if (is_array($date_in)) {
            /* This is probably a failed isValid input so just return
             * the parts as they are. */
            return $date_in;
        } elseif (empty($date_in)) {
            /* This is just an empty field so return empty parts. */
            return array('year' => '', 'month' => '', 'day' => '');
        }

        $date = $this->getDateOb($date_in);
        return array('year' => $date->year,
                     'month' => $date->month,
                     'day' => $date->mday);
    }

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param string $date_in  Date in one of the three formats supported by
     *                         Horde_Form and Horde_Date (ISO format
     *                         YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS
     *                         and UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return Date  The date object.
     */
    function getDateOb($date_in)
    {
        if (is_array($date_in)) {
            /* If passed an array change it to the ISO format. */
            if ($this->emptyDateArray($date_in) == 0) {
                $date_in = sprintf('%04d-%02d-%02d 00:00:00',
                                   $date_in['year'],
                                   $date_in['month'],
                                   $date_in['day']);
            }
        } elseif (preg_match('/^\d{4}-?\d{2}-?\d{2}$/', $date_in)) {
            /* Fix the date if it is the shortened ISO. */
            $date_in = $date_in . ' 00:00:00';
        }

        return new Horde_Date($date_in);
    }

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param string $date  Either an already set up Horde_Date object or a
     *                      string date in one of the three formats supported
     *                      by Horde_Form and Horde_Date (ISO format
     *                      YYYY-MM-DD HH:MM:SS, timestamp YYYYMMDDHHMMSS and
     *                      UNIX epoch) plus the fourth YYYY-MM-DD.
     *
     * @return string  The date formatted according to the $format_out
     *                 parameter when setting up the monthdayyear field.
     */
    function formatDate($date)
    {
        if (!is_a($date, 'Date')) {
            $date = $this->getDateOb($date);
        }

        return $date->strftime($this->_format_out);
    }

    /**
     * Insert the date input through the form into $info array, in the format
     * specified by the $format_in parameter when setting up monthdayyear
     * field.
     */
    function getInfo($vars, &$var, &$info)
    {
        $info = $this->_validateAndFormat($var->getValue($vars), $var);
    }

    /**
     * Validate/format a date submission.
     */
    function _validateAndFormat($value, $var)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        if ($this->emptyDateArray($value) == 1) {
            return $var->getDefault();
        } else {
            $date = $this->getDateOb($value);
            if ($this->_format_in === null) {
                return $date->timestamp();
            } else {
                return $date->strftime($this->_format_in);
            }
        }
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Date selection"),
            'params' => array(
                'start_year' => array('label' => _("Start year"),
                                      'type'  => 'int'),
                'end_year'   => array('label' => _("End year"),
                                      'type'  => 'int'),
                'picker'     => array('label' => _("Show picker?"),
                                      'type'  => 'boolean'),
                'format_in'  => array('label' => _("Storage format"),
                                      'type'  => 'text'),
                'format_out' => array('label' => _("Display format"),
                                      'type'  => 'text')));
    }

}

class Horde_Form_Type_datetime extends Horde_Form_Type {

    var $_mdy;
    var $_hms;

    /**
     * Return the date supplied as a Horde_Date object.
     *
     * @param integer $start_year  The first available year for input.
     * @param integer $end_year    The last available year for input.
     * @param boolean $picker      Do we show the DHTML calendar?
     * @param integer $format_in   The format to use when sending the date
     *                             for storage. Defaults to Unix epoch.
     *                             Similar to the strftime() function.
     * @param integer $format_out  The format to use when displaying the
     *                             date. Similar to the strftime() function.
     * @param boolean $show_seconds Include a form input for seconds.
     */
    function init($start_year = '', $end_year = '', $picker = true,
                  $format_in = null, $format_out = '%x', $show_seconds = false)
    {
        $this->_mdy = new Horde_Form_Type_monthdayyear();
        $this->_mdy->init($start_year, $end_year, $picker, $format_in, $format_out);

        $this->_hms = new Horde_Form_Type_hourminutesecond();
        $this->_hms->init($show_seconds);
    }

    function isValid(&$var, &$vars, $value, &$message)
    {
        if ($var->isRequired()) {
            return $this->_mdy->isValid($var, $vars, $value, $message) &&
                $this->_hms->isValid($var, $vars, $value, $message);
        }
        return true;
    }

    function getInfo(&$vars, &$var, &$info)
    {
        /* If any component is empty consider it a bad date and return the
         * default. */
        $value = $var->getValue($vars);
        if ($this->emptyDateArray($value) == 1 || $this->emptyTimeArray($value)) {
            $info = $var->getDefault();
            return;
        }

        $date = $this->getDateOb($value);
        $time = $this->getTimeOb($value);
        $date->hour = $time->hour;
        $date->min = $time->min;
        $date->sec = $time->sec;
        if (is_null($this->format_in)) {
            $info = $date->timestamp();
        } else {
            $info = $date->strftime($this->format_in);
        }
    }

    function __get($property)
    {
        if ($property == 'show_seconds') {
            return $this->_hms->$property;
        } else {
            return $this->_mdy->$property;
        }
    }

    function __set($property, $value)
    {
        if ($property == 'show_seconds') {
            $this->_hms->$property = $value;
        } else {
            $this->_mdy->$property = $value;
        }
    }

    function checktime($hour, $minute, $second)
    {
        return $this->_hms->checktime($hour, $minute, $second);
    }

    function getTimeOb($time_in)
    {
        return $this->_hms->getTimeOb($time_in);
    }

    function getTimeParts($time_in)
    {
        return $this->_hms->getTimeParts($time_in);
    }

    function emptyTimeArray($time)
    {
        return $this->_hms->emptyTimeArray($time);
    }

    function emptyDateArray($date)
    {
        return $this->_mdy->emptyDateArray($date);
    }

    function getDateParts($date_in)
    {
        return $this->_mdy->getDateParts($date_in);
    }

    function getDateOb($date_in)
    {
        return $this->_mdy->getDateOb($date_in);
    }

    function formatDate($date)
    {
        if ($date === null) {
            return '';
        }
        return $this->_mdy->formatDate($date);
    }

    function about()
    {
        return array(
            'name' => _("Date and time selection"),
            'params' => array(
                'start_year' => array('label' => _("Start year"),
                                      'type'  => 'int'),
                'end_year'   => array('label' => _("End year"),
                                      'type'  => 'int'),
                'picker'     => array('label' => _("Show picker?"),
                                      'type'  => 'boolean'),
                'format_in'  => array('label' => _("Storage format"),
                                      'type'  => 'text'),
                'format_out' => array('label' => _("Display format"),
                                      'type'  => 'text'),
                'seconds'    => array('label' => _("Show seconds?"),
                                      'type'  => 'boolean')));
    }

}

class Horde_Form_Type_colorpicker extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if ($var->isRequired() && empty($value)) {
            $message = _("This field is required.");
            return false;
        }

        if (empty($value) || preg_match('/^#([0-9a-z]){6}$/i', $value)) {
            return true;
        }

        $message = _("This field must contain a color code in the RGB Hex format, for example '#1234af'.");
        return false;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Colour selection"));
    }

}

class Horde_Form_Type_sorter extends Horde_Form_Type {

    var $_instance;
    var $_values;
    var $_size;
    var $_header;

    function init($values, $size = 8, $header = '')
    {
        static $horde_sorter_instance = 0;

        /* Get the next progressive instance count for the horde
         * sorter so that multiple sorters can be used on one page. */
        $horde_sorter_instance++;
        $this->_instance = 'horde_sorter_' . $horde_sorter_instance;
        $this->_values = $values;
        $this->_size   = $size;
        $this->_header = $header;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    function getOptions($keys = null)
    {
        $html = '';
        if ($this->_header) {
            $html .= '<option value="">' . htmlspecialchars($this->_header) . '</option>';
        }

        if (empty($keys)) {
            $keys = array_keys($this->_values);
        } else {
            $keys = explode("\t", $keys['array']);
        }
        foreach ($keys as $sl_key) {
            $html .= '<option value="' . $sl_key . '">' . htmlspecialchars($this->_values[$sl_key]) . '</option>';
        }

        return $html;
    }

    function getInfo($vars, &$var, &$info)
    {
        $value = $vars->get($var->getVarName());
        $info = explode("\t", $value['array']);
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Sort order selection"),
            'params' => array(
                'values' => array('label' => _("Values"),
                                  'type'  => 'stringlist'),
                'size'   => array('label' => _("Size"),
                                  'type'  => 'int'),
                'header' => array('label' => _("Header"),
                                  'type'  => 'text')));
    }

}

class Horde_Form_Type_selectfiles extends Horde_Form_Type {

    /**
     * The text to use in the link.
     *
     * @var string
     */
    var $_link_text;

    /**
     * The style to use for the link.
     *
     * @var string
     */
    var $_link_style;

    /**
     *  Create the link with an icon instead of text?
     *
     * @var boolean
     */
    var $_icon;

    /**
     * Contains gollem selectfile selectionID
     *
     * @var string
     */
    var $_selectid;

    function init($selectid, $link_text = null, $link_style = '',
                  $icon = false)
    {
        $this->_selectid = $selectid;
        if (is_null($link_text)) {
            $link_text = _("Select Files");
        }
        $this->_link_text = $link_text;
        $this->_link_style = $link_style;
        $this->_icon = $icon;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    function getInfo($var, &$vars, &$info)
    {
        $value = $vars->getValue($var);
        $info = $GLOBALS['registry']->call('files/selectlistResults', array($value));
    }

    function about()
    {
        return array(
            'name' => _("File selection"),
            'params' => array(
                'selectid'   => array('label' => _("Id"),
                                      'type' => 'text'),
                'link_text'  => array('label' => _("Link text"),
                                      'type' => 'text'),
                'link_style' => array('label' => _("Link style"),
                                      'type' => 'text'),
                'icon'       => array('label' => _("Show icon?"),
                                      'type' => 'boolean')));
    }

}

class Horde_Form_Type_assign extends Horde_Form_Type {

    var $_leftValues;
    var $_rightValues;
    var $_leftHeader;
    var $_rightHeader;
    var $_size;
    var $_width;

    function init($leftValues, $rightValues, $leftHeader = '',
                  $rightHeader = '', $size = 8, $width = '200px')
    {
        $this->_leftValues = $leftValues;
        $this->_rightValues = $rightValues;
        $this->_leftHeader = $leftHeader;
        $this->_rightHeader = $rightHeader;
        $this->_size = $size;
        $this->_width = $width;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    function setValues($side, $values)
    {
        if ($side) {
            $this->_rightValues = $values;
        } else {
            $this->_leftValues = $values;
        }
    }

    function getHeader($side)
    {
        return $side ? $this->_rightHeader : $this->_leftHeader;
    }

    function getOptions($side, $formname, $varname)
    {
        $html = '';
        $headers = false;
        if ($side) {
            $values = $this->_rightValues;
            if (!empty($this->_rightHeader)) {
                $values = array('' => $this->_rightHeader) + $values;
                $headers = true;
            }
        } else {
            $values = $this->_leftValues;
            if (!empty($this->_leftHeader)) {
                $values = array('' => $this->_leftHeader) + $values;
                $headers = true;
            }
        }

        foreach ($values as $key => $val) {
            $html .= '<option value="' . htmlspecialchars($key) . '"';
            if ($headers) {
                $headers = false;
            } else {
                $html .= ' ondblclick="Horde_Form_Assign.move(\'' . $formname . '\', \'' . $varname . '\', ' . (int)$side . ');"';
            }
            $html .= '>' . htmlspecialchars($val) . '</option>';
        }

        return $html;
    }

    function getInfo($vars, &$var, &$info)
    {
        $value = $vars->get($var->getVarName() . '__values');
        if (strpos($value, "\t\t") === false) {
            $left = $value;
            $right = '';
        } else {
            list($left, $right) = explode("\t\t", $value);
        }
        if (empty($left)) {
            $info['left'] = array();
        } else {
            $info['left'] = explode("\t", $left);
        }
        if (empty($right)) {
            $info['right'] = array();
        } else {
            $info['right'] = explode("\t", $right);
        }
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Assignment columns"),
            'params' => array(
                'leftValues'  => array('label' => _("Left values"),
                                       'type'  => 'stringlist'),
                'rightValues' => array('label' => _("Right values"),
                                       'type'  => 'stringlist'),
                'leftHeader'  => array('label' => _("Left header"),
                                       'type'  => 'text'),
                'rightHeader' => array('label' => _("Right header"),
                                       'type'  => 'text'),
                'size'        => array('label' => _("Size"),
                                       'type'  => 'int'),
                'width'       => array('label' => _("Width in CSS units"),
                                       'type'  => 'text')));
    }

}

class Horde_Form_Type_creditcard extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        if (empty($value) && $var->isRequired()) {
            $message = _("This field is required.");
            return false;
        }

        if (!empty($value)) {
            /* getCardType() will also verify the checksum. */
            $type = $this->getCardType($value);
            if ($type === false || $type == 'unknown') {
                $message = _("This does not seem to be a valid card number.");
                return false;
            }
        }

        return true;
    }

    function getChecksum($ccnum)
    {
        $len = strlen($ccnum);
        if (!is_long($len / 2)) {
            $weight = 2;
            $digit = $ccnum[0];
        } elseif (is_long($len / 2)) {
            $weight = 1;
            $digit = $ccnum[0] * 2;
        }
        if ($digit > 9) {
            $digit = $digit - 9;
        }
        $i = 1;
        $checksum = $digit;
        while ($i < $len) {
            if ($ccnum[$i] != ' ') {
                $digit = $ccnum[$i] * $weight;
                $weight = ($weight == 1) ? 2 : 1;
                if ($digit > 9) {
                    $digit = $digit - 9;
                }
                $checksum += $digit;
            }
            $i++;
        }

        return $checksum;
    }

    function getCardType($ccnum)
    {
        $sum = $this->getChecksum($ccnum);
        $l = strlen($ccnum);

        // Screen checksum.
        if (($sum % 10) != 0) {
            return false;
        }

        // Check for Visa.
        if ((($l == 16) || ($l == 13)) &&
            ($ccnum[0] == 4)) {
            return 'visa';
        }

        // Check for MasterCard.
        if (($l == 16) &&
            ($ccnum[0] == 5) &&
            ($ccnum[1] >= 1) &&
            ($ccnum[1] <= 5)) {
            return 'mastercard';
        }

        // Check for Amex.
        if (($l == 15) &&
            ($ccnum[0] == 3) &&
            (($ccnum[1] == 4) || ($ccnum[1] == 7))) {
            return 'amex';
        }

        // Check for Discover (Novus).
        if (strlen($ccnum) == 16 &&
            substr($ccnum, 0, 4) == '6011') {
            return 'discover';
        }

        // If we got this far, then no card matched.
        return 'unknown';
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Credit card number"));
    }

}

class Horde_Form_Type_obrowser extends Horde_Form_Type {

    function isValid($var, $vars, $value, &$message)
    {
        return true;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array('name' => _("Relationship browser"));
    }

}

class Horde_Form_Type_dblookup extends Horde_Form_Type_enum {

    function init($dsn, $sql, $prompt = null)
    {
        $values = array();
        $db = DB::connect($dsn);
        if (!($db instanceof PEAR_Error)) {
            // Set DB portability options.
            switch ($db->phptype) {
            case 'mssql':
                $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS | DB_PORTABILITY_RTRIM);
                break;

            default:
                $db->setOption('portability', DB_PORTABILITY_LOWERCASE | DB_PORTABILITY_ERRORS);
                break;
            }

            $col = $db->getCol($sql);
            if (!($col instanceof PEAR_Error)) {
                $values = array_combine($col, $col);
            }
        }
        parent::init($values, $prompt);
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Database lookup"),
            'params' => array(
                'dsn' => array('label' => _("DSN (see http://pear.php.net/manual/en/package.database.db.intro-dsn.php)"),
                               'type'  => 'text'),
                'sql' => array('label' => _("SQL statement for value lookups"),
                               'type'  => 'text'),
                'prompt' => array('label' => _("Prompt text"),
                                  'type'  => 'text'))
            );
    }

}

class Horde_Form_Type_figlet extends Horde_Form_Type {

    var $_text;
    var $_font;

    function init($text, $font)
    {
        $this->_text = $text;
        $this->_font = $font;
    }

    function isValid($var, $vars, $value, &$message)
    {
        if (empty($value) && $var->isRequired()) {
            $message = _("This field is required.");
            return false;
        }

        if (Horde_String::lower($value) != Horde_String::lower($this->_text)) {
            $message = _("The text you entered did not match the text on the screen.");
            return false;
        }

        return true;
    }

    /**
     * Return info about field type.
     */
    function about()
    {
        return array(
            'name' => _("Figlet CAPTCHA"),
            'params' => array(
                'text' => array('label' => _("Text"),
                                'type'  => 'text'),
                'font' => array('label' => _("Figlet font"),
                                'type'  => 'text'))
            );
    }

}

class Horde_Form_Type_invalid extends Horde_Form_Type {

    var $message;

    function init($message)
    {
        $this->message = $message;
    }

    function isValid($var, $vars, $value, &$message)
    {
        return false;
    }

}

/**
 * This class represents a single form variable that may be rendered as one or
 * more form fields.
 *
 * @author  Robert E. Coyle <robertecoyle@hotmail.com>
 * @package Horde_Form
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
    function setFormOb($form)
    {
        $this->form = $form;
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
     * $action = new Horde_Form_Action_submit;
     * $v->setAction($action);
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
    function getType()
    {
        return $this->type;
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
        return $this->type->values;
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
        return ($this->type instanceof Horde_Form_Type_file);
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
    function getInfo($vars, &$info)
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
    function wasChanged($vars)
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
    function validate($vars, &$message)
    {
        if ($this->_arrayVal) {
            $vals = $this->getValue($vars);
            if (!is_array($vals)) {
                if ($this->required) {
                    $message = _("This field is required.");
                    return false;
                } else {
                    return true;
                }
            }
            foreach ($vals as $i => $value) {
                if ($value === null && $this->required) {
                    $message = _("This field is required.");
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
    function getValue($vars, $index = null)
    {
        if ($this->_arrayVal) {
            $name = str_replace('[]', '', $this->varName);
        } else {
            $name = $this->varName;
        }
        $value = $vars->getExists($name, $wasset);

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
