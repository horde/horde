<?php
/**
 * The Horde_Cli_Modular_ModuleProvider:: class provides access to a single
 * module.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * The Horde_Cli_Modular_ModuleProvider:: class provides access to a single
 * module.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Cli_Modular
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Cli_Modular_ModuleProvider
{
    /**
     * Class prefix.
     *
     * @var string
     */
    private $_prefix;

    /**
     * Constructor.
     *
     * @param array $parameters Options for this instance.
     * <pre>
     *  - 
     * </pre>
     */
    public function __construct(array $parameters = null)
    {
        if (!isset($parameters['prefix'])) {
            throw new Horde_Cli_Modular_Exception(
                'Missing "prefix" parameter!'
            );
        }
        $this->_prefix = $parameters['prefix'];
    }

    /**
     * Get the usage string for the specified module.
     *
     * @param string $module The desired module.
     *
     * @return string The usage description for this module.
     *
     * @throws Horde_Cli_Modular_Exception In case the specified module does not
     * exist.
     */
    public function getUsage($module)
    {
        if (!class_exists($this->_prefix . $module)) {
            throw new Horde_Cli_Modular_Exception(
                sprintf(
                    'Invalid module %s!', $this->_prefix . $module
                )
            );
        }
        return call_user_func_array(
            array($this->_prefix . $module, 'getUsage'), array()
        );
    }
}