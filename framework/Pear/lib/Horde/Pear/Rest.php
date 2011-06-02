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
        return $this->_client->get($this->_url . '/rest/p/packages.xml')
            ->getStream();
    }
}