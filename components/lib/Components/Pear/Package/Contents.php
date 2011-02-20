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
     * Provides access to the contents handler.
     *
     * @var Components_Pear_Package_Contents_Factory
     */
    private $_contents_factory;

    /**
     * Constructor.
     *
     * @param Components_Pear_Package_Tasks            $tasks    A tasks helper.
     * @param Components_Pear_Package_Filelist_Factory $filelist Creates the filelist handler.
     * @param Components_Pear_Package_Contents_Factory $contents Creates the contents handler.
     */
    public function __construct(
        Components_Pear_Package_Tasks $tasks,
        Components_Pear_Package_Filelist_Factory $filelist,
        Components_Pear_Package_Contents_Factory $contents
    ) {
        $this->_tasks = $tasks;
        $this->_filelist_factory = $filelist;
        $this->_contents_factory = $contents;
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
     * Return an updated package description.
     *
     * @return PEAR_PackageFileManager2 The updated package.
     */
    public function update()
    {
        $generator = $this->_contents_factory->create($this->getPackage());
        $this->getPackage()->clearContents('/');
        $this->getPackage()->_struc = $generator->getFileList();
        $this->getPackage()->_getSimpleDirTag($this->getPackage()->_struc);

        // Workaround for [#9364] Components notices and fatal error
        if (empty($this->getPackage()->_packageInfo['changelog'])) {
            unset($this->getPackage()->_packageInfo['changelog']);
        }
        $this->_filelist_factory->create($this->getPackage())->update();
    }
}