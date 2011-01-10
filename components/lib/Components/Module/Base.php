<?php
/**
 * Components_Module_Base:: provides core functionality for the
 * different modules.
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
 * Components_Module_Base:: provides core functionality for the
 * different modules.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
abstract class Components_Module_Base
implements Components_Module
{
    /**
     * The dependency provider.
     *
     * @var Components_Dependencies
     */
    protected $_dependencies;

    /**
     * Constructor.
     *
     * @param Components_Dependencies $dependencies The dependency provider.
     */
    public function __construct(Components_Dependencies $dependencies)
    {
        $this->_dependencies = $dependencies;
    }

    /**
     * Get the usage description for this module.
     *
     * @return string The description.
     */
    public function getUsage()
    {
        return '';
    }

    /**
     * Get a set of base options that this module adds to the CLI argument
     * parser.
     *
     * @return array The options.
     */
    public function getBaseOptions()
    {
        return array();
    }

    /**
     * Indicate if the module provides an option group.
     *
     * @return boolean True if an option group should be added.
     */
    public function hasOptionGroup()
    {
        return true;
    }

    /**
     * Validate that there is a package.xml file in the provided directory.
     *
     * @param string $directory The package directory.
     *
     * @return NULL
     */
    protected function requirePackageXml($directory)
    {
        if (!file_exists($directory . '/package.xml')) {
            throw new Components_Exception(sprintf('There is no package.xml at %s!', $directory));
        }
    }
}