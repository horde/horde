<?php
/**
 * Components_Helper_Tree:: handles a tree of dependencies.
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
 * Components_Helper_Tree:: handles a tree of dependencies.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
     * The root handler for the Horde package hierarchy.
     *
     * @var Components_Helper_Root
     */
    private $_root;

    /**
     * Constructor.
     *
     * @param Components_Pear_Factory         $factory     The factory for PEAR
     *                                                     dependencies.
     * @param Components_Pear_InstallLocation $environment The PEAR environment.
     * @param Components_Helper_Root          $root        The root handler for
     *                                                     Horde packages.
     *
     */
    public function __construct(
        Components_Pear_Factory $factory,
        Components_Pear_InstallLocation $environment,
        Components_Helper_Root $root
    ) {
        $this->_factory = $factory;
        $this->_environment = $environment;
        $this->_root = $root;
    }

    /**
     * Install the tree of packages into the specified environment.
     *
     * @param string           $package_file Path to the package file representing the element
     *                                       at the root of the dependency tree.
     * @param Component_Output $output       The output handler.
     * @param array            $options      Options for this installation.
     *
     * @return NULL
     */
    public function installTreeInEnvironment(
        $package_file,
        Components_Output $output,
        array $options
    ) {
        $run = new Components_Helper_InstallationRun($this->_environment, $this, $output, $options);
        $run->install($this->_getHordeChildElement($package_file));
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
        $run = new Components_Helper_ListRun($output, $this);
        $run->listTree($this->_getHordeChildElement($package_file));
        $run->finish();
    }

    /**
     * Get the children for an element.
     *
     * @param array $dependencies The dependencies of a package to be
     *                            transformed in elements.
     * @return array The list of children.
     */
    public function getChildren(array $dependencies)
    {
        $children = array();
        foreach ($dependencies as $dependency) {
            $children[$dependency->key()] = $this->_getHordeChildElement(
                $this->_root->getPackageXml($dependency->name())
            );
        }
        return $children;
    }

    /**
     * Return a Horde child element.
     *
     * @param string  $package_file Path to the package file representing the
     *                              element at the root of the dependency tree.
     *
     * @return Components_Pear_Package The child package.
     */
    private function _getHordeChildElement($package_file)
    {
        return $this->_factory->createPackageForEnvironment(
            $package_file, $this->_environment
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

    /**
     * Return the root handler for the horde repository.
     *
     * @return Components_Helper_Root The root handler.
     */
    public function getRoot()
    {
        return $this->_root;
    }
}