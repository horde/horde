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
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Factory_Connection_Configuration
extends Horde_Kolab_Server_Factory_Connection_Base
{
    /**
     * Configuration parameters for the connection.
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
     */
    public function __construct(array $config)
    {
        $this->setConfiguration($config);
    }

    /**
     * Get the connection configuration.
     *
     * @return array $configuration The configuration parameters.
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

    /**
     * Set the connection configuration.
     *
     * @param array $configuration The configuration parameters.
     *
     * @return NULL
     */
    public function setConfiguration(array $configuration)
    {
        $this->_configuration = $configuration;

        if (empty($configuration['mock'])) {
            $this->_factory = new Horde_Kolab_Server_Factory_Connection_Ldap();
        } else {
            $this->_factory = new Horde_Kolab_Server_Factory_Connection_Mock();
        }

        $this->_factory->setConfiguration($configuration);
    }

    /**
     * Return the server connection that should be used.
     *
     * @return Horde_Kolab_Server_Connection The server connection.
     */
    public function getConnection()
    {
        return $this->_factory->getConnection();
    }
}