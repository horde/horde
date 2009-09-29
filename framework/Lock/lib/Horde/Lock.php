<?php
/**
 * The Horde_Lock class provides an API to create, store, check and expire locks
 * based on a given resource URI.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Horde_Lock
 */
class Horde_Lock
{
    const TYPE_EXCLUSIVE = 1;
    const TYPE_SHARED = 2;

    /**
     * Local copy of driver parameters
     * @var $_params
     */
    protected $_params;

    /**
     * Horde_Lock constructor
     *
     * @param array $params  Parameters for the specific Horde_Lock driver
     *
     * @return Horde_Lock    Instance of Horde_Lock
     */
    public function __construct($params)
    {
        $this->_params = $params;
        return $this;
    }

    /**
     * Return an array of information about the requested lock.
     *
     * @param string $lockid   Lock ID to look up
     *
     * @return mixed           Array of lock information
     * @throws Horde_Log_Exception
     */
    public function getLockInfo($lockid)
    {
        throw new Horde_Log_Exception(_("No lock driver configured!"));
    }

    /**
     * Return a list of valid locks with the option to limit the results
     * by principal, scope and/or type.
     *
     * @param string $scope      The scope of the lock.  Typically the name of
     *                           the application requesting the lock or some
     *                           other identifier used to group locks together.
     * @param string $principal  Principal for which to check for locks
     * @param int $type          Only return locks of the given type.
     *                           Defaults to null, or all locks
     *
     * @return array  Array of locks with the ID as the key and the lock details
     *                as the value. If there are no current locks this will
     *                return an empty array.
     *
     * @throws Horde_Log_Exception
     */
    public function getLocks($scope = null, $principal = null, $type = null)
    {
        throw new Horde_Log_Exception(_("No lock driver configured!"));
    }

    /**
     * Extend the valid lifetime of a valid lock to now + $extend.
     *
     * @param string $lockid  Lock ID to reset.  Must be a valid, non-expired
     *                        lock.
     * @param int $extend     Extend lock this many seconds from now.
     *
     * @return boolean
     * @throws Horde_Log_Exception
     */
    public function resetLock($lockid, $extend)
    {
        throw new Horde_Log_Exception(_("No lock driver configured!"));
    }

    /**
     * Sets a lock on the requested principal and returns the generated lock ID.
     * NOTE: No security checks are done in the Horde_Lock API.  It is expected
     * that the calling application has done all necessary security checks
     * before requesting a lock be granted.
     *
     * @param string $requestor  User ID of the lock requestor.
     * @param string $scope      The scope of the lock.  Typically the name of
     *                           the application requesting the lock or some
     *                           other identifier used to group locks together.
     * @param string $principal  A principal on which a lock should be granted.
     *                           The format can be any string but is suggested
     *                           to be in URI form.
     * @param int $lifetime      Time (in seconds) for which the lock will be
     *                           considered valid.
     * @param string exclusive   One of self::TYPE_SHARED or
     *                           self::TYPE_EXCLUSIVE.
     *                           - An exclusive lock will be enforced strictly
     *                             and must be interpreted to mean that the
     *                             resource can not be modified.  Only one
     *                             exclusive lock per principal is allowed.
     *                           - A shared lock is one that notifies other
     *                             potential lock requestors that the resource
     *                             is in use.  This lock can be overridden
     *                             (cleared or replaced with a subsequent
     *                             call to setLock()) by other users.  Multiple
     *                             users may request (and will be granted) a
     *                             shared lock on a given principal.  All locks
     *                             will be considered valid until they are
     *                             cleared or expire.
     *
     * @return mixed   A string lock ID.
     * @throws Horde_Log_Exception
     */
    public function setLock($requestor, $scope, $principal,
                     $lifetime = 1, $exclusive = self::TYPE_SHARED)
    {
        throw new Horde_Log_Exception(_("No lock driver configured!"));
    }

    /**
     * Removes a lock given the lock ID.
     * NOTE: No security checks are done in the Horde_Lock API.  It is expected
     * that the calling application has done all necessary security checks
     * before requesting a lock be cleared.
     *
     * @param string $lockid  The lock ID as generated by a previous call
     *                        to setLock()
     *
     * @return boolean
     * @throws Horde_Log_Exception
     */
    public function clearLock($lockid)
    {
        throw new Horde_Log_Exception(_("No lock driver configured!"));
    }

    /**
     * Attempts to return a concrete Horde_Lock instance based on $driver.
     *
     * @param mixed $driver  The type of concrete Horde_Lock subclass to return.
     *                       This is based on the storage driver ($driver).
     *                       The code is dynamically included. If $driver is an
     *                       array, then we will look in $driver[0]/lib/Lock/
     *                       for the subclass implementation named
     *                       $driver[1].php.
     * @param array $params  A hash containing any additional configuration or
     *                       connection parameters a subclass might need.
     *
     * @return Horde_Lock    The newly created concrete Lock instance.
     * @throws Horde_Log_Exception
     */
    public function factory($driver, $params = null)
    {
        if (is_array($driver)) {
            $app = $driver[0];
            $driver = $driver[1];
        }

        $driver = basename($driver);
        if (empty($driver) || ($driver == 'none')) {
            return new Horde_Lock();
        }

        if (is_null($params)) {
            $params = Horde::getDriverConfig('lock', $driver);
        }

        $class = 'Horde_Lock_' . $driver;
        $include_error = '';
        if (!class_exists($class)) {
            $oldTrackErrors = ini_set('track_errors', 1);
            if (!empty($app)) {
                include $GLOBALS['registry']->get('fileroot', $app) . '/lib/Horde_Lock/' . $driver . '.php';
            } else {
                include 'Horde/Lock/' . $driver . '.php';
            }
            if (isset($php_errormsg)) {
                $include_error = $php_errormsg;
            }
            ini_set('track_errors', $oldTrackErrors);
        }

        if (class_exists($class)) {
            $lock = new $class($params);
        } else {
            throw new Horde_Log_Exception('Horde_Lock Driver (' . $class . ') not found' . ($include_error ? ': ' . $include_error : '') . '.');
        }

        return $lock;
    }

    /**
     * Attempts to return a reference to a concrete Horde_Lock instance based on
     * $driver. It will only create a new instance if no Horde_Lock instance
     * with the same parameters currently exists.
     *
     * This should be used if multiple authentication sources (and, thus,
     * multiple Horde_Lock instances) are required.
     *
     * This method must be invoked as: $var = &Horde_Lock::singleton()
     *
     * @param string $driver  The type of concrete Horde_Lock subclass to
     *                        return.
     *                        This is based on the storage driver ($driver).
     *                        The code is dynamically included.
     * @param array $params   A hash containing any additional configuration or
     *                        connection parameters a subclass might need.
     *
     * @return Horde_Lock     The concrete Horde_Lock reference
     * @throws Horde_Log_Exception
     */
    public function &singleton($driver, $params = null)
    {
        static $instances = array();

        if (is_null($params)) {
            $params = Horde::getDriverConfig('lock',
                is_array($driver) ? $driver[1] : $driver);
        }

        $signature = serialize(array($driver, $params));
        if (empty($instances[$signature])) {
            $instances[$signature] = Horde_Lock::factory($driver, $params);
        }

        return $instances[$signature];
    }

}
