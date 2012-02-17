<?php
/**
 * The Horde_Lock class provides an API to create, store, check and expire locks
 * based on a given resource URI.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Ben Klang <bklang@horde.org>
 * @category Horde
 * @package  Lock
 */
abstract class Horde_Lock
{
    /* Class constants. */
    const TYPE_EXCLUSIVE = 1;
    const TYPE_SHARED = 2;

    /**
     * Driver parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     * <pre>
     * 'logger' - (Horde_Log_Logger) A logger instance.
     * </pre>
     */
    public function __construct($params = array())
    {
        if (!empty($params['logger'])) {
            $this->_logger = $params['logger'];
            unset($params['logger']);
        }

        $this->_params = $params;
    }

    /**
     * Return an array of information about the requested lock.
     *
     * @param string $lockid  Lock ID to look up.
     *
     * @return array  Lock information.
     * @throws Horde_Lock_Exception
     */
    abstract public function getLockInfo($lockid);

    /**
     * Return a list of valid locks with the option to limit the results
     * by principal, scope and/or type.
     *
     * @param string $scope      The scope of the lock.  Typically the name of
     *                           the application requesting the lock or some
     *                           other identifier used to group locks together.
     * @param string $principal  Principal for which to check for locks
     * @param integer $type      Only return locks of the given type.
     *                           Defaults to null, or all locks
     *
     * @return array  Array of locks with the ID as the key and the lock details
     *                as the value. If there are no current locks this will
     *                return an empty array.
     * @throws Horde_Lock_Exception
     */
    abstract public function getLocks($scope = null, $principal = null,
                                      $type = null);

    /**
     * Extend the valid lifetime of a valid lock to now + $extend.
     *
     * @param string $lockid   Lock ID to reset. Must be a valid, non-expired
     *                         lock.
     * @param integer $extend  Extend lock this many seconds from now.
     *
     * @return boolean  Returns true on success.
     * @throws Horde_Lock_Exception
     */
    abstract public function resetLock($lockid, $extend);

    /**
     * Sets a lock on the requested principal and returns the generated lock
     * ID. NOTE: No security checks are done in the Horde_Lock API. It is
     * expected that the calling application has done all necessary security
     * checks before requesting a lock be granted.
     *
     * @param string $requestor  User ID of the lock requestor.
     * @param string $scope      The scope of the lock.  Typically the name of
     *                           the application requesting the lock or some
     *                           other identifier used to group locks
     *                           together.
     * @param string $principal  A principal on which a lock should be
     *                           granted. The format can be any string but is
     *                           suggested to be in URI form.
     * @param integer $lifetime  Time (in seconds) for which the lock will be
     *                           considered valid.
     * @param string exclusive   One of Horde_Lock::TYPE_SHARED or
     *                           Horde_Lock::TYPE_EXCLUSIVE.
     *                           - An exclusive lock will be enforced strictly
     *                             and must be interpreted to mean that the
     *                             resource can not be modified. Only one
     *                             exclusive lock per principal is allowed.
     *                           - A shared lock is one that notifies other
     *                             potential lock requestors that the resource
     *                             is in use. This lock can be overridden
     *                             (cleared or replaced with a subsequent
     *                             call to setLock()) by other users. Multiple
     *                             users may request (and will be granted) a
     *                             shared lock on a given principal. All locks
     *                             will be considered valid until they are
     *                             cleared or expire.
     *
     * @return mixed   A string lock ID.
     * @throws Horde_Lock_Exception
     */
    abstract public function setLock($requestor, $scope, $principal,
                                     $lifetime = 1,
                                     $exclusive = Horde_Lock::TYPE_SHARED);

    /**
     * Removes a lock given the lock ID.
     * NOTE: No security checks are done in the Horde_Lock API.  It is
     * expected that the calling application has done all necessary security
     * checks before requesting a lock be cleared.
     *
     * @param string $lockid  The lock ID as generated by a previous call
     *                        to setLock()
     *
     * @return boolean  Returns true on success.
     * @throws Horde_Lock_Exception
     */
    abstract public function clearLock($lockid);
}
