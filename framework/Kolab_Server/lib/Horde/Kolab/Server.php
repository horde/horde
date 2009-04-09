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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

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
abstract class Horde_Kolab_Server
{
    /** Maximum accepted level for the object class hierarchy */
    const MAX_HIERARCHY = 100;

    /**
     * Server parameters.
     *
     * @var array
     */
    public $params = array();

    /**
     * The UID of the current user.
     *
     * @var string
     */
    public $uid;

    /**
     * The search methods offered by the object defined for this server.
     *
     * @var array
     */
    protected $searches;

    /**
     * The structure handler for this server.
     *
     * @var Horde_Kolab_Server_Structure
     */
    public $structure;

    /**
     * Construct a new Horde_Kolab_Server object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        $this->params = $params;
        if (isset($params['uid'])) {
            $this->uid = $params['uid'];
        }

        $structure        = isset($params['structure']['driver'])
            ? $params['structure']['driver'] : 'kolab';
        $structure_params = isset($params['structure']['params'])
            ? $params['structure']['params'] : array();

        $this->structure = &Horde_Kolab_Server_Structure::factory($structure,
                                                                  $this,
                                                                  $structure_params);

        // Initialize the search operations supported by this server.
        $this->searches = $this->getSearchOperations();
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server instance based
     * on $driver.
     *
     * @param mixed $driver The type of concrete Horde_Kolab_Server subclass to
     *                      return.
     * @param array $params A hash containing any additional
     *                      configuration or connection parameters a subclass
     *                      might need.
     *
     * @return Horde_Kolab_Server The newly created concrete Horde_Kolab_Server
     *                            instance.
     *
     * @throws Horde_Kolab_Server_Exception If the requested Horde_Kolab_Server
     *                                      subclass could not be found.
     */
    static public function &factory($driver, $params = array())
    {
        $class = 'Horde_Kolab_Server_' . ucfirst(basename($driver));
        if (class_exists($class)) {
            $db = new $class($params);
            return $db;
        }
        throw new Horde_Kolab_Server_Exception(
            'Server type definition "' . $class . '" missing.');
    }

    /**
     * Attempts to return a reference to a concrete Horde_Kolab_Server
     * instance based on $driver. It will only create a new instance
     * if no Horde_Kolab_Server instance with the same parameters currently
     * exists.
     *
     * This method must be invoked as:
     * $var = &Horde_Kolab_Server::singleton()
     *
     * @param array $params An array of optional login parameters. May
     *                      contain "uid" (for the login uid), "user"
     *                      (if the uid is not yet known), and "pass"
     *                      (for a password).
     *
     * @return Horde_Kolab_Server The concrete Horde_Kolab_Server reference.
     *
     * @throws Horde_Kolab_Server_Exception If the driver configuration is
     *                                      missing or the given user could not
     *                                      be identified.
     */
    static public function &singleton($params = null)
    {
        global $conf;

        static $instances = array();

        if (isset($conf['kolab']['server']['driver'])) {
            $driver = $conf['kolab']['server']['driver'];
            if (isset($conf['kolab']['server']['params'])) {
                $server_params = $conf['kolab']['server']['params'];
            } else {
                $server_params = array();
            }
        } else {
            throw new Horde_Kolab_Server_Exception(
                'The configuration for the Kolab server driver is missing!');
        }

        if (!empty($params)) {
            if (isset($params['user'])) {
                $tmp_server = &Horde_Kolab_Server::factory($driver, $server_params);

                try {
                    $uid = $tmp_server->uidForIdOrMail($params['user']);
                } catch (Horde_Kolab_Server_Exception $e) {
                    throw new Horde_Kolab_Server_Exception(
                        sprintf(_("Failed identifying the UID of the Kolab user %s. Error was: %s"),
                                $params['user'],
                                $e->getMessage()));
                }
                if ($uid === false) {
                    throw new Horde_Kolab_Server_MissingObjectException(
                        sprintf(_("Failed identifying the UID of the Kolab user %s."),
                                $params['user']));
                }
                $params['uid'] = $uid;
            }
            $server_params = array_merge($server_params, $params);
        }

        $sparam         = $server_params;
        $sparam['pass'] = isset($sparam['pass']) ? md5($sparam['pass']) : '';
        ksort($sparam);
        $signature = serialize(array($driver, $sparam));
        if (empty($instances[$signature])) {
            $instances[$signature] = &Horde_Kolab_Server::factory($driver,
                                                                  $server_params);
        }

        return $instances[$signature];
    }

    /**
     * Stores the attribute definitions in the cache.
     *
     * @return Horde_Kolab_Server The concrete Horde_Kolab_Server reference.
     */
    function shutdown()
    {
        if (isset($this->attributes)) {
            if (!empty($GLOBALS['conf']['kolab']['server']['cache']['driver'])) {
                $params = isset($GLOBALS['conf']['kolab']['server']['cache']['params'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['params'] : null;
                $cache  = Horde_Cache::singleton($GLOBALS['conf']['kolab']['server']['cache']['driver'],
                                                 $params);
                foreach ($this->attributes as $key => $value) {
                    $cache->set('attributes_' . $key, @serialize($value));
                }
            }
        }
    }

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
    public function &fetch($uid = null, $type = null)
    {
        if (!isset($uid)) {
            $uid = $this->uid;
        }
        if (empty($type)) {
            $type = $this->determineType($uid);
        }

        $object = &Horde_Kolab_Server_Object::factory($type, $uid, $this);
        return $object;
    }

    /**
     * Generates a unique ID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function generateUid($type, $info)
    {
        if (!class_exists($type)) {
            $result = Horde_Kolab_Server_Object::loadClass($type);
        }

        $id = call_user_func(array($type, 'generateId'), $info);

        return $this->generateServerUid($type, $id, $info);
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
    public function &add($info)
    {
        if (!isset($info['type'])) {
            throw new Horde_Kolab_Server_Exception(
                'The type of a new object must be specified!');
        }

        $uid = $this->generateUid($info['type'], $info);

        $object = &Horde_Kolab_Server_Object::factory($info['type'], $uid, $this);
        if ($object->exists()) {
            throw new Horde_Kolab_Server_Exception(
                sprintf(_("The object with the uid \"%s\" does already exist!"),
                        $uid));
        }
        unset($info['type']);
        $object->save($info);
        return $object;
    }

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
    public function listHash($type, $params = null)
    {
        $list = $this->listObjects($type, $params);

        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
        } else {
            $attributes = null;
        }

        $hash = array();
        foreach ($list as $uid => $entry) {
            $hash[$uid] = $entry->toHash($attributes);
        }

        return $hash;
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
    protected function generateServerUid($type, $id, $info)
    {
        return $this->structure->generateServerUid($type, $id, $info);
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
        static $cache = null;
        static $lifetime;

        if (!isset($this->attributes)) {
            if (!empty($GLOBALS['conf']['kolab']['server']['cache']['driver'])) {
                $params = isset($GLOBALS['conf']['kolab']['server']['cache']['params'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['params'] : null;
                $cache  = Horde_Cache::singleton($GLOBALS['conf']['kolab']['server']['cache']['driver'],
                                                 $params);
                register_shutdown_function(array($this, 'shutdown'));
                $lifetime = isset($GLOBALS['conf']['kolab']['server']['cache']['lifetime'])
                    ? $GLOBALS['conf']['kolab']['server']['cache']['lifetime'] : 300;
            }
        }

        if (empty($this->attributes[$class])) {

            if (!empty($cache)) {
                $this->attributes[$class] = @unserialize($cache->get('attributes_' . $class, $lifetime));
            }

            if (empty($this->attributes[$class])) {

                $childclass = $class;
                $classes    = array();
                $level      = 0;
                while ($childclass != 'Horde_Kolab_Server_Object'
                       && $level < self::MAX_HIERARCHY) {
                    $classes[]  = $childclass;
                    $childclass = get_parent_class($childclass);
                    $level++;
                }

                /** Finally add the basic object class */
                $classes[] = $childclass;

                if ($level == self::MAX_HIERARCHY) {
                    Horde::logMessage(sprintf('The maximal level of the object hierarchy has been exceeded for class \"%s\"!',
                                              $class),
                                      __FILE__, __LINE__, PEAR_LOG_ERROR);
                }

                /**
                 * Collect attributes from bottom to top.
                 */
                $classes = array_reverse($classes);

                $types = array('defined', 'required', 'derived', 'defaults',
                               'locked', 'object_classes');
                foreach ($types as $type) {
                    $$type = array();
                }

                foreach ($classes as $childclass) {
                    $vars = get_class_vars($childclass);
                    if (isset($vars['init_attributes'])) {
                        foreach ($types as $type) {
                            /**
                             * If the user wishes to adhere to the schema
                             * information from the server we will skip the
                             * attributes defined within the object class here.
                             */
                            if (!empty($this->params['schema_override'])
                                && in_array($type, 'defined', 'required')) {
                                continue;
                            }
                            if (isset($vars['init_attributes'][$type])) {
                                $$type = array_merge($$type,
                                                     $vars['init_attributes'][$type]);
                            }
                        }
                    }
                }

                $attrs = array();

                foreach ($object_classes as $object_class) {
                    $info = $this->getObjectclassSchema($object_class);
                    if (isset($info['may'])) {
                        $defined = array_merge($defined, $info['may']);
                    }
                    if (isset($info['must'])) {
                        $defined  = array_merge($defined, $info['must']);
                        $required = array_merge($required, $info['must']);
                    }
                    foreach ($defined as $attribute) {
                        try {
                            $attrs[$attribute] = $this->getAttributeSchema($attribute);
                        } catch (Horde_Kolab_Server_Exception $e) {
                            /**
                             * If the server considers the attribute to be
                             * invalid we mark it.
                             */
                            $attrs[$attribute] = array('invalid' => true);
                        }
                    }
                    foreach ($required as $attribute) {
                        $attrs[$attribute]['required'] = true;
                    }
                    foreach ($locked as $attribute) {
                        $attrs[$attribute]['locked'] = true;
                    }
                    foreach ($defaults as $attribute => $default) {
                        $attrs[$attribute]['default'] = $default;
                    }
                    $attrs[Horde_Kolab_Server_Object::ATTRIBUTE_OC]['default'] = $object_classes;
                }
                foreach ($derived as $key => $attribute) {
                    if (isset($attribute['base'])) {
                        if (!is_array($attribute['base'])) {
                            $bases = array($attribute['base']);
                        } else {
                            $bases = $attribute['base'];
                        }
                        /**
                         * Usually derived attribute are determined on basis
                         * of one or more attributes. If any of these is not
                         * supported the derived attribute should not be
                         * included into the set of supported attributes.
                         */
                        foreach ($bases as $base) {
                            if (!isset($attrs[$base])) {
                                continue;
                            }
                            $attrs[$key] = $attribute;
                        }
                    } else {
                        $attrs[$key] = $attribute;
                    }
                }
                $this->attributes[$class] = array($attrs,
                                                  array(
                                                      'derived'  => array_keys($derived),
                                                      'locked'   => $locked,
                                                      'required' => $required));
            }
        }
        return $this->attributes[$class];
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
    protected function getObjectclassSchema($objectclass)
    {
        return array();
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
    protected function getAttributeSchema($attribute)
    {
        return array();
    }

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        $server_searches = array();
        foreach ($this->getSupportedObjects() as $sobj) {
            if (in_array('getSearchOperations', get_class_methods($sobj))) {
                $searches = call_user_func(array($sobj, 'getSearchOperations'));
                foreach ($searches as $search) {
                    $server_searches[$search] = array('class' => $sobj);
                }
            }
        }
        return $server_searches;
    }

    /**
     * Capture undefined calls.
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
        if (in_array($method, array_keys($this->searches))) {
            array_unshift($args, $this);
            if (isset($this->searches[$method])) {
                return call_user_func_array(array($this->searches[$method]['class'],
                                                  $method), $args);
            }
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf("The server type \"%s\" does not support method \"%s\"!",
                    get_class($this), $method));
    }

    /**
     * Stub for reading object data.
     *
     * @param string $uid   The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array An array of attributes.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function read($uid, $attrs = null);

    /**
     * Stub for saving object data.
     *
     * @param string  $uid    The UID of the object to be added.
     * @param array   $data   The attributes of the object to be added.
     * @param boolean $exists Does the object already exist on the server?
     *
     * @return boolean  True if saving succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function save($uid, $data, $exists = false);

    /**
     * Delete an object.
     *
     * @param string $uid The UID of the object to be deleted.
     *
     * @return boolean True if saving succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function delete($uid);

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
    abstract public function listObjects($type, $params = null);

    /**
     * Return the root of the UID values on this server.
     *
     * @return string The base UID on this server (base DN on ldap).
     */
    abstract public function getBaseUid();

}
