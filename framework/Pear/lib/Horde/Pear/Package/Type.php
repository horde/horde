<?php
/**
 * Horde_Pear_Package_Type:: defines a helper that identifies a package type.
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
 * Horde_Pear_Package_Type:: defines a helper that identifies a package type.
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
interface Horde_Pear_Package_Type
{
    /**
     * Return the path to the root of the package.
     *
     * @return string The path to the root.
     */
    public function getRootPath();

    /**
     * Return the path to the package.xml file for the package.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXmlPath();

    /**
     * Return the ignore handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Ignore The ignore handler.
     */
    public function getIgnore();

    /**
     * Return the include handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Include The include handler.
     */
    public function getInclude();

    /**
     * Return the role handler for this package.
     *
     * @return Horde_Pear_Package_Contents_Role The role handler.
     */
    public function getRole();

    /**
     * Return the install-as handler for this package.
     *
     * @return Horde_Pear_Package_Contents_InstallAs The install-as handler.
     */
    public function getInstallAs();
}