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
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server_Connection The server connection.
     */
    public function getConnection()
    {
        $ldap_read = new Net_LDAP2($params);
        if (isset($params['host_master'])) {
            $params['host'] = $params['host_master'];
            $ldap_write = new Net_LDAP2($params);
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