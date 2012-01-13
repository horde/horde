<?php
/**
 * Identifies the requested component based on an argument and delivers a
 * corresponding component instance.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Identifies the requested component based on an argument and delivers a
 * corresponding component instance.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Components
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Components
 */
class Components_Component_Identify
{
    /**
     * The active configuration.
     *
     * @var Components_Config
     */
    private $_config;

    /**
     * The list of available actions
     *
     * @var array
     */
    private $_actions;

    /**
     * The dependency handler.
     *
     * @var Components_Dependencies
     */
    private $_dependencies;

    /**
     * Constructor.
     *
     * @param Components_Config       $config       The active configuration.
     * @param array                   $actions      The list of available actions.
     * @param Components_Dependencies $dependencies The dependency handler.
     */
    public function __construct(
        Components_Config $config,
        $actions,
        Components_Dependencies $dependencies
    )
    {
        $this->_config        = $config;
        $this->_actions       = $actions;
        $this->_dependencies  = $dependencies;
    }

    /**
     * Inject the component selected based on the command arguments into the
     * configuration.
     *
     * @return NULL.
     */
    public function setComponentInConfiguration()
    {
        $arguments = $this->_config->getArguments();
        if (list($component, $path) = $this->_determineComponent($arguments)) {
            $this->_config->setComponent($component);
            $this->_config->setPath($path);
        }
    }

    /**
     * Determine the requested component.
     *
     * @param array $arguments The arguments.
     *
     * @return array Two elements: The selected component as
     *               Components_Component instance and optionally a string
     *               representing the path to the specified source component.
     */
    private function _determineComponent($arguments)
    {
        if (isset($arguments[0])) {
            if (in_array($arguments[0], $this->_actions['missing_argument'])) {
                return;
            }

            if ($this->_isPackageXml($arguments[0])) {
                $this->_config->shiftArgument();
                return array(
                    $this->_dependencies
                    ->getComponentFactory()
                    ->createSource(dirname($arguments[0])),
                    dirname($arguments[0])
                );
            }

            if (!in_array($arguments[0], $this->_actions['list'])) {
                if ($this->_isDirectory($arguments[0])) {
                    $this->_config->shiftArgument();
                    return array(
                        $this->_dependencies
                        ->getComponentFactory()
                        ->createSource($arguments[0]),
                        $arguments[0]
                    );
                }

                $options = $this->_config->getOptions();
                if (!empty($options['allow_remote'])) {
                    $result = $this->_dependencies
                        ->getComponentFactory()
                        ->getResolver()
                        ->resolveName(
                            $arguments[0],
                            'pear.horde.org',
                            $options
                        );
                    if ($result !== false) {
                        $this->_config->shiftArgument();
                        return array($result, '');
                    }
                }
                
                throw new Components_Exception(
                    sprintf(Components::ERROR_NO_ACTION_OR_COMPONENT, $arguments[0])
                );
            }
        }

        $cwd = getcwd();
        if ($this->_isDirectory($cwd) && $this->_containsPackageXml($cwd)) {
            return array(
                $this->_dependencies
                ->getComponentFactory()
                ->createSource($cwd),
                $cwd
            );
        }

        throw new Components_Exception(Components::ERROR_NO_COMPONENT);
    }

    /**
     * Checks if the provided directory is a directory.
     *
     * @param string $path The path to the directory.
     *
     * @return boolean True if it is a directory
     */
    private function _isDirectory($path)
    {
        return (!empty($path) && is_dir($path));
    }

    /**
     * Checks if the directory contains a package.xml file.
     *
     * @param string $path The path to the directory.
     *
     * @return boolean True if the directory contains a package.xml file.
     */
    private function _containsPackageXml($path)
    {
        return file_exists($path . '/package.xml');
    }

    /**
     * Checks if the file name is a package.xml file.
     *
     * @param string $path The path.
     *
     * @return boolean True if the provided file name points to a package.xml
     *                 file.
     */
    private function _isPackageXml($path)
    {
        if (basename($path) == 'package.xml' && file_exists($path)) {
            return true;
        }
        return false;
    }
}