<?php
/**
 * Components_Pear_Package_Filelist_Factory:: handles the different file list
 * generators.
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
 * Components_Pear_Package_Filelist_Factory:: handles the different file list
 * generators.
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
class Components_Pear_Package_Filelist_Factory
{
    /**
     * Create the filelist handler.
     *
     * @param PEAR_PackageFile_v2_rw $package The package.
     *
     * @return Components_Pear_Package_Filelist The file list handler.
     */
    public function create(PEAR_PackageFile_v2_rw $package)
    {
        return new Components_Pear_Package_Filelist_Default($package);
    }
}