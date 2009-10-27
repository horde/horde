<?php
/**
 * A factory that receives all required details via configuration parameters.
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
 * A factory that receives all required details via configuration parameters.
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
class Horde_Kolab_Server_Factory_Configuration
implements Horde_Kolab_Server_Factory
{
    /**
     * Configuration parameters for the server.
     *
     * @var array
     */
    private $_configuration;

    /**
     * The factory used for creating the instances.
     *
     * @var Horde_Kolab_Server_Factory
     */
    private $_factory;

    /**
     * Constructor.
     *
     * @param array $config Configuration parameters for the server.
     */
    public function __construct(
        Horde_Kolab_Server_Factory $factory,
        array $config
    ) {
        $this->_configuration = $config;

        if (isset($config['logger'])) {
            $factory = new Horde_Kolab_Server_Factory_Logged(
                $factory, $config['logger']
            );
        }

        if (isset($config['map'])) {
            $factory = new Horde_Kolab_Server_Factory_Mapped(
                $factory, $config['map']
            );
        }

        if (!empty($config['cleanup'])) {
            $factory = new Horde_Kolab_Server_Factory_Cleaned(
                $factory, $config['map']
            );
        }

        $this->_factory = $factory;
    }

    /**
     * Returns the conn factory.
     *
     * @return Horde_Kolab_Server_Factory_Conn The connection factory.
     */
    public function getConnectionFactory()
    {
        return $this->_factory->getConnectionFactory();
    }

    /**
     * Returns the server configuration parameters.
     *
     * @return array The configuration parameters.
     */
    public function getConfiguration()
    {
        return $this->_factory->getConfiguration();
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server The Horde_Kolab_Server connection.
     */
    public function getServer()
    {
        return $this->_factory->getServer();
    }

    /**
     * Return the server that should be used.
     *
     * @return Horde_Kolab_Server_Connection The connection.
     */
    public function getConnection()
    {
        return $this->_factory->getConnection();
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
        return $this->_factory->getComposite();
    }

    /**
     * Return the object handler that should be used.
     *
     * @return Horde_Kolab_Server_Objects The handler for objects on the server.
     */
    public function getObjects()
    {
        return $this->_factory->getObjects();
    }

    /**
     * Return the structural representation that should be used.
     *
     * @return Horde_Kolab_Server_Structure The representation of the db
     *                                      structure.
     */
    public function getStructure()
    {
        return $this->_factory->getStructure();
    }

    /**
     * Return the search handler that should be used.
     *
     * @return Horde_Kolab_Server_Search The search handler.
     */
    public function getSearch()
    {
        return $this->_factory->getSearch();
    }

    /**
     * Return the db schema representation that should be used.
     *
     * @return Horde_Kolab_Server_Schema The db schema representation.
     */
    public function getSchema()
    {
        return $this->_factory->getSchema();
    }

}