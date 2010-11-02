<?php
/**
 * Components_Pear_Package_Contents:: handles the PEAR package content.
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
 * Components_Pear_Package_Contents:: handles the PEAR package content.
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
class Components_Pear_Package_Contents
{
    /**
     * The package to work on.
     *
     * @var PEAR_PackageFileManager2
     */
    private $_package;

    /**
     * A tasks helper.
     *
     * @var Components_Pear_Package_Tasks
     */
    private $_tasks;

    /**
     * Provides access to the filelist handler.
     *
     * @var Components_Pear_Package_Filelist_Factory
     */
    private $_filelist_factory;

    /**
     * Constructor.
     *
     * @param Components_Pear_Package_Tasks            $tasks   A tasks helper.
     * @param Components_Pear_Package_Filelist_Factory $factory Creates the filelist handler.
     */
    public function __construct(
        Components_Pear_Package_Tasks $tasks,
        Components_Pear_Package_Filelist_Factory $factory
    ) {
        $this->_tasks = $tasks;
        $this->_filelist_factory = $factory;
    }

    /**
     * Set the package that should be handled.
     *
     * @param PEAR_PackageFileManager2 $package The package to work on.
     *
     * @return NULL
     */
    public function setPackage(PEAR_PackageFileManager2 $package)
    {
        $this->_package = $package;
    }

    /**
     * Set the package that should be handled.
     *
     * @param PEAR_PackageFileManager2 $package The package to work on.
     *
     * @return NULL
     */
    public function getPackage()
    {
        if (empty($this->_package)) {
            throw new Components_Exception('Set the package first!');
        }
        return $this->_package;
    }

    /**
     * Generate an updated contents listing.
     *
     * @return NULL
     */
    private function _generateContents()
    {
        $this->getPackage()->generateContents();
    }

    /**
     * Return an updated package description.
     *
     * @return PEAR_PackageFileManager2 The updated package.
     */
    public function update()
    {
        $taskfiles = $this->_tasks->denote($this->getPackage());
        $this->_generateContents();
        $this->_tasks->annotate($this->getPackage(), $taskfiles);

        $this->_filelist_factory->create($this->getPackage())->update();
    }
}