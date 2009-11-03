<?php
/**
 * Basic server factory functionality.
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
 * Basic server factory functionality.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Server_Factory_Base
implements Horde_Kolab_Server_Factory_Interface
{
    /**
     * The connection factory.
     *
     * @param Horde_Kolab_Server_Factory_Core
     */
    private $_conn_factory;

    /**
     * The server configuration parameters
     *
     * @param array
     */
    private $_configuration;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Factory_Conn $factory The server connection
     *                                                 factory.
     * @param array                           $config  Configuration
     *                                                 parameters for the
     *                                                 server.
     */
    public function __construct(
        Horde_Kolab_Server_Factory_Connection_Interface $factory,
        array $config
    ) {
        $this->_conn_factory  = $factory;
        $this->_configuration = $config;
    }

    /**
     * Returns the conn factory.
     *
     * @return Horde_Kolab_Server_Factory_Conn The connection factory.
     */
    public function getConnectionFactory()
    {
        return $this->_conn_factory;
    }

    /**
     * Returns the server configuration parameters.
     *
     * @return array The configuration parameters.
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getServer()
    {
        $configuration = $this->getConfiguration();
        if (!isset($configuration['basedn'])) {
            throw new Horde_Kolab_Server_Exception('The base DN is missing');
        }

        $connection = $this->getConnection();

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
        return $server;
    }

    /**
     * Return the server that should be used.
     *
     * @return Horde_Kolab_Server_Connection The connection.
     */
    public function getConnection()
    {
        $factory = $this->getConnectionFactory();
        $factory->setConfiguration($this->getConfiguration());
        return $factory->getConnection();
    }

    /**
     * Returns a concrete Horde_Kolab_Server_Composite instance.
     *
     * @return Horde_Kolab_Server_Composite The newly created concrete
     *                                      Horde_Kolab_Server_Composite
     *                                      instance.
     */
    public function getComposite()
    {
        $composite = new Horde_Kolab_Server_Composite_Base(
            $this->getServer(),
            $this->getObjects(),
            $this->getStructure(),
            $this->getSearch(),
            $this->getSchema()
        );
        return $composite;
    }
}