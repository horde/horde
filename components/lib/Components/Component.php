<?php
/**
 * Represents a component.
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
 * Represents a component.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
interface Components_Component
{
    /**
     * Return the name of the component.
     *
     * @return string The component name.
     */
    public function getName();

    /**
     * Return the version of the component.
     *
     * @return string The component version.
     */
    public function getVersion();

    /**
     * Return the channel of the component.
     *
     * @return string The component channel.
     */
    public function getChannel();

    /**
     * Return the path to the local source directory.
     *
     * @return string The directory that contains the source code.
     */
    public function getPath();

    /**
     * Return the (base) name of the component archive.
     *
     * @return string The name of the component archive.
     */
    public function getArchiveName();

    /**
     * Return the path to the package.xml file of the component.
     *
     * @return string The path to the package.xml file.
     */
    public function getPackageXml();

    /**
     * Place the component source archive at the specified location.
     *
     * @param string $destination The path to write the archive to.
     *
     * @return NULL
     */
    public function placeArchive($destination);

    /**
     * Return a PEAR package representation for the component.
     *
     * @return PEAR_PackageFile The package representation.
     */
    public function getPackage();

    /**
     * Return the dependencies for the component.
     *
     * @return array The component dependencies.
     */
    public function getDependencies();

    /**
     * Validate that there is a package.xml file in the source directory.
     *
     * @return NULL
     */
    public function requirePackageXml();

    /**
     * Bail out if this is no local source.
     *
     * @return NULL
     */
    public function requireLocal();
}