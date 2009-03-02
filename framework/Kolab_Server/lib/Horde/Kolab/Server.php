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

/** Define types of return values. */
define('KOLAB_SERVER_RESULT_SINGLE', 1);
define('KOLAB_SERVER_RESULT_STRICT', 2);
define('KOLAB_SERVER_RESULT_MANY',   3);

/** Define the base types. */
define('KOLAB_SERVER_USER',  'kolabInetOrgPerson');
define('KOLAB_SERVER_GROUP', 'kolabGroupOfNames');

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

    /**
     * Server parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * The UID of the current user.
     *
     * @var string
     */
    public $uid;

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
    public function &factory($driver, $params = array())
    {
        $class = 'Horde_Kolab_Server_' . basename($driver);
        if (class_exists($class)) {
            $db = new $class($params);
            return $db;
        }
        throw new Horde_Kolab_Server_Exception('Server type definition "' . $class . '" missing.');
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
    public function &singleton($params = null)
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
            throw new Horde_Kolab_Server_Exception('The configuration for the Kolab server driver is missing!');
        }

        if (!empty($params)) {
            if (isset($params['user'])) {
                $tmp_server = &Horde_Kolab_Server::factory($driver, $server_params);

                try {
                    $uid = $tmp_server->uidForIdOrMail($params['user']);
                } catch (Horde_Kolab_Server_Exception $e) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("Failed identifying the UID of the Kolab user %s. Error was: %s"),
                                                                   $params['user'],
                                                                   $e->getMessage()));
                }
                $params['uid'] = $uid;
            }
            $server_params = array_merge($server_params, $params);
        }

        $sparam             = $server_params;
        $sparam['password'] = isset($sparam['password']) ? md5($sparam['password']) : '';
        $signature          = serialize(array($driver, $sparam));
        if (empty($instances[$signature])) {
            $instances[$signature] = &Horde_Kolab_Server::factory($driver,
                                                                  $server_params);
        }

        return $instances[$signature];
    }

    /**
     * Fetch a Kolab object.
     *
     * This method will not necessarily retrieve any data from the server and
     * might simply generate a new instance for the desired object. This method
     * can also be used in order to fetch non-existing objects that will be
     * saved later.
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
            throw new Horde_Kolab_Server_Exception('The type of a new object must be specified!');
        }

        $uid = $this->generateUid($info['type'], $info);

        $object = &Horde_Kolab_Server_Object::factory($info['type'], $uid, $this);
        if ($object->exists()) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("The object with the uid \"%s\" does already exist!"),
                                                           $uid));
        }
        $object->save($info);
        return $object;
    }

    /**
     * Identify the UID for the first object found with the given ID.
     *
     * @param string $id       Search for objects with this ID.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForId($id,
                             $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => 'uid',
                                              'op'    => '=',
                                              'test'  => $id),
                         ),
        );
        return $this->uidForSearch($criteria, $restrict);
    }

    /**
     * Identify the UID for the first user found with the given mail.
     *
     * @param string $mail     Search for users with this mail address.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForMail($mail,
                               $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => 'mail',
                                              'op'    => '=',
                                              'test'  => $mail),
                         ),
        );
        return $this->uidForSearch($criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given alias.
     *
     * @param string $mail     Search for objects with this mail alias.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForAlias($mail,
                                $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => 'alias',
                                              'op'    => '=',
                                              'test'  => $mail),
                         ),
        );
        return $this->uidForSearch($criteria, $restrict);
    }

    /**
     * Identify the UID for the first object found with the given ID or mail.
     *
     * @param string $id Search for objects with this uid/mail.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForIdOrMail($id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => 'uid',
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => 'mail',
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return $this->uidForSearch($criteria);
    }

    /**
     * Identify the UID for the first object found with the given mail
     * address or alias.
     *
     * @param string $mail Search for objects with this mail address
     * or alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForMailOrAlias($mail)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => 'alias',
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => 'mail',
                                   'op'    => '=',
                                   'test'  => $id),
                         )
        );
        return $this->uidForSearch($criteria);
    }

    /**
     * Identify the UID for the first object found with the given ID,
     * mail or alias.
     *
     * @param string $id Search for objects with this ID/mail/alias.
     *
     * @return string|boolean The UID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function uidForIdOrMailOrAlias($id)
    {
        $criteria = array('OR' =>
                         array(
                             array('field' => 'alias',
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => 'mail',
                                   'op'    => '=',
                                   'test'  => $id),
                             array('field' => 'uid',
                                   'op'    => '=',
                                   'test'  => $id),
                         ),
        );
        return $this->uidForSearch($criteria);
    }

    /**
     * Identify the primary mail attribute for the first object found
     * with the given ID or mail.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return mixed The mail address or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function mailForIdOrMail($id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => 'objectClass',
                                   'op'    => '=',
                                   'test'  => KOLAB_SERVER_USER),
                             array('OR' =>
                                   array(
                                       array('field' => 'uid',
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => 'mail',
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $data = $this->attrsForSearch($criteria, array('mail'),
                                      KOLAB_SERVER_RESULT_STRICT);
        if (!empty($data)) {
            return $data['mail'][0];
        } else {
            return false;
        }
    }

    /**
     * Returns a list of allowed email addresses for the given user.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return array An array of allowed mail addresses.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function addrsForIdOrMail($id)
    {
        $criteria = array('AND' =>
                         array(
                             array('field' => 'objectClass',
                                   'op'    => '=',
                                   'test'  => KOLAB_SERVER_USER),
                             array('OR' =>
                                   array(
                                       array('field' => 'uid',
                                             'op'    => '=',
                                             'test'  => $id),
                                       array('field' => 'mail',
                                             'op'    => '=',
                                             'test'  => $id),
                                   ),
                             ),
                         ),
        );

        $result = $this->attrsForSearch($criteria, array('mail', 'alias'),
                                        KOLAB_SERVER_RESULT_STRICT);
        $addrs  = array_merge((array) $result['mail'], (array) $result['alias']);

        if (empty($result)) {
            return array();
        }
        $criteria = array('AND' =>
                         array(
                             array('field' => 'objectClass',
                                   'op'    => '=',
                                   'test'  => KOLAB_SERVER_USER),
                             array('field' => 'kolabDelegate',
                                   'op'    => '=',
                                   'test'  => $result['mail'][0]),
                         ),
        );

        $result = $this->attrsForSearch($criteria, array('mail', 'alias'),
                                      KOLAB_SERVER_RESULT_MANY);
        if (!empty($result)) {
            foreach ($result as $adr) {
                if (isset($adr['mail'])) {
                    $addrs = array_merge((array) $addrs, (array) $adr['mail']);
                }
                if (isset($adr['alias'])) {
                    $addrs = array_merge((array) $addrs, (array) $adr['alias']);
                }
            }
        }

        return $addrs;
    }

    /**
     * Identify the GID for the first group found with the given mail.
     *
     * @param string $mail     Search for groups with this mail address.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed The GID or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function gidForMail($mail,
                               $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        $criteria = array('AND' => array(array('field' => 'mail',
                                              'op'    => '=',
                                              'test'  => $mail),
                         ),
        );
        return $this->gidForSearch($criteria, $restrict);
    }

    /**
     * Is the given UID member of the group with the given mail address?
     *
     * @param string $uid  UID of the user.
     * @param string $mail Search the group with this mail address.
     *
     * @return boolean True in case the user is in the group, false otherwise.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function memberOfGroupAddress($uid, $mail)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => 'mail',
                                    'op'    => '=',
                                    'test'  => $mail),
                              array('field' => 'member',
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $result = $this->gidForSearch($criteria,
                                      KOLAB_SERVER_RESULT_SINGLE);
        return !empty($result);
    }

    /**
     * Get the groups for this object.
     *
     * @param string $uid The UID of the object to fetch.
     *
     * @return array An array of group ids.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function getGroups($uid)
    {
        $criteria = array('AND' =>
                          array(
                              array('field' => 'member',
                                    'op'    => '=',
                                    'test'  => $uid),
                          ),
        );

        $result = $this->gidForSearch($criteria, KOLAB_SERVER_RESULT_MANY);
        if (empty($result)) {
            return array();
        }
        return $result;
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
        $list = $this->listObjects($type, $params);

        if (isset($params['attributes'])) {
            $attributes = $params['attributes'];
        } else {
            $attributes = null;
        }

        $hash = array();
        foreach ($list as $entry) {
            $hash[] = $entry->toHash($attributes);
        }

        return $hash;
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
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The corresponding Kolab object type.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract protected function determineType($uid);

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
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract protected function generateServerUid($type, $id, $info);

    /**
     * Return the root of the UID values on this server.
     *
     * @return string The base UID on this server (base DN on ldap).
     */
    abstract public function getBaseUid();

    /**
     * Identify the UID for the first user found using a specified
     * attribute value.
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function uidForSearch($criteria,
                                          $restrict = KOLAB_SERVER_RESULT_SINGLE);

    /**
     * Identify the GID for the first group found using a specified
     * attribute value.
     *
     * @param array $criteria The search parameters as array.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return boolean|string|array The GID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function gidForSearch($criteria,
                                          $restrict = KOLAB_SERVER_RESULT_SINGLE);

    /**
     * Identify attributes for the objects found using a filter.
     *
     * @param array $criteria The search parameters as array.
     * @param array $attrs    The attributes to retrieve.
     * @param int   $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return array The results.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    abstract public function attrsForSearch($criteria, $attrs,
                                            $restrict = KOLAB_SERVER_RESULT_SINGLE);
}
