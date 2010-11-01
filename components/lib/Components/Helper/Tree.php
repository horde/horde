<?php
/**
 * Components_Helper_Tree:: handles a tree of dependencies and takes the Horde
 * component layout into account.
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
 * Components_Helper_Tree:: handles a tree of dependencies and takes the Horde
 * component layout into account.
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
class Components_Helper_Tree
{
    /**
     * The factory for PEAR dependencies.
     *
     * @var Components_Pear_Factory
     */
    private $_factory;

    /**
     * The environment we establish the tree for.
     *
     * @var Components_Pear_InstallLocation
     */
    private $_environment;

    /**
     * The root path for the Horde package hierarchy.
     *
     * @var string
     */
    private $_root_path;

    /**
     * Constructor.
     *
     * @param Components_Pear_Factory         $factory     The factory for PEAR
     *                                                     dependencies.
     * @param Components_Pear_InstallLocation $environment The PEAR environment.
     * @param string                          $root_path   The basic root path for
     *                                                     Horde packages.
     *
     */
    public function __construct(
        Components_Pear_Factory $factory,
        Components_Pear_InstallLocation $environment,
        $root_path
    ) {
        $this->_factory = $factory;
        $this->_environment = $environment;
        $this->_root_path = $root_path;
    }

    /**
     * Install the tree of packages into the specified environment.
     *
     * @param string $package_file Path to the package file representing the element
     *                             at the root of the dependency tree.
     *
     * @return NULL
     */
    public function installTreeInEnvironment($package_file) {
        $this->_getHordeChildElement($package_file)
            ->installInTree(
                new Components_Helper_InstallationRun($this->_environment)
            );
    }

    /**
     * List the dependency tree for the specified root package element.
     *
     * @param string           $package_file Path to the package file representing
     *                                       the element at the root of the
     *                                       dependency tree.
     * @param Component_Output $output       The output handler.
     *
     * @return NULL
     */
    public function listDependencyTree(
        $package_file,
        Components_Output $output
    ) {
        $run = new Components_Helper_ListRun($output);
        $this->_getHordeChildElement($package_file)
            ->listDependencies($run, 0);
        $run->finish();
    }

    /**
     * Get the children for an element.
     *
     * @param array $dependencies The dependencies of a package to be
     *                            transformed in elements.
     * @return array The list of children elements.
     */
    public function getChildren(array $dependencies)
    {
        $children = array();
        foreach ($dependencies as $dependency) {
            $package_file = $this->_root_path . DIRECTORY_SEPARATOR
                 . $dependency['name'] . DIRECTORY_SEPARATOR . 'package.xml';
            if (!file_exists($package_file)) {
                $package_file = $this->_root_path . DIRECTORY_SEPARATOR
                    . 'framework' . DIRECTORY_SEPARATOR . $dependency['name']
                    . DIRECTORY_SEPARATOR . 'package.xml';
            }
            $children[] = $this->_getHordeChildElement(
                $package_file,
                isset($dependency['optional']) && $dependency['optional'] == 'no'
            );
        }
        return $children;
    }

    /**
     * Return a Horde child element.
     *
     * @param string  $package_file Path to the package file representing the
     *                              element at the root of the dependency tree.
     * @param boolean $required     Is this a required element?
     * @return NULL
     */
    private function _getHordeChildElement($package_file, $required = true)
    {
        return new Components_Helper_Tree_Element(
            $this->_factory->createPackageForEnvironment(
                $package_file, $this->_environment
            ),
            $package_file,
            $required,
            $this
        );
    }

    /**
     * Return the environment for this tree.
     *
     * @return Components_Pear_InstallLocation The installation environment.
     */
    public function getEnvironment()
    {
        return $this->_environment;
    }
}