<?php
/**
 * A factory that generates LDAP connections.
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
 * A factory that generates LDAP connections.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Factory_Conn_Ldap
extends Horde_Kolab_Server_Factory_Conn_Base
{
    /**
     * Set the connection configuration.
     *
     * @param array $configuration The configuration parameters.
     *
     * @return NULL
     */
    public function setConfiguration(array $configuration)
    {
        if (!isset($configuration['basedn'])) {
            throw new Horde_Kolab_Server_Exception('The base DN is missing!');
        }

        if (isset($configuration['server'])) {
            $configuration['host'] = $configuration['server'];
            unset($configuration['server']);
        }

        if (isset($configuration['phpdn'])) {
            $configuration['binddn'] = $configuration['phpdn'];
            unset($configuration['phpdn']);
        }

        if (isset($configuration['phppw'])) {
            $configuration['bindpw'] = $configuration['phppw'];
            unset($configuration['phppw']);
        }

        parent::setConfiguration($configuration);
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server_Connection The server connection.
     */
    public function getConnection()
    {
        $configuration = $this->getConfiguration();
        $ldap_read = new Net_LDAP2($configuration);
        if (isset($configuration['host_master'])) {
            $configuration['host'] = $configuration['host_master'];
            $ldap_write = new Net_LDAP2($configuration);
            $connection = new Horde_Kolab_Server_Connection_Splittedldap(
                $ldap_read, $ldap_write
            );
        } else {
            $connection = new Horde_Kolab_Server_Connection_Simpleldap(
                $ldap_read
            );
        }
        return $connection;
    }
}