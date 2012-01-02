<?php
/**
 * A parser for a package information response from a PEAR server.
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
 * A parser for a package information response from a PEAR server.
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
class Horde_Pear_Rest_Package extends Horde_Xml_Element
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
        return (string)$this->n;
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
}