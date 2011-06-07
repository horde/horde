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
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Components
 */

/**
 * Identifies the requested component based on an argument and delivers a
 * corresponding component instance.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
        if ($component = $this->_determineComponent($arguments)) {
            $this->_config->setComponent($component, isset($arguments[0]));
        }
    }

    /**
     * Determine the requested component.
     *
     * @param array $arguments The arguments.
     *
     * @return Components_Component The selected component.
     */
    private function _determineComponent($arguments)
    {
        if (isset($arguments[0])) {
            if (in_array($arguments[0], $this->_actions['missing_argument'])) {
                return;
            }

            if ($this->_isPackageXml($arguments[0])) {
                return $this->_dependencies
                    ->getComponentFactory()
                    ->createSource(dirname($arguments[0]));
            }

            if (!in_array($arguments[0], $this->_actions['list'])) {
                if ($this->_containsPackageXml($arguments[0])) {
                    return $this->_dependencies
                        ->getComponentFactory()
                        ->createSource($arguments[0]);
                }

                $options = $this->_config->getOptions();
                if (!empty($options['allow_remote'])) {
                    return $this->_dependencies
                        ->getComponentFactory()
                        ->createRemote(
                            $this->_dependencies->getRemote()
                            ->getLatestDownloadUri($arguments[0])
                        );
                }
                
                throw new Components_Exception(
                    sprintf(Components::ERROR_NO_ACTION_OR_COMPONENT, $arguments[0])
                );
            }
        }

        $cwd = getcwd();
        if ($this->_isDirectory($cwd) && $this->_containsPackageXml($cwd)) {
            return $this->_dependencies
                ->getComponentFactory()
                ->createSource($cwd);
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