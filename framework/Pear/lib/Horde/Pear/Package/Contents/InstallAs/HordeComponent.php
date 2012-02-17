<?php
/**
 * Horde_Pear_Package_Contents_InstallAs_HordeComponent:: determines install
 * locations for Horde components.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Contents_InstallAs_HordeComponent:: determines install
 * locations for Horde components.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Contents_InstallAs_HordeComponent
implements Horde_Pear_Package_Contents_InstallAs
{
    /**
     * Tell which location the specified file should be installed to.
     *
     * @param string $file     The file name.
     * @param string $package  The package name.
     *
     * @return string The install location for the file.
     */
    public function getInstallAs($file, $package)
    {
        $elements = explode('/', substr($file, 1));
        $basedir = array_shift($elements);
        switch ($basedir) {
        case 'COPYING':
        case 'examples':
        case 'js':
        case 'locale':
            return substr($file, 1);
        case 'migration':
            return $basedir . '/' . basename($file);
        case 'doc':
            foreach (explode('_', $package) as $dir) {
                if ($elements[0] == $dir) {
                    array_shift($elements);
                } else {
                    break;
                }
            }
            // Fall through.
        default:
            return join('/', $elements);
        }
    }
}