<?php
/**
 * A parser for a release list response from a PEAR server.
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
 * A parser for a release list response from a PEAR server.
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
class Horde_Pear_Rest_Releases extends Horde_Xml_Element_List
{
    /**
     * The list of releases.
     *
     * @var array
     */
    private $_releases;

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
        $this->_releases = $this->_buildReleases();
    }

    /**
     * Build the list of elements.
     *
     * @return array The list of elements.
     */
    protected function _buildListItemCache()
    {
        $entries = array();
        foreach ($this->_element->getElementsByTagName('r') as $child) {
            $entries[] = $child;
        }
        return $entries;
    }

    /**
     * Build the list of releases.
     *
     * @return array The list of releases.
     */
    private function _buildReleases()
    {
        $releases = array();
        foreach ($this->r as $r) {
            $releases[(string)$r->v] = (string)$r->s;
        }
        return $releases;
    }

    /**
     * Return the list of releases.
     *
     * @return array The releases.
     */
    public function listReleases()
    {
        return array_keys($this->_releases);
    }

    /**
     * Return the list of releases.
     *
     * @return array The releases.
     */
    public function getReleases()
    {
        return $this->_releases;
    }

    /**
     * Return the stability for the provided release version.
     *
     * @param string $version The version to query.
     *
     * @return string The stability of the specified release.
     */
    public function getReleaseStability($version)
    {
        if (isset($this->_releases[$version])) {
            return $this->_releases[$version];
        } else {
            throw new Horde_Pear_Exception(
                sprintf('No release with version "%s" available!', $version)
            );
        }
    }
}