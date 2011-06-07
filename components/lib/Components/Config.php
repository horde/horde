<?php
/**
 * Components_Config:: interface represents a configuration type for the Horde
 * component tool.
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
 * Components_Config:: interface represents a configuration type for the Horde
 * component tool.
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
interface Components_Config
{
    /**
     * Set an additional option value.
     *
     * @param string $key   The option to set.
     * @param string $value The value of the option.
     *
     * @return NULL
     */
    public function setOption($key, $value);

    /**
     * Return the options provided by the configuration handlers.
     *
     * @return array An array of options.
     */
    public function getOptions();

    /**
     * Shift an element from the argument list.
     *
     * @return mixed The shifted element.
     */
    public function shiftArgument();

    /**
     * Unshift an element to the argument list.
     *
     * @param string $element The element to unshift.
     *
     * @return NULL
     */
    public function unshiftArgument($element);

    /**
     * Return the arguments provided by the configuration handlers.
     *
     * @return array An array of arguments.
     */
    public function getArguments();

    /**
     * Set the path to the component directory.
     *
     * @param Components_Component $component The path to the component directory.
     * @return NULL
     */
    public function setComponent(Components_Component $component);

    /**
     * Return the selected component.
     *
     * @return Components_Component The selected component.
     */
    public function getComponent();
}