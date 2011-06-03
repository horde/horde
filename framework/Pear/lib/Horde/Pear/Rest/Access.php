<?php
/**
 * Wraps the tools for convenient access to the REST server.
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
 * Wraps the tools for convenient access to the REST server.
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
class Horde_Pear_Rest_Access
{
    /**
     * The name of the current server.
     *
     * @var string
     */
    private $_server;

    /**
     * The REST handlers.
     *
     * @var array
     */
    private $_rest;

    /**
     * Set the server name.
     *
     * @params string $server The server name.
     *
     * @return NULL
     */
    public function setServer($server)
    {
        $this->_server = $server;
    }

    /**
     * Set a rest handler for a server.
     *
     * @params string          $server The server name.
     * @params Horde_Pear_Rest $rest   The REST handler.
     *
     * @return NULL
     */
    public function setRest($server, Horde_Pear_Rest $rest)
    {
        $this->_rest[$server] = $rest;
    }

    /**
     * Return the REST handler.
     *
     * @return Horde_Pear_Rest The REST handler.
     */
    private function _getRest()
    {
        if (!isset($this->_rest[$this->_server])) {
            $this->_rest[$this->_server] = new Horde_Pear_Rest(
                new Horde_Http_Client(),
                $this->_server
            );
        }
        return $this->_rest[$this->_server];
    }

    /**
     * Return the package list handler.
     *
     * @return Horde_Pear_Rest_PackageList The handler.
     */
    public function getPackageList()
    {
        return new Horde_Pear_Rest_PackageList(
            $this->_getRest()->fetchPackageList()
        );
    }
}