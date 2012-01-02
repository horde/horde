<?php
/**
 * A parser for a release information response from a PEAR server.
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
 * A parser for a release information response from a PEAR server.
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
class Horde_Pear_Rest_Release extends Horde_Xml_Element
{
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
    }

    /**
     * Return the package name.
     *
     * @return string The package name.
     */
    public function getName()
    {
        return (string)$this->p;
    }

    /**
     * Return the package channel.
     *
     * @return string The package channel.
     */
    public function getChannel()
    {
        return (string)$this->c;
    }

    /**
     * Return the release version.
     *
     * @return string The release version.
     */
    public function getVersion()
    {
        return (string)$this->v;
    }

    /**
     * Return the package license.
     *
     * @return string The package license.
     */
    public function getLicense()
    {
        return (string)$this->l;
    }

    /**
     * Return the package summary.
     *
     * @return string The package summary.
     */
    public function getSummary()
    {
        return (string)$this->s;
    }

    /**
     * Return the package description.
     *
     * @return string The package description.
     */
    public function getDescription()
    {
        return (string)$this->d;
    }

    /**
     * Return the release notes.
     *
     * @return string The release notes.
     */
    public function getNotes()
    {
        return (string)$this->n;
    }

    /**
     * Return the uri for downloading the package.
     *
     * @return string The URI.
     */
    public function getDownloadUri()
    {
        return $this->g . '.tgz';
    }
}