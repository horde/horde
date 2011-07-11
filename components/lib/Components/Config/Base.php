<?php
/**
 * Components_Configs_Base:: provides common utilities for the configuration
 * handlers.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Components_Configs_Base:: provides common utilities for the configuration
 * handlers.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */
abstract class Components_Config_Base
implements Components_Config
{
    /**
     * Additional options.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Additional arguments.
     *
     * @var array
     */
    protected $_arguments = array();

    /**
     * The selected component.
     *
     * @var Components_Component
     */
    private $_component;

    /**
     * Set an additional option value.
     *
     * @param string $key   The option to set.
     * @param string $value The value of the option.
     *
     * @return NULL
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }

    /**
     * Return all options.
     *
     * @return Horde_Argv_Values The option values.
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Return the specified option.
     *
     * @param string $option The name of the option.
     *
     * @return mixed The option value or NULL if it is not defined.
     */
    public function getOption($option)
    {
        $options = $this->getOptions();
        if (isset($options[$option])) {
            return $options[$option];
        }
    }

    /**
     * Shift an element from the argument list.
     *
     * @return mixed The shifted element.
     */
    public function shiftArgument()
    {
        return array_shift($this->_arguments);
    }

    /**
     * Unshift an element to the argument list.
     *
     * @param string $element The element to unshift.
     *
     * @return NULL
     */
    public function unshiftArgument($element)
    {
        array_unshift($this->_arguments, $element);
    }

    /**
     * Return the arguments parsed from the command line.
     *
     * @return array An array of arguments.
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Set the path to the component directory.
     *
     * @param Components_Component $component The path to the component directory.
     * @return NULL
     */
    public function setComponent(Components_Component $component)
    {
        $this->_component = $component;
    }

    /**
     * Return the selected component.
     *
     * @return Components_Component The selected component.
     */
    public function getComponent()
    {
        if ($this->_component === null) {
            throw new Components_Exception(
                'The selected component has not been identified yet!'
            );
        }
        return $this->_component;
    }
}