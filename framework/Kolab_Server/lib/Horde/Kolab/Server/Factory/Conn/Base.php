<?php
/**
 * A base connection factory definition.
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
 * A base connection factory definition.
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
abstract class Horde_Kolab_Server_Factory_Conn_Base
implements Horde_Kolab_Server_Factory_Conn
{
    /**
     * Connection parameters.
     *
     * @var array
     */
    private $_configuration;

    /**
     * Get the connection configuration.
     *
     * @return array $configuration The configuration parameters.
     */
    public function getConfiguration()
    {
        if (!isset($this->_configuration)) {
            throw new Horde_Kolab_Server_Exception(
                'The configuration has not been set!'
            ); 
        }
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
    }
}