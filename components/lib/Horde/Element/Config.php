<?php
/**
 * Horde_Element_Config:: interface represents a configuration type for the Horde
 * element tool.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * Horde_Element_Config:: interface represents a configuration type for the Horde
 * element tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
interface Horde_Element_Config
{
    /**
     * Provide each configuration handler with the list of supported modules.
     *
     * @param Horde_Element_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Horde_Element_Modules $modules);

    /**
     * Return the options provided by the configuration handlers.
     *
     * @return array An array of options.
     */
    public function getOptions();

    /**
     * Return the arguments provided by the configuration handlers.
     *
     * @return array An array of arguments.
     */
    public function getArguments();
}