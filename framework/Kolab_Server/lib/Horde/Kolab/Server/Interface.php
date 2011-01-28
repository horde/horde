<?php
/**
 * A library for accessing the Kolab user database.
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
 * This class defines the interface of a generic Kolab user database.
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
interface Horde_Kolab_Server_Interface
{
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
    public function connectGuid($guid = '', $pass = '');

    /**
     * Get the current GUID
     *
     * @return string The GUID of the currently connected user.
     */
    public function getGuid();

    /**
     * Get the base GUID of this server
     *
     * @return string The base GUID of this server.
     */
    public function getBaseGuid();

    /**
     * Low level access to reading object data.
     *
     * This function provides direct access to the Server data.
     *
     * Usually you should use
     *
     * <code>
     *   $object = $server->fetch('a server uid');
     *   $variable = $object['attribute']
     * </code>
     *
     * to access object attributes. This is slower but takes special object
     * handling into account (e.g. custom attribute parsing).
     *
     * @param string $guid The object to retrieve.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function read($guid);

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
    public function readAttributes($guid, array $attrs);

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
    public function find($query, array $params = array());

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
    public function findBelow($query, $parent, array $params = array());

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
    );

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
    );

    /**
     * Delete an object.
     *
     * @param string $guid The GUID of the object to be deleted.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete($guid);

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
    public function rename($guid, $new);

    /**
     * Return the database schema description.
     *
     * @return array The schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getSchema();

    /**
     * Get the parent GUID of this object.
     *
     * @param string $guid The GUID of the child.
     *
     * @return string the parent GUID of this object.
     */
    public function getParentGuid($guid);
}