<?php
/**
 * Components_Config_Application:: provides a wrapper that provides application
 * specific configuration values by combining defaults and options provided at
 * runtime.
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
 * Components_Config_Application:: provides a wrapper that provides application
 * specific configuration values by combining defaults and options provided at
 * runtime.
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
class Components_Config_Application
{
    /**
     * The generic configuration handler.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * Constructor.
     *
     * @param Components_Config $config The generic configuration handler.
     */
    public function __construct(
        Components_Config $config
    ) {
        $this->_config = $config;
    }

    /**
     * Return the path to the template directory
     *
     * @return string The path to the template directory.
     */
    public function getTemplateDirectory()
    {
        $options = $this->_config->getOptions();
        if (!isset($options['templatedir'])) {
            return Components_Constants::getDataDirectory();
        } else {
            return $options['templatedir'];
        }
    }
}
