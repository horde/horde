<?php
/**
 * Components_Pear_Package_Contents_Factory:: handles the different contents
 * list generators.
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
 * Components_Pear_Package_Contents_Factory:: handles the different content list
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
class Components_Pear_Package_Contents_Factory
{
    /**
     * Create the contents handler.
     *
     * @param PEAR_PackageFile_v2_rw $package The package.
     *
     * @return Components_Pear_Package_Contents_List The content handler.
     */
    public function create(PEAR_PackageFile_v2_rw $package)
    {
        $root = new Components_Helper_Root(
            $package->_options['packagedirectory']
        );
        return new Components_Pear_Package_Contents_List(
            $package->_options['packagedirectory'],
            new Components_Pear_Package_Contents_Ignore(
                $root->fetchGitIgnore()
            )
        );
    }
}