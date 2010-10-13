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
     * @param Components_Helper_Tree  $tree         The parent tree for this child.
     */
    public function __construct(
        Components_Pear_Package $package,
        $package_file,
        Components_Helper_Tree $tree
    ) {
        $this->_package = $package;
        $this->_package_file = $package_file;
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
}