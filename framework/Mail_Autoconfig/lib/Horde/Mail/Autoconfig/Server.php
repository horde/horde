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
     * Is TLS needed to directly connect to the server/port?
     *
     * @var boolean
     */
    public $tls = false;

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
