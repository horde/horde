<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-2.1.php
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */

/**
 * The Horde_Core_Auth_UsernameHook class wraps another authentication driver
 * but converts all user names through the user name hooks where necessary.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://opensource.org/licenses/lgpl-2.1.php LGPL
 * @package  Core
 */
class Horde_Core_Auth_UsernameHook extends Horde_Auth_Base
{
    /**
     * The wrapped authentication driver.
     *
     * @var Horde_Auth_Base
     */
    protected $_base;

    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     *   - base: (Horde_Auth_Base) The base Horde_Auth driver.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['base'])) {
            throw new InvalidArgumentException('Missing base parameter.');
        }

        $this->_base = $params['base'];
        unset($params['base']);

        parent::__construct($params);
    }

    /**
     */
    protected function _authenticate($userId, $credentials)
    {
    }

    /**
     */
    public function authenticate($userId, $credentials, $login = true)
    {
        return $this->_base->authenticate($userId, $credentials, $login);
    }


    /**
     */
    public function validateAuth()
    {
        return $this->_base->validateAuth();
    }

    /**
     */
    public function addUser($userId, $credentials)
    {
        return $this->_base->addUser(
            $GLOBALS['registry']->convertUsername($userId, true),
            $credentials
        );
    }

    /**
     */
    public function lockUser($userId, $time = 0)
    {
        return $this->_base->lockUser(
            $GLOBALS['registry']->convertUsername($userId, true),
            $time
        );
    }

    /**
     */
    public function unlockUser($userId, $resetBadLogins = false)
    {
        return $this->_base->unlockUser(
            $GLOBALS['registry']->convertUsername($userId, true),
            $resetBadLogins
        );
    }

    /**
     */
    public function isLocked($userId, $show_details = false)
    {
        return $this->_base->isLocked(
            $GLOBALS['registry']->convertUsername($userId, true),
            $show_details
        );
    }

    /**
     */
    public function updateUser($oldID, $newID, $credentials)
    {
        return $this->_base->updateUser($oldID, $newID, $credentials);
    }

    /**
     */
    public function removeUser($userId)
    {
        return $this->_base->removeUser($userId);
    }

    /**
     */
    public function listUsers($sort = false)
    {
        return $this->_base->listUsers($sort);
    }

    /**
     */
    public function exists($userId)
    {
        return $this->_base->exists($userId);
    }

    /**
     */
    public function transparent()
    {
        return $this->_base->transparent();
    }

    /**
     */
    public function resetPassword($userId)
    {
        return $this->_base->resetPassword($userId);
    }

    /**
     */
    public function hasCapability($capability)
    {
        return $this->_base->hasCapability($capability);
    }

    /**
     */
    public function getParam($param)
    {
        return $this->_base->getParam($param);
    }

    /**
     */
    public function getCredential($name = null)
    {
        return $this->_base->getCredential($name);
    }

    /**
     */
    public function setCredential($type, $value)
    {
        return $this->_base->setCredential($type, $value);
    }

    /**
     */
    public function setError($type, $msg = null)
    {
        return $this->_base->setError($type, $msg);
    }

    /**
     */
    public function getError($msg = false)
    {
        return $this->_base->getError($msg);
    }
}