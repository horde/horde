<?php
/**
 * A connection to a LDAP master/slave setup.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * A connection to a LDAP master/slave setup.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Connection_Splittedldap
implements Horde_Kolab_Server_Connection_Interface
{
    /**
     * LDAP read connection handle.
     *
     * @var Horde_Ldap
     */
    private $_ldap_read;

    /**
     * LDAP write connection handle.
     *
     * @var Horde_Ldap
     */
    private $_ldap_write;

    /**
     * Constructor
     *
     * @param Horde_Ldap $ldap_read  The ldap_read connection.
     * @param Horde_Ldap $ldap_write The ldap_write connection.
     */
    public function __construct(
        Horde_Ldap $ldap_read,
        Horde_Ldap $ldap_write
    ) {
        $this->_ldap_read = $ldap_read;
        $this->_ldap_write = $ldap_write;
    }

    /**
     * Get the server read connection.
     *
     * @return mixed The connection for reading data.
     */
    public function getRead()
    {
        return $this->_ldap_read;
    }

    /**
     * Get the server write connection.
     *
     * @return mixed The connection for writing data.
     */
    public function getWrite()
    {
        return $this->_ldap_write;
    }
}