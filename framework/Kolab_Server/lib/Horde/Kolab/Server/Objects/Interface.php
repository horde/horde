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
 * Interface for a server object list.
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
interface Horde_Kolab_Server_Objects_Interface
{
    /**
     * Set the composite server reference for this object.
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     */
    public function setComposite(
        Horde_Kolab_Server_Composite $composite
    );

    /**
     * Add a Kolab object.
     *
     * @param array $info The object to store.
     *
     * @return Kolab_Object The newly created Kolab object.
     *
     * @throws Horde_Kolab_Server_Exception If the type of the object to add has
     *                                      been left undefined or the object
     *                                      already exists.
     */
    public function add(array $info);

    /**
     * Fetch a Kolab object.
     *
     * This method will not retrieve any data from the server
     * immediately. Instead it will simply generate a new instance for the
     * desired object.
     *
     * The server data will only be accessed once you start reading the object
     * data.
     *
     * This method can also be used in order to fetch non-existing objects that
     * will be saved later. This is however not recommended and you should
     * rather use the add($info) method for that.
     *
     * If you do not provide the object type the server will try to determine it
     * automatically based on the uid. As this requires reading data from the
     * server it is recommended to specify the object type whenever it is known.
     *
     * If you do not specify a uid the object corresponding to the user bound to
     * the server will be returned.
     *
     * @param string $uid  The UID of the object to fetch.
     * @param string $type The type of the object to fetch.
     *
     * @return Kolab_Object The corresponding Kolab object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function fetch($uid = null, $type = null);

    /**
     * List all objects of a specific type
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function listObjects($type, $params = null);

    /**
     * Generate a hash representation for a list of objects.
     *
     * The approach taken here is somewhat slow as the server data gets fetched
     * into objects first which are then converted to hashes again. Since a
     * server search will usually deliver the result as a hash the intermediate
     * object conversion is inefficient.
     *
     * But as the object classes are able to treat the attributes returned from
     * the server with custom parsing, this is currently the preferred
     * method. Especially for large result sets it would be better if this
     * method would call a static object class function that operate on the
     * result array returned from the server without using objects.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @todo The LDAP driver needs a more efficient version of this call as it
     *       is not required to generate objects before returning data as a
     *       hash. It can be derived directly from the LDAP result.
     */
    public function listHash($type, $params = null);

}