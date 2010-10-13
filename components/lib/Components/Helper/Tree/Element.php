<?php
/**
 * Components_Helper_Tree_Element:: provides utility methods for a single
 * element of the tree.
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
 * Components_Helper_Tree_Element:: provides utility methods for a single
 * element of the tree.
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
class Components_Helper_Tree_Element
{
    /**
     * The package represented by this element.
     *
     * @var Components_Pear_Package
     */
    private $_package;

    /**
     * The path to the package file defining this element.
     *
     * @var string
     */
    private $_package_file;

    /**
     * Is this a required element?
     *
     * @var boolean
     */
    private $_required;

    /**
     * The parent tree for this child.
     *
     * @var Components_Helper_Tree
     */
    private $_tree;

    /**
     * Constructor.
     *
     * @param Components_Pear_Package $package      The root of the dependency tree.
     * @param string                  $package_file The path to the package file.
     * @param boolean                 $required     Is this a required element?
     * @param Components_Helper_Tree  $tree         The parent tree for this child.
     */
    public function __construct(
        Components_Pear_Package $package,
        $package_file,
        $required,
        Components_Helper_Tree $tree
    ) {
        $this->_package = $package;
        $this->_package_file = $package_file;
        $this->_required = $required;
        $this->_tree = $tree;
    }

    /**
     * Install the tree of packages into the environment.
     *
     * @param Components_Helper_InstallationRun $run The current installation run.
     *
     * @return NULL
     */
    public function installInTree(Components_Helper_InstallationRun $run)
    {
        $run->installChannelsOnce($this->_package->listAllRequiredChannels());
        foreach ($this->_package->listAllExternalDependencies() as $dependency) {
            $run->installExternalPackageOnce(
                $dependency['channel'], $dependency['name']
            );
        }
        foreach (
            $this->_tree->getChildren(
                $this->_package->listAllHordeDependencies()
            ) as $child
        ) {
            $child->installInTree($run);
        }
        $run->installHordePackageOnce($this->_package_file);
    }

    /**
     * List the dependency tree for this package.
     *
     * @param Components_Helper_ListRun $run    The current listing run.
     * @param int                       $level  The current list level.
     * @param string                    $parent The name of the parent element.
     *
     * @return NULL
     */
    public function listDependencies(
        Components_Helper_ListRun $run,
        $level,
        $parent = ''
    ) {
        if ($run->listHordePackage($this->_package, $level, $parent, $this->_required)) {
            foreach ($this->_package->listAllExternalDependencies() as $dependency) {
                $run->listExternalPackage($dependency, $level + 1);
            }
            foreach (
                $this->_tree->getChildren(
                    $this->_package->listAllHordeDependencies()
                ) as $child
            ) {
                $child->listDependencies($run, $level + 1, $this->_package->getName());
            }
        }
    }

}