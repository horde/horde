<?php
/**
 * A Horde_Form:: form that implements a user interface for the config
 * system.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Config_Form extends Horde_Form
{
    /**
     * Don't use form tokens for the configuration form - while
     * generating configuration info, things like the Token system
     * might not work correctly. This saves some headaches.
     *
     * @var boolean
     */
    protected $_useFormToken = false;

    /**
     * Contains the Horde_Config object that this form represents.
     *
     * @var Horde_Config
     */
    protected $_xmlConfig;

    /**
     * Contains the Horde_Variables object of this form.
     *
     * @var Horde_Variables
     */
    protected $_vars;

    /**
     * Constructor.
     *
     * @param Horde_Variables &$vars  The variables object of this form.
     * @param string $app             The name of the application that this
     *                                configuration form is for.
     */
    public function __construct(&$vars, $app)
    {
        parent::__construct($vars);

        $this->_xmlConfig = new Horde_Config($app);
        $this->_vars = &$vars;
        $config = $this->_xmlConfig->readXMLConfig();
        $this->addHidden('', 'app', 'text', true);
        $this->_buildVariables($config);
    }

    /**
     * Builds the form based on the specified level of the configuration tree.
     *
     * @param array $config   The portion of the configuration tree for that
     *                        the form fields should be created.
     * @param string $prefix  A string representing the current position
     *                        inside the configuration tree.
     */
    protected function _buildVariables($config, $prefix = '')
    {
        if (!is_array($config)) {
            return;
        }

        foreach ($config as $name => $configitem) {
            $prefixedname = empty($prefix) ? $name : $prefix . '|' . $name;
            $varname = str_replace('|', '__', $prefixedname);
            if ($configitem == 'placeholder') {
                continue;
            } elseif (isset($configitem['tab'])) {
                $this->setSection($configitem['tab'], $configitem['desc']);
            } elseif (isset($configitem['switch'])) {
                $selected = $this->_vars->getExists($varname, $wasset);
                $var_params = array();
                $select_option = true;
                if (is_bool($configitem['default'])) {
                    $configitem['default'] = $configitem['default'] ? 'true' : 'false';
                }
                foreach ($configitem['switch'] as $option => $case) {
                    $var_params[$option] = $case['desc'];
                    if ($option == $configitem['default']) {
                        $select_option = false;
                        if (!$wasset) {
                            $selected = $option;
                        }
                    }
                }

                $name = '$conf[' . implode('][', explode('|', $prefixedname)) . ']';
                $desc = $configitem['desc'];

                $v = &$this->addVariable($name, $varname, 'enum', true, false, $desc, array($var_params, $select_option));
                if (array_key_exists('default', $configitem)) {
                    $v->setDefault($configitem['default']);
                }
                if (!empty($configitem['is_default'])) {
                    $v->_new = true;
                }
                $v_action = Horde_Form_Action::factory('reload');
                $v->setAction($v_action);
                if (isset($selected) && isset($configitem['switch'][$selected])) {
                    $this->_buildVariables($configitem['switch'][$selected]['fields'], $prefix);
                }
            } elseif (isset($configitem['_type'])) {
                $required = (isset($configitem['required'])) ? $configitem['required'] : true;
                $type = $configitem['_type'];

                if (($type == 'header') || ($type == 'description')) {
                    $required = false;
                }

                $var_params = ($type == 'multienum' || $type == 'enum')
                    ? array($configitem['values'])
                    : array();

                if ($type == 'header' || $type == 'description') {
                    $name = $configitem['desc'];
                    $desc = null;
                } else {
                    $name = '$conf[' . implode('][', explode('|', $prefixedname)) . ']';
                    $desc = $configitem['desc'];
                    if ($type == 'php') {
                        $type = 'text';
                        $desc .= "\nEnter a valid PHP expression.";
                    }
                }

                $v = &$this->addVariable($name, $varname, $type, $required, false, $desc, $var_params);
                if (isset($configitem['default'])) {
                    $v->setDefault($configitem['default']);
                }
                if (!empty($configitem['is_default'])) {
                    $v->_new = true;
                }
            } else {
                $this->_buildVariables($configitem, $prefixedname);
            }
        }
    }

}
