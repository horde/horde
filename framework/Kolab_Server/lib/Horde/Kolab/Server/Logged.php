<?php
/**
 * A server delegation that logs server access via Horde_Log_Logger.
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
 * A server delegation that logs server access via Horde_Log_Logger.
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
class Horde_Kolab_Server_Logged implements Horde_Kolab_Server
{
    /**
     * The server we delegate to.
     *
     * @var Horde_Kolab_Server
     */
    private $_server;

    /**
     * The log handler.
     *
     * @var Horde_Log_Logger
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server $server The base server connection.
     * @param Horde_Log_Logger   $logger THe log handler.
     */
    public function __construct(
        Horde_Kolab_Server $server,
        Horde_Log_Logger $logger
    ) {
        $this->_server = $server;
        $this->_logger = $logger;
    }

    /**
     * Connect to the server.
     *
     * @param string $guid The global unique id of the user.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    public function connectGuid($guid = null, $pass = null)
    {
         $this->_server->connectGuid($guid, $pass);
    }

    /**
     * Low level access to reading object data.
     *
     * @param string $guid  The object to retrieve.
     * @param array  $attrs Restrict to these attributes.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation hit an error
     *                                      or returned no result.
     */
    public function read($guid, array $attrs = array())
    {
        return $this->_server->read($guid);
    }

    /**
     * Low level access to reading some object attributes.
     *
     * @param string $guid  The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @see Horde_Kolab_Server::read
     */
    public function readAttributes($guid, array $attrs)
    {
        return $this->_server->readAttributes($guid, $attrs);
    }

    /**
     * Finds object data matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query $query  The criteria for the search.
     * @param array                    $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find(
        Horde_Kolab_Server_Query $query,
        array $params = array()
    ) {
        return $this->_server->find($query, $params);
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query $query  The criteria for the search.
     * @param string                   $parent The parent to search below.
     * @param array                    $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow(
        Horde_Kolab_Server_Query $query,
        $parent,
        array $params = array()
    ) {
        return $this->_server->findBelow($query, $parent, $params);
    }

    /**
     * Modify existing object data.
     *
     * @param string $guid The GUID of the object to be added.
     * @param array  $data The attributes of the object to be added.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function save($guid, array $data)
    {
        $this->_server->save($guid, $data);
        $this->_logger->info(
            sprintf("The object \"%s\" has been successfully saved!", $guid)
        );
    }

    /**
     * Add new object data.
     *
     * @param string $guid The GUID of the object to be added.
     * @param array  $data The attributes of the object to be added.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function add($guid, array $data)
    {
        $this->_server->add($guid, $data);
        $this->_logger->info(
            sprintf("The object \"%s\" has been successfully added!", $guid)
        );
    }

    /**
     * Delete an object.
     *
     * @param string $guid The GUID of the object to be deleted.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($guid)
    {
        $this->_server->delete($guid);
        $this->_logger->info(
            sprintf("The object \"%s\" has been successfully deleted!", $guid)
        );
    }

    /**
     * Rename an object.
     *
     * @param string $guid The GUID of the object to be renamed.
     * @param string $new  The new GUID of the object.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function rename($guid, $new)
    {
        $this->_server->rename($guid, $new);
        $this->_logger->info(
            sprintf(
                "The object \"%s\" has been successfully renamed to \"%s\"!",
                $guid, $new
            )
        );
    }

    /**
     * Return the ldap schema.
     *
     * @return Net_LDAP2_Schema The LDAP schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getSchema()
    {
        return $this->_server->getSchema();
    }
}
