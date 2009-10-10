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
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab object db.
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
abstract class Horde_Kolab_Server_Base implements Horde_Kolab_Server,
    Horde_Kolab_Server_Objects,
    Horde_Kolab_Server_Schema,
    Horde_Kolab_Server_Search,
    Horde_Kolab_Server_Structure
{
    /**
     * Server parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * The user name of the current user.
     *
     * @var string
     */
    private $_user = null;

    /**
     * The structure handler for this server.
     *
     * @var Horde_Kolab_Server_Structure
     */
    protected $structure;

    /**
     * The search handler for this server.
     *
     * @var Horde_Kolab_Server_Search
     */
    protected $search;

    /**
     * The object handler for this server.
     *
     * @var Horde_Kolab_Server_Objects
     */
    protected $objects;

    /**
     * The data cache.
     *
     * @var mixed
     */
    protected $cache = null;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct(
        Horde_Kolab_Server_Objects $objects,
        Horde_Kolab_Server_Structure $structure,
        Horde_Kolab_Server_Search $search,
        Horde_Kolab_Server_Schema $schema
    ) {
        $objects->setServer($this);
        $structure->setServer($this);
        $search->setServer($this);
        $schema->setServer($this);

        $this->objects   = $objects;
        $this->structure = $structure;
        $this->search    = $search;
        $this->schema    = $schema;
    }

    /**
     * Set configuration parameters.
     *
     * @param array $params The parameters.
     *
     * @return NULL
     */
    public function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);

        if (isset($this->params['uid'])) {
            $this->uid = $this->params['uid'];
        }
    }

    /**
     * Set the cache handler.
     *
     * @param mixed $cache The cache handler.
     *
     * @return NULL
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Connect to the server.
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
        /** Do we need to switch the user? */
        if ($user !== $this->_current_user) {
            $this->user = $this->_connect($user, $pass);
        }
    }

    /**
     * Connect to the server.
     *
     * @param string $uid  The unique id of the user.
     * @param string $pass The password.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    public function connectUid($uid = null, $pass = null)
    {
    }

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
    public function add(array $info)
    {
        return $this->objects->add($info);
    }

    /**
     * Fetch a Kolab object.
     *
     * @param string $uid  The UID of the object to fetch.
     * @param string $type The type of the object to fetch.
     *
     * @return Kolab_Object The corresponding Kolab object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function fetch($uid = null, $type = null)
    {
        return $this->objects->fetch($uid = null, $type = null);
    }

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
    public function listObjects($type, $params = null)
    {
        return $this->objects->listObjects($type, $params = null);
    }

    /**
     * Generate a hash representation for a list of objects.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function listHash($type, $params = null)
    {
        return $this->objects->listHash($type, $params = null);
    }

    /**
     * Returns the set of objects supported by this server.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
        return $this->structure->getSupportedObjects();
    }

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid)
    {
        return $this->structure->determineType($uid);
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function generateServerUid($type, $id, $info)
    {
        return $this->structure->generateServerUid($type, $id, $info);
    }

    /**
     * Return the schema for the given objectClass.
     *
     * @param string $objectclass Fetch the schema for this objectClass.
     *
     * @return array The schema for the given objectClass.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getObjectclassSchema($objectclass)
    {
        return $this->schema->getObjectclassSchema($objectclass);
    }

    /**
     * Return the schema for the given attribute.
     *
     * @param string $attribute Fetch the schema for this attribute.
     *
     * @return array The schema for the given attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    public function getAttributeSchema($attribute)
    {
        return $this->schema->getAttributeSchema($attribute);
    }

    /**
     * Return the attributes supported by the given object class.
     *
     * @param string $class Determine the attributes for this class.
     *
     * @return array The supported attributes.
     *
     * @throws Horde_Kolab_Server_Exception If the schema analysis fails.
     */
    public function &getAttributes($class)
    {
        return $this->schema->getAttributes($class);
    }

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        return $this->search->getSearchOperations();
    }

    /**
     * Capture undefined calls and assume they refer to a search operation.
     *
     * @param string $method The name of the called method.
     * @param array  $args   Arguments of the call.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function __call($method, $args)
    {
        return $this->search->__call($method, $args);
    }
}
