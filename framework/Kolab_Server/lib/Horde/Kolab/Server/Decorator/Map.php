<?php
/**
 * A server delegation that maps object attributes.
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
 * A server delegation that maps object attributes.
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
class Horde_Kolab_Server_Decorator_Map
implements Horde_Kolab_Server_Interface
{
    /**
     * The server we delegate to.
     *
     * @var Horde_Kolab_Server
     */
    private $_server;

    /**
     * The attribute mapping.
     *
     * @var array
     */
    private $_mapping;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server $server  The base server connection.
     * @param array              $mapping The attribute mapping.
     */
    public function __construct(
        Horde_Kolab_Server_Interface $server,
        array $mapping
    ) {
        $this->_server  = $server;
        $this->_mapping = $mapping;
    }

    /**
     * Connect to the server. Use this method if the user name you can provide
     * does not match a GUID. In this case it will be required to map this user
     * name first.
     *
     * @param string $user The user name.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    public function connect($user = null, $pass = null)
    {
        $this->_server->connect($user, $pass);
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
    public function connectGuid($guid = '', $pass = '')
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
        $this->_server->getGuid();
    }

    /**
     * Get the base GUID of this server
     *
     * @return string The base GUID of this server.
     */
    public function getBaseGuid()
    {
        $this->_server->getBaseGuid();
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
        $data = $this->_server->read($guid);
        $this->unmapAttributes($data);
        return $data;
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
        $this->mapKeys($attrs);
        $data = $this->_server->readAttributes($guid, $attrs);
        $this->unmapAttributes($data);
        return $data;
    }

    /**
     * Finds object data matching a given set of criteria.
     *
     * @param string $query  The LDAP search query
     * @param array  $params Additional search parameters.
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function find($query, array $params = array())
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Mapped($criteria, $this);
        $data = $this->_server->find($criteria, $params);
        $this->unmapAttributes($data);
        return $data;
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param string $query  The LDAP search query
     * @param string $parent The parent to search below.
     * @param array  $params Additional search parameters.
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow($query, $parent, array $params = array())
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Mapped($criteria, $this);
        $data = $this->_server->findBelow($criteria, $parent, $params);
        $this->unmapAttributes($data);
        return $data;
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
    public function save(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        //@todo: This will not work this way as we need to map internal
        // attributes.
        $this->mapAttributes($data);
        $this->_server->save($object, $data);
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
    public function add(
        Horde_Kolab_Server_Object_Interface $object,
        array $data
    ) {
        //@todo: This will not work this way as we need to map internal
        // attributes.
        $this->mapAttributes($data);
        $this->_server->add($object, $data);
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
     * Map attributes defined within this library to their real world
     * counterparts.
     *
     * @param array &$data The data that has been read and needs to be mapped.
     *
     * @return NULL
     */
    protected function unmapAttributes(&$data)
    {
        foreach ($data as &$element) {
            foreach ($this->mapping as $attribute => $map) {
                if (isset($element[$map])) {
                    $element[$attribute] = $element[$map];
                    unset($element[$map]);
                }
            }
        }
    }

    /**
     * Map attributes defined within this library into their real world
     * counterparts.
     *
     * @param array &$data The data to be written.
     *
     * @return NULL
     */
    protected function mapAttributes(&$data)
    {
        foreach ($this->mapping as $attribute => $map) {
            if (isset($data[$attribute])) {
                $data[$map] = $data[$attribute];
                unset($data[$attribute]);
            }
        }
    }

    /**
     * Map attribute keys defined within this library into their real world
     * counterparts.
     *
     * @param array &$keys The attribute keys.
     *
     * @return NULL
     */
    protected function mapKeys(&$keys)
    {
        foreach ($this->mapping as $attribute => $map) {
            $key = array_search($attribute, $keys);
            if ($key !== false) {
                $keys[$key] = $map;
            }
        }
    }

    /**
     * Map a single attribute key defined within this library into its real
     * world counterpart.
     *
     * @param array $field The attribute name.
     *
     * @return The real name of this attribute on the server we connect to.
     */
    public function mapField($field)
    {
        if (isset($this->mapping[$field])) {
            return $this->mapping[$field];
        }
        return $field;
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
}
