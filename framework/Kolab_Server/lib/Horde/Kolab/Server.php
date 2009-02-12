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

/** Provide access to the Kolab specific objects. */
require_once 'Horde/Kolab/Server/Object.php';

/** Define types of return values. */
define('KOLAB_SERVER_RESULT_SINGLE', 1);
define('KOLAB_SERVER_RESULT_STRICT', 2);
define('KOLAB_SERVER_RESULT_MANY',   3);

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
     * @return Horde_Kolab_Server|PEAR_Error The newly created concrete
     *                                       Horde_Kolab_Server instance.
     */
    public function &factory($driver, $params = array())
    {
        $class = 'Horde_Kolab_Server_' . $driver;
        if (class_exists($class)) {
            $db = new $class($params);
        } else {
            $db = PEAR::raiseError('Class definition of ' . $class . ' not found.');
        }

        return $db;
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
     * @return Horde_Kolab_Server|PEAR_Error The concrete Horde_Kolab_Server
     *                                       reference.
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
            return PEAR::raiseError('The configuration for the Kolab server driver is missing!');
        }

        if (!empty($params)) {
            if (isset($params['user'])) {
                $tmp_server = &Horde_Kolab_Server::factory($driver, $server_params);

                $uid = $tmp_server->uidForIdOrMail($params['user']);
                if (is_a($uid, 'PEAR_Error')) {
                    return PEAR::raiseError(sprintf(_("Failed identifying the UID of the Kolab user %s. Error was: %s"),
                                                    $params['user'],
                                                    $uid->getMessage()));
                }
                $server_params['uid'] = $uid;
            }
            if (isset($params['pass'])) {
                if (isset($server_params['pass'])) {
                    $server_params['search_pass'] = $server_params['pass'];
                }
                $server_params['pass'] = $params['pass'];
            }
            if (isset($params['uid'])) {
                if (isset($server_params['uid'])) {
                    $server_params['search_uid'] = $server_params['pass'];
                }
                $server_params['uid'] = $params['uid'];
            }
        }

        $sparam         = $server_params;
        $sparam['pass'] = isset($sparam['pass']) ? md5($sparam['pass']) : '';
        $signature      = serialize(array($driver, $sparam));
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
     * @return Kolab_Object|PEAR_Error The corresponding Kolab object.
     */
    public function &fetch($uid = null, $type = null)
    {
        if (!isset($uid)) {
            $uid = $this->uid;
        }
        if (empty($type)) {
            $type = $this->determineType($uid);
            if (is_a($type, 'PEAR_Error')) {
                return $type;
            }
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
     * @return string|PEAR_Error The UID.
     */
    public function generateUid($type, $info)
    {
        if (!class_exists($type)) {
            $result = Horde_Kolab_Server_Object::loadClass($type);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
        }

        $id = call_user_func(array($type, 'generateId'), $info);
        if (is_a($id, 'PEAR_Error')) {
            return $id;
        }
        return $this->generateServerUid($type, $id, $info);
    }

    /**
     * Add a Kolab object.
     *
     * @param array $info The object to store.
     *
     * @return Kolab_Object|PEAR_Error The newly created Kolab object.
     */
    public function &add($info)
    {
        if (!isset($info['type'])) {
            return PEAR::raiseError('The type of a new object must be specified!');
        }

        $uid = $this->generateUid($info['type'], $info);
        if (is_a($uid, 'PEAR_Error')) {
            return $uid;
        }

        $object = &Horde_Kolab_Server_Object::factory($info['type'], $uid, $this);
        if (is_a($object, 'PEAR_Error')) {
            return $object;
        }

        if ($object->exists()) {
            return PEAR::raiseError('The object does already exist!');
        }

        $result = $object->save($info);
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf('Adding object failed: %s',
                                            $result->getMessage()));
        }
        return $object;
    }

    /**
     * Identify the UID for the first object found with the given ID.
     *
     * @param string $id       Search for objects with this ID.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForId($id,
                             $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        return $this->uidForAttr('uid', $id);
    }

    /**
     * Identify the UID for the first user found with the given mail.
     *
     * @param string $mail     Search for users with this mail address.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForMail($mail,
                               $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        return $this->uidForAttr('mail', $mail);
    }

    /**
     * Identify the UID for the first object found with the given alias.
     *
     * @param string $mail     Search for objects with this mail alias.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForAlias($mail,
                                $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        return $this->uidForAttr('alias', $mail);
    }

    /**
     * Identify the UID for the first object found with the given ID or mail.
     *
     * @param string $id Search for objects with this uid/mail.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForIdOrMail($id)
    {
        $uid = $this->uidForAttr('uid', $id);
        if (!$uid) {
            $uid = $this->uidForAttr('mail', $id);
        }
        return $uid;
    }

    /**
     * Identify the UID for the first object found with the given mail
     * address or alias.
     *
     * @param string $mail Search for objects with this mail address
     * or alias.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForMailOrAlias($mail)
    {
        $uid = $this->uidForAttr('alias', $mail);
        if (!$uid) {
            $uid = $this->uidForAttr('mail', $mail);
        }
        return $uid;
    }

    /**
     * Identify the UID for the first object found with the given ID,
     * mail or alias.
     *
     * @param string $id Search for objects with this ID/mail/alias.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    public function uidForIdOrMailOrAlias($id)
    {
        $uid = $this->uidForAttr('uid', $id);
        if (!$uid) {
            $uid = $this->uidForAttr('mail', $id);
            if (!$uid) {
                $uid = $this->uidForAttr('alias', $id);
            }
        }
        return $uid;
    }

    /**
     * Identify the primary mail attribute for the first object found
     * with the given ID or mail.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return mixed|PEAR_Error The mail address or false if there was
     *                          no result.
     */
    public function mailForIdOrMail($id)
    {
        $uid  = $this->uidForIdOrMail($id);
        $data = $this->read($uid, array('mail'));
        return $data['mail'];
    }

    /**
     * Returns a list of allowed email addresses for the given user.
     *
     * @param string $id Search for objects with this ID/mail.
     *
     * @return array|PEAR_Error An array of allowed mail addresses.
     */
    public function addrsForIdOrMail($id)
    {
        $uid  = $this->uidForIdOrMail($id);
        $data = $this->read($uid, array('mail', 'alias'));
        return array_merge($data['mail'], $data['alias']);
    }

    /**
     * Identify the GID for the first group found with the given mail.
     *
     * @param string $mail     Search for groups with this mail address.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The GID or false if there was no result.
     */
    public function gidForMail($mail,
                               $restrict = KOLAB_SERVER_RESULT_SINGLE)
    {
        return $this->gidForAttr('mail', $mail);
    }

    /**
     * Is the given UID member of the group with the given mail address?
     *
     * @param string $uid  UID of the user.
     * @param string $mail Search the group with this mail address.
     *
     * @return boolean|PEAR_Error True in case the user is in the
     *                            group, false otherwise.
     */
    public function memberOfGroupAddress($uid, $mail)
    {
        $gid  = $this->gidForMail($mail);
        $data = $this->read($gid, array('member'));
        return in_array($uid, $data['member']);
    }

    /**
     * Generate a hash representation for a list of objects.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array|PEAR_Error An array of Kolab objects.
     */
    public function listHash($type, $params = null)
    {
        $list = $this->listObjects($type, $params);
        if (is_a($list, 'PEAR_Error')) {
            return $list;
        }

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
     * @return array|PEAR_Error An array of attributes.
     */
    abstract public function read($uid, $attrs = null);

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The corresponding Kolab object type.
     */
    abstract protected function determineType($uid);

    /**
     * List all objects of a specific type
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array|PEAR_Error An array of Kolab objects.
     */
    abstract public function listObjects($type, $params = null);

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string|PEAR_Error The UID.
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
     * @param string $attr     The name of the attribute used for searching.
     * @param string $value    The desired value of the attribute.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The UID or false if there was no result.
     */
    abstract public function uidForAttr($attr, $value,
                                        $restrict = KOLAB_SERVER_RESULT_SINGLE);

    /**
     * Identify the GID for the first group found using a specified
     * attribute value.
     *
     * @param string $attr     The name of the attribute used for searching.
     * @param string $value    The desired value of the attribute.
     * @param int    $restrict A KOLAB_SERVER_RESULT_* result restriction.
     *
     * @return mixed|PEAR_Error The GID or false if there was no result.
     */
    abstract public function gidForAttr($attr, $value,
                                        $restrict = KOLAB_SERVER_RESULT_SINGLE);

}
