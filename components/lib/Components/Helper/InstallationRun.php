<?php
/**
 * Components_Helper_InstallationRun:: provides a utility that records what has
 * already happened during an installation run.
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
 * Components_Helper_InstallationRun:: provides a utility that records what has
 * already happened during an installation run.
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
class Components_Helper_InstallationRun
{
    /**
     * The environment we establish the tree for.
     *
     * @var Components_Pear_InstallLocation
     */
    private $_environment;

    /**
     * The tree for this run.
     *
     * @var Components_Helper_Tree
     */
    private $_tree;

    /**
     * The output handler.
     *
     * @param Component_Output
     */
    private $_output;

    /**
     * The options for this run.
     *
     * @param array
     */
    private $_options;

    /**
     * The list of channels already installed.
     *
     * @var array
     */
    private $_installed_channels = array();

    /**
     * The list of packages already installed.
     *
     * @var array
     */
    private $_installed_packages = array();

    /**
     * Constructor.
     *
     * @param Components_Pear_InstallLocation $environment The environment we
     *                                                     establish the tree for.
     * @param Components_Helper_Tree          $tree        The tree for this run.
     * @param Component_Output                $output      The output handler.
     * @param array                           $options     Options for this installation.
     */
    public function __construct(
        Components_Pear_InstallLocation $environment,
        Components_Helper_Tree $tree,
        Components_Output $output,
        array $options
    ) {
        $this->_environment = $environment;
        $this->_tree = $tree;
        $this->_output = $output;
        $this->_options = $options;
    }

    /**
     * Install a package into the environment.
     *
     * @param Components_Pear_Package $package The package that should be installed.
     * @param string                            $reason  Optional reason for adding the package.
     *
     * @return NULL
     */
    public function install(
        Components_Pear_Package $package,
        $reason = ''
    ) {
        $this->_installDependencies(
            $package->getDependencyHelper(),
            sprintf(' [required by %s]', $package->getName())
        );
        $this->_installHordePackageOnce($package->getPackageXml(), $reason);
    }

    /**
     * Install a list of dependencies.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param string                       $reason  Optional reason for adding the dependencies.
     *
     * @return NULL
     */
    private function _installDependencies(
        Components_Pear_Dependencies $dependencies,
        $reason = ''
    ) {
        $this->_installChannels($dependencies, $reason);
        $this->_installExternalPackages($dependencies, $reason);
        $this->_installHordeDependencies($dependencies, $reason);
    }

    /**
     * Ensure that the listed channels are available within the installation
     * environment. The channels are only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param string                       $reason       Optional reason for adding the channels.
     *
     * @return NULL
     */
    private function _installChannels(
        Components_Pear_Dependencies $dependencies,
        $reason = ''
    ) {
        foreach ($dependencies->listAllChannels() as $channel) {
            if (!in_array($channel, $this->_installed_channels)) {
                if (empty($this->_options['pretend'])) {
                    $this->_environment->provideChannel($channel, $reason);
                } else {
                    $this->_output->ok(
                        sprintf('Would install channel %s%s.', $channel, $reason)
                    );
                }
                $this->_installed_channels[] = $channel;
            }
        }
    }

    /**
     * Ensure that the external dependencies are available within the
     * installation environment.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param string                       $reason       Optional reason for adding the package.
     *
     * @return NULL
     */
    private function _installExternalPackages(
        Components_Pear_Dependencies $dependencies,
        $reason = ''
    ) {
        foreach (
            $dependencies->listExternalDependencies(
                $this->_options['include'], $this->_options['exclude']
            ) as $dependency
        ) {
            // Refrain from installing optional pecl packages
            if ($dependency->isPackage()) {
                $this->_installExternalPackageOnce(
                    $dependency, $reason
                );
            }
        }
    }

    /**
     * Ensure that the external package is available within the installation
     * environment. The package is only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param Components_Pear_Dependency $dependency The package dependency.
     * @param string                     $reason     Optional reason for adding the package.
     * @param array                      $to_add     The local packages currently being added.
     *
     * @return NULL
     */
    private function _installExternalPackageOnce(
        Components_Pear_Dependency $dependency,
        $reason = '',
        array &$to_add = null
    ) {
        if (!in_array($dependency->key(), $this->_installed_packages)) {
            if (empty($to_add)) {
                $to_add = array($dependency->key());
            }
            $dependencies = $this->_environment->identifyRequiredLocalDependencies(
                $dependency
            );
            /**
             * @todo This section won't really work as reading the package.xml
             * from an archive fails if the channels are unknown. So we never
             * get here. Sigh...
             */
            if ($dependencies) {
                $this->_installChannels(
                    $dependencies, sprintf(' [required by %s]', $dependency->name())
                );
                $list = $dependencies->listExternalDependencies(
                    $this->_options['include'], $this->_options['exclude']
                );
            } else {
                $list = array();
            }

            foreach ($list as $required) {
                if (in_array($required->key(), $to_add)) {
                    continue;
                }
                $to_add[] = $required->key();
                $this->_installExternalPackageOnce(
                    $required, sprintf(' [required by %s]', $dependency->name()), &$to_add
                );
            }
                
            if (empty($this->_options['pretend'])) {
                $this->_environment->addPackageFromPackage(
                    $dependency, $reason
                );
            } else {
                $this->_output->ok(
                    sprintf(
                        'Would install external package %s%s.',
                        $dependency->key(),
                        $reason
                    )
                );
            }
            $this->_installed_packages[] = $dependency->key();
        }
    }

    /**
     * Ensure that the horde package is available within the installation
     * environment. The package is only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param Components_Pear_Dependencies $dependencies The package dependencies.
     * @param string                       $reason       Optional reason for adding the package.
     *
     * @return NULL
     */
    private function _installHordeDependencies(
        Components_Pear_Dependencies $dependencies,
        $reason = ''
    ) {
        foreach (
            $this->_tree->getChildren(
                $dependencies->listHordeDependencies(
                    $this->_options['include'], $this->_options['exclude']
                )
            ) as $child
        ) {
            if (in_array($child->getName(), $this->_installed_packages)) {
                continue;
            }
            $this->_installed_packages[] = $child->getName();
            $this->install($child, $reason);
        }
    }
  
    /**
     * Ensure that the horde package is available within the installation
     * environment. The package is only going to be installed once during the
     * installation run represented by this instance.
     *
     * @param string $package_file The package file indicating which Horde
     *                             source package should be installed.
     * @param string $reason       Optional reason for adding the package.
     *
     * @return NULL
     */
    private function _installHordePackageOnce($package_file, $reason = '')
    {
        if (empty($this->_options['pretend'])) {
            if (empty($this->_options['symlink'])) {
                $this->_environment->addPackageFromSource(
                    $package_file, $reason
                );
            } else {
                $this->_environment->linkPackageFromSource(
                    $package_file, $reason
                );
            }
        } else {
            $this->_output->ok(
                sprintf(
                    'Would install package %s%s.',
                    $package_file,
                    $reason
                )
            );
        }
    }
}