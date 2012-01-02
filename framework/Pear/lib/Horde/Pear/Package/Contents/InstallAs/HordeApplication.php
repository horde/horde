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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Contents_InstallAs_HordeApplication:: determines install
 * locations for Horde applications.
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
class Horde_Pear_Package_Contents_InstallAs_HordeApplication
implements Horde_Pear_Package_Contents_InstallAs
{
    /**
     * The package type.
     *
     * @var Horde_Pear_Package_Type
     */
    private $_type;

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Type $type The package type.
     */
    public function __construct(Horde_Pear_Package_Type $type)
    {
        $this->_type = $type;
    }

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
        case 'bin':
        case 'docs':
        case 'test':
            return join('/', $elements);
        case 'COPYING':
        case 'COPYING':
        case 'README':
            return substr($file, 1);
        default:
            return $this->_type->getName() . $file;
        }
    }
}