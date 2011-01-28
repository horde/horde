<?php
/**
 * A cleanup decoration for Kolab Servers that allows to remove all added
 * objects.
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
 * A cleanup decoration for Kolab Servers that allows to remove all added
 * objects.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Decorator_Clean
implements Horde_Kolab_Server_Interface
{
    /**
     * The server we delegate to.
     *
     * @var Horde_Kolab_Server
     */
    private $_server;

    /**
     * The objects added.
     *
     * @var array
     */
    private $_added = array();

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server $server The base server connection.
     */
    public function __construct(
        Horde_Kolab_Server_Interface $server
    ) {
        $this->_server = $server;
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
     * Get the current GUID
     *
     * @return string The GUID of the connected user.
     */
    public function getGuid()
    {
        return $this->_server->getGuid();
    }

    /**
     * Get the base GUID of this server
     *
     * @return string The base GUID of this server.
     */
    public function getBaseGuid()
    {
        return $this->_server->getBaseGuid();
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
     * @param string $query  The LDAP search query
     * @param array  $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find($query, array $params = array())
    {
        return $this->_server->find($query, $params);
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param string $query  The LDAP search query
     * @param string $parent The parent to search below.
     * @param array  $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow($query, $parent, array $params = array())
    {
        return $this->_server->findBelow($query, $parent, $params);
    }

    /**
     * Modify existing object data.
     *
     * @param Horde_Kolab_Server_Object $object The object to be modified.
     * @param array                     $data   The attributes of the object
     *                                          to be stored.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function save(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        $this->_server->save($object, $data);
    }

    /**
     * Add new object data.
     *
     * @param Horde_Kolab_Server_Object $object The object to be added.
     * @param array                     $data   The attributes of the object
     *                                          to be added.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function add(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        $this->_server->add($object, $data);
        $this->_added[] = $object->getGuid();
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
        if (in_array($guid, $this->_added)) {
            $this->_added = array_diff($this->_added, array($guid));
        }
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
    }

    /**
     * Return the ldap schema.
     *
     * @return Horde_Ldap_Schema The LDAP schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getSchema()
    {
        return $this->_server->getSchema();
    }

    /**
     * Get the parent GUID of this object.
     *
     * @param string $guid The GUID of the child.
     *
     * @return string the parent GUID of this object.
     */
    public function getParentGuid($guid)
    {
        return $this->_server->getParentGuid($guid);
    }

    /**
     * Cleanup the server.
     *
     * @return NULL
     */
    public function cleanup()
    {
        foreach ($this->_added as $guid) {
            $this->delete($guid);
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}
