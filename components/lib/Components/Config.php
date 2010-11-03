<?php
/**
 * Components_Config:: interface represents a configuration type for the Horde
 * element tool.
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
 * element tool.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
     * Provide each configuration handler with the list of supported modules.
     *
     * @param Components_Modules $modules A list of modules.
     * @return NULL
     */
    public function handleModules(Components_Modules $modules);

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

    /**
     * Return the first argument - the package directory - provided by the
     * configuration handlers.
     *
     * @return string The package directory.
     */
    public function getPackageDirectory();
}