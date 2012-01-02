<?php
/**
 * Remote access to a PEAR server.
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
 * Remote access to a PEAR server.
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
class Horde_Pear_Remote
{
    /**
     * The instance accessing the REST interface of the PEAR server.
     *
     * @var Horde_Pear_Rest
     */
    private $_rest;

    /**
     * Constructor
     *
     * @param string          $server The server name.
     * @param Horde_Pear_Rest $rest   The accessor to the PEAR server rest
     *                                interface.
     */
    public function __construct($server = 'pear.horde.org',
                                Horde_Pear_Rest $rest = null)
    {
        if ($rest === null) {
            $this->_rest = new Horde_Pear_Rest(
                new Horde_Http_Client(),
                $server
            );
        } else {
            $this->_rest = $rest;
            $this->_rest->setServer($server);
        }
    }

    /**
     * Return the list of package names.
     *
     * @return array The package names.
     */
    public function listPackages()
    {
        $list = new Horde_Pear_Rest_PackageList(
            $this->_rest->fetchPackageList()
        );
        return $list->listPackages();
    }

    /**
     * Return the latest release for a specific package and stability.
     *
     * @param string $package The name of the package.
     * @param string $stability The stability of the release. Must be one of
     *                          "stable", "beta", "alpha", or "devel". The
     *                          default is "stable" If you explicitely set the
     *                          $stability parameter to NULL the method will
     *                          return the highest release version independent
     *                          of the stability.
     *
     * @return string|boolean The latest version for this stability or false if
     *                        no version with this stability level exists.
     */
    public function getLatestRelease($package, $stability = 'stable')
    {
        if ($stability === null) {
            return $this->_rest->fetchLatestRelease($package);
        } else {
            $result = $this->_rest->fetchLatestPackageReleases($package);
            return isset($result[$stability]) ? $result[$stability] : false;
        }
    }

    /**
     * Retrieve the dependencies for the specified package release.
     *
     * @param string $package  The package name.
     * @param string $version  The package version.
     *
     * @return array The package dependencies.
     */
    public function getDependencies($package, $version)
    {
        $deps = new Horde_Pear_Rest_Dependencies(
            $this->_rest->fetchPackageDependencies($package, $version)
        );
        return $deps->getDependencies();
    }

    /**
     * Return the package.xml for the specified release from the server.
     *
     * @param string $package The name of the package.
     * @param string $version The version of the release.
     *
     * @return Horde_Pear_Package_Xml The package.xml handler.
     */
    public function getPackageXml($package, $version)
    {
        return new Horde_Pear_Package_Xml(
            $this->_rest->fetchReleasePackageXml($package, $version)
        );
    }

    /**
     * Return the channel.xml from the server.
     *
     * @return string The content of the channel.xml file.
     */
    public function getChannel()
    {
        return $this->_rest->fetchChannelXml();
    }

    /**
     * Test if the specified release exists.
     *
     * @param string $package The name of the package.
     * @param string $version The version of the release.
     *
     * @return boolean True if the release exists.
     */
    public function releaseExists($package, $version)
    {
        return $this->_rest->releaseExists($package, $version);
    }

    /**
     * Retrieve the download location for the latest package release.
     *
     * @param string $package   The package name.
     * @param string $stability The stability the release should have. Must be one of
     *                          "stable", "beta", "alpha", or "devel". The
     *                          default is "stable" If you explicitely set the
     *                          $stability parameter to NULL the method will
     *                          return the download URI for the highest release
     *                          version independent of the stability.
     *
     * @return string The URI for downloading the release.
     *
     * @throws Horde_Pear_Exception In case there is no release for
     *                              this package with the specified
     *                              stability level.
     */
    public function getLatestDownloadUri($package, $stability = 'stable')
    {
        if ($latest = $this->getLatestRelease($package, $stability)) {
            return $this->_getRelease($package, $latest)->getDownloadUri();
        } else {
            throw new Horde_Pear_Exception(
                sprintf(
                    'No release of stability "%s" for package "%s".',
                    $stability,
                    $package
                )
            );
        }
    }

    /**
     * Retrieve the release details for the most stable package version.
     *
     * @param string $package   The package name.
     * @param string $stability The stability of the release. Must be one of
     *                          "stable", "beta", "alpha", or "devel". The
     *                          default is "stable" If you explicitely set the
     *                          $stability parameter to NULL the method will
     *                          return the details for the highest release
     *                          version independent of the stability.
     *
     * @return Horde_Pear_Rest_Release|boolean The details of the most stable
     *                                         release. Or false if no release
     *                                         was found.
     */
    public function getLatestDetails($package, $stability = 'stable')
    {
        $result = false;
        if ($latest = $this->getLatestRelease($package, $stability)) {
            return $this->_getRelease($package, $latest);
        }
        return $result;
    }

    /**
     * Return the release information wrapper for a specific package version
     * from the server.
     *
     * @param string $package The name of the package.
     * @param string $version The version of the release.
     *
     * @return Horde_Pear_Rest_Release The wrapper.
     */
    private function _getRelease($package, $version)
    {
        return new Horde_Pear_Rest_Release(
            $this->_rest->fetchReleaseInformation($package, $version)
        );
    }
}