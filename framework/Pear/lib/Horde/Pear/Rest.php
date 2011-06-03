<?php
/**
 * REST access to a PEAR server.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 * @link     http://pear.php.net/manual/en/core.rest.php
 */

/**
 * REST access to a PEAR server.
 *
 * This implements a subset of the REST methods detailed in
 * http://pear.php.net/manual/en/core.rest.php
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
 * @link     http://pear.php.net/manual/en/core.rest.php
 */
class Horde_Pear_Rest
{
    /**
     * The HTTP client.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The base URL for the remote PEAR server
     *
     * @var string
     */
    private $_url;

    /**
     * Constructor.
     *
     * @param Horde_Http_Client $client The HTTP client.
     * @param string            $url    The URL for the remote PEAR server.
     */
    public function __construct(Horde_Http_Client $client, $url)
    {
        $this->_client = $client;
        $this->_url = $url;
    }

    /**
     * Return the complete list of packages on the server.
     *
     * @return resource A stream with the package list.
     */
    public function fetchPackageList()
    {
        return $this->_get($this->_url . '/rest/p/packages.xml');
    }

    /**
     * Return the information on a specific package from the server.
     *
     * @param string $package The name of the package to retrieve information
     *                        for.
     *
     * @return resource A stream with the package information.
     */
    public function fetchPackageInformation($package)
    {
        return $this->_get(
            $this->_url . '/rest/p/' . strtolower($package) . '/info.xml'
        );
    }

    /**
     * Return the release list for a specific package from the server.
     *
     * @param string $package The name of the package to retrieve the releases
     *                        for.
     *
     * @return resource A stream with the package release information.
     */
    public function fetchPackageReleases($package)
    {
        return $this->_get(
            $this->_url . '/rest/r/' . strtolower($package) . '/allreleases.xml'
        );
    }

    /**
     * Return the latest releases for a specific package.
     *
     * @param string $package The name of the package to retrieve the latest
     *                        releases for.
     *
     * @return array A list of latest releases per level of stability.
     */
    public function fetchLatestPackageReleases($package)
    {
        $base = $this->_url . '/rest/r/' . strtolower($package);
        return array(
            'stable' => $this->_read($base . '/stable.txt'),
            'alpha'  => $this->_read($base . '/alpha.txt'),
            'beta'   => $this->_read($base . '/beta.txt'),
            'devel'  => $this->_read($base . '/devel.txt'),
        );
    }

    /**
     * Return the release information for a specific package version from the
     * server.
     *
     * @param string $package The name of the package.
     * @param string $version The version of the release.
     *
     * @return resource A stream with the package release information.
     */
    public function fetchReleaseInformation($package, $version)
    {
        return $this->_get(
            $this->_url . '/rest/r/' . strtolower($package) . '/' . $version . '.xml'
        );
    }

    /**
     * Return the package.xml for a specific release from the server.
     *
     * @param string $package The name of the package.
     * @param string $version The version of the release.
     *
     * @return resource A stream with the package.xml information.
     */
    public function fetchReleasePackageXml($package, $version)
    {
        return $this->_get(
            $this->_url . '/rest/r/' . strtolower($package) . '/package.' . $version . '.xml'
        );
    }

    /**
     * Fetch the provided URL as stream.
     *
     * @param string $url The URL.
     *
     * @return resource The response as stream.
     */
    private function _get($url)
    {
        return $this->_client->get($url)->getStream();
    }

    /**
     * Fetch the provided URL as string.
     *
     * @param string $url The URL.
     *
     * @return string The response as string.
     */
    private function _read($url)
    {
        $response = $this->_client->get($url);
        if ($response->code === 200) { 
            return $this->_client->get($url)->getBody();
        } else {
            return false;
        }
    }
}