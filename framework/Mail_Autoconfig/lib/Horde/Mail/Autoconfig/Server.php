<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */

/**
 * Autoconfigured configuration details for a server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mail_Autoconfig
 */
abstract class Horde_Mail_Autoconfig_Server
{
    /**
     * The server hostname.
     *
     * @var string
     */
    public $host = null;

    /**
     * The server port.
     *
     * @var integer
     */
    public $port = null;

    /**
     * The server label.
     *
     * @var integer
     */
    public $label = null;

    /**
     * TLS connection details.
     *
     * 'tls' = TLS needed for direct connection to server/port.
     * 'starttls' = Switch to TLS via protocol after connection.
     * false = No TLS connection used.
     *
     * @var mixed
     */
    public $tls = null;

    /**
     * The username to use.
     *
     * @var string
     */
    public $username = null;

    /**
     * Check to see if server can be connected to.
     *
     * @param array $opts  Additional options:
     *   - auth: (mixed) The authentication credentials used to test a
     *           successful connection.
     *   - insecure: (boolean) If true, will attempt insecure authentication.
     *   - users: (array) A list of usernames to attempt if trying auth. If
     *            successful, the username will be stored in $username.
     *
     * @return boolean  True if server is valid.
     */
    abstract public function valid(array $opts = array());

}
