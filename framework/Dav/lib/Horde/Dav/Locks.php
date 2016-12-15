<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;
use Sabre\DAV\Locks;

/**
 * A locking backend.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Locks extends Locks\Backend\AbstractBackend
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * A lock handler
     *
     * @var Horde_Lock
     */
    protected $_lock;

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry  A registry object.
     * @param Horde_Lock $lock          A lock object.
     */
    public function __construct(Horde_Registry $registry, Horde_Lock $lock)
    {
        $this->_registry = $registry;
        $this->_lock = $lock;
    }

    /**
     * Returns a list of Sabre\DAV\Locks\LockInfo objects
     *
     * This method should return all the locks for a particular uri, including
     * locks that might be set on a parent uri.
     *
     * If returnChildLocks is set to true, this method should also look for
     * any locks in the subtree of the uri for locks.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    public function getLocks($uri, $returnChildLocks)
    {
        list($app) = explode('/', $uri);
        try {
            // @todo use $returnChildLocks when we implemented sub-tree
            // searching in Horde_Lock
            $locks = $this->_lock->getLocks($app, $uri);
        } catch (Horde_Lock_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
        $infos = array();
        foreach ($locks as $lock) {
            $info = new Locks\LockInfo();
            $info->owner = $lock['lock_owner'];
            $info->token = $lock['lock_id'];
            $info->timeout = $lock['lock_expiry_timestamp'];
            $info->created = $lock['lock_origin_timestamp'];
            $info->scope = $lock['lock_type'] == Horde_Lock::TYPE_EXCLUSIVE
                ? Locks\LockInfo::EXCLUSIVE
                : Locks\LockInfo::SHARED;
            $info->uri = $lock['lock_principal'];
            $infos[] = $info;
        }
        return $infos;
    }

    /**
     * Locks a uri
     *
     * @param string $uri
     * @param Locks\LockInfo $lockInfo
     * @return bool
     */
    public function lock($uri, Locks\LockInfo $lockInfo)
    {
        list($app) = explode('/', $uri);
        $type = $lockInfo->scope == Locks\LockInfo::EXCLUSIVE
            ? Horde_Lock::TYPE_EXCLUSIVE
            : Horde_Lock::TYPE_SHARED;
        try {
            $lockId = $this->_lock->setLock(
                $this->_registry->getAuth(),
                $app,
                $uri,
                $lockInfo->timeout ?: Horde_Lock::PERMANENT,
                $type
            );
            $lockInfo->token = $lockId;
        } catch (Horde_Lock_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Removes a lock from a uri
     *
     * @param string $uri
     * @param Locks\LockInfo $lockInfo
     * @return bool
     */
    public function unlock($uri, Locks\LockInfo $lockInfo)
    {
        try {
            $this->_lock->clearLock($lockInfo->token);
        } catch (Horde_Lock_Exception $e) {
            throw new DAV\Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
