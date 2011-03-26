<?php
/**
 * Horde_Pear_Package_Contents_InstallAs_HordeApplication:: determines install
 * locations for Horde applications.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Contents_InstallAs_HordeApplication:: determines install
 * locations for Horde applications.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Contents_InstallAs_HordeApplication
implements Horde_Pear_Package_Contents_InstallAs
{
    /**
     * Tell which location the specified file should be installed to.
     *
     * @param string $file The file name.
     *
     * @return string The install location for the file.
     */
    public function getInstallAs($file)
    {
        return $file;
    }
}