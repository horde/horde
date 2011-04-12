<?php
/**
 * Horde_Pear_Package_Contents_InstallAs_Horde:: determines install locations
 * for the base Horde application.
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
 * Horde_Pear_Package_Contents_InstallAs_Horde:: determines install locations
 * for the base Horde application.
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
class Horde_Pear_Package_Contents_InstallAs_Horde
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
     * @param string $file The file name.
     *
     * @return string The install location for the file.
     */
    public function getInstallAs($file)
    {
        $elements = explode('/', substr($file, 1));
        $basedir = array_shift($elements);
        switch ($basedir) {
        case 'bin':
        case 'docs':
        case 'test':
            return join('/', $elements);
        case 'COPYING':
        case 'LICENSE':
        case 'README':
            return substr($file, 1);
        default:
            return $file;
        }
    }
}