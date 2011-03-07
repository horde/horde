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
 */

/**
 * REST access to a PEAR server.
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

    public function fetchPackageList()
    {
        return $this->_client->get($this->_url . '/rest/c/Default/packages.xml')->getStream();
    }
}