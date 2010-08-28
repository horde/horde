<?php
/**
 * A Horde_Kolab_Server:: factory.
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
 * A based Horde_Kolab_Server:: factory.
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
class Horde_Kolab_Server_Factory
{
    /**
     * Return the suggested interface bindings for the Kolab Server components.
     *
     * @return array The bindings.
     */
    public function getBindings()
    {
        return array(
            array(
                'Horde_Kolab_Server_Objects_Interface',
                'Horde_Kolab_Server_Objects_Base'
            ),
            array(
                'Horde_Kolab_Server_Search_Interface',
                'Horde_Kolab_Server_Search_Base'
            ),
            array(
                'Horde_Kolab_Server_Schema_Interface',
                'Horde_Kolab_Server_Schema_Base'
            ),
        );
    }

    /**
     * Setup the machinery to create a Horde_Kolab_Server_Structure handler.
     *
     * @param array $configuration The configuration parameters for the
     *                             connection. (@todo: describe parameters)
     *
     * @return Horde_Kolab_Server_Structure_Interface
     */
    private function getStructure(array $configuration)
    {
        if (!isset($configuration['driver'])) {
            $driver = 'Horde_Kolab_Server_Structure_Kolab';
        } else {
            $driver = $configuration['driver'];
        }
        return new $driver();
    }

    /**
     * Return the server connection that should be used.
     *
     * @param array $configuration The configuration parameters for the
     *                             connection. (@todo: describe parameters)
     *
     * @return Horde_Kolab_Server_Connection The connection to the server.
     */
    public function getConnection(array $configuration)
    {
        if (empty($configuration['mock'])) {
            if (!isset($configuration['basedn'])) {
                throw new Horde_Exception('The parameter \'basedn\' is missing in the Kolab server configuration!');
            }

            $ldap_read = new Horde_Ldap($configuration);
            if (isset($configuration['host_master'])) {
                $configuration['host'] = $configuration['host_master'];
                $ldap_write = new Horde_Ldap($configuration);
                $connection = new Horde_Kolab_Server_Connection_Splittedldap(
                    $ldap_read, $ldap_write
                );
            } else {
                $connection = new Horde_Kolab_Server_Connection_Simpleldap(
                    $ldap_read
                );
            }
            return $connection;
        } else {
            if (isset($configuration['data'])) {
                $data = $configuration['data'];
            } else {
                $data = array();
            }
            $connection = new Horde_Kolab_Server_Connection_Mock(
                new Horde_Kolab_Server_Connection_Mock_Ldap(
                    $configuration, $data
                )
            );
            return $connection;
        }
    }

    /**
     * Return the server connection that should be used.
     *
     * @param array $configuration     The configuration parameters for the
     *                                 server. (@todo: describe parameters)
     * @param mixed $logger The logger (@todo: which methods need to be provided?)
     *
     * @return Horde_Kolab_Server_Interface The Horde_Kolab_Server connection.
     */
    public function getServer(array $configuration, $logger)
    {
        $connection = $this->getConnection($configuration);

        if (!isset($configuration['filter'])) {
            $server = new Horde_Kolab_Server_Ldap_Standard(
                $connection,
                $configuration['basedn']
            );
        } else {
            $server = new Horde_Kolab_Server_Ldap_Filtered(
                $connection,
                $configuration['basedn'],
                $configuration['filter']
            );
        }

        if (isset($configuration['map'])) {
            $server = new Horde_Kolab_Server_Decorator_Map(
                $server, $configuration['map']
            );
        }

        if (isset($configuration['debug']) || isset($configuration['log'])) {
            $server = new Horde_Kolab_Server_Decorator_Log(
                $server, $logger
            );
        }

        if (isset($configuration['debug']) || isset($configuration['count'])) {
            $server = new Horde_Kolab_Server_Decorator_Count(
                $server, $logger
            );
        }

        if (!empty($configuration['cleanup'])) {
            $server = new Horde_Kolab_Server_Decorator_Clean(
                $server
            );
        }
        return $server;
    }
}