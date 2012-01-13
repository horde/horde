<?php
/**
 * A parser for a package list response from a PEAR server.
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
 * A parser for a package list response from a PEAR server.
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
class Horde_Pear_Rest_PackageList extends Horde_Xml_Element_List
{
    /**
     * The list of packages.
     *
     * @var array
     */
    private $_packages;

    /**
     * Constructor.
     *
     * @param resource|string $xml The XML document received from the server.
     */
    public function __construct($xml)
    {
        if (is_resource($xml)) {
            rewind($xml);
            $xml = stream_get_contents($xml);
        }
        parent::registerNamespace('xlink', 'http://www.w3.org/1999/xlink');
        parent::__construct($xml);
        $this->_packages = $this->_buildPackageList();
    }

    /**
     * Build the list of elements.
     *
     * @return array The list of elements.
     */
    protected function _buildListItemCache()
    {
        $entries = array();
        foreach ($this->_element->getElementsByTagName('p') as $child) {
            $entries[] = $child;
        }
        return $entries;
    }

    /**
     * Build the list of packages.
     *
     * @return array The list of elements.
     */
    private function _buildPackageList()
    {
        $packages = array();
        foreach ($this->p as $p) {
            $packages[(string)$p] = $p['xlink:href'];
        }
        return $packages;
    }

    /**
     * Return the list of package names.
     *
     * @return array The package names.
     */
    public function listPackages()
    {
        return array_keys($this->_packages);
    }

    /**
     * Return the list of packages.
     *
     * @return array The packages.
     */
    public function getPackages()
    {
        return $this->_packages;
    }

    /**
     * Return the link for additional information on the specified package.
     *
     * @param string $package The package name.
     *
     * @return string The URL for additional information.
     */
    public function getPackageLink($package)
    {
        if (isset($this->_packages[$package])) {
            return $this->_packages[$package];
        } else {
            throw new Horde_Pear_Exception(
                sprintf('No package named "%s" available!', $package)
            );
        }
    }
}