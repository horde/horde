<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Core
 */
/**
 * The Horde_Core_ActiveSync_Auth class provides a way to combine a globally
 * configured configuration driver with a transparent driver like X509
 * certificates for ActiveSync authentication. Used to mimic Exchange's ability
 * to require one, the other, or layer basic auth on top of client certs.
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, http://www.horde.org/licenses/lgpl21
 *
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package  Core
 */
class Horde_Core_ActiveSync_Auth extends Horde_Auth_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Required parameters:
     *   - base_driver: (Horde_Auth_Base) The globally configured horde auth
     *                 driver. REQUIRED
     *   - transparent_driver: (Horde_Auth_Base) The driver to perform
     *                        transparent auth, such as X509. OPTIONAL.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        if (!isset($params['base_driver'])) {
            throw new InvalidArgumentException('Missing ' . $val . ' parameter.');
        }

        parent::__construct($params);
    }

    /**
     * Find out if a set of login credentials are valid.
     *
     * @param string $userId      The userId to check.
     * @param array $credentials  The credentials to use.
     *
     * @throws Horde_Auth_Exception
     */
    protected function _authenticate($userId, $credentials)
    {
        if (!$this->_params['base_driver']->authenticate($userId, $credentials)) {
            throw new Horde_Auth_Exception($this->_params['base_driver']->getError(true), $this->_params['base_driver']->getError());
        }
    }

    /**
     * Query the current Auth object to find out if it supports the given
     * capability.
     *
     * @param string $capability  The capability to test for.
     *
     * @return boolean  Whether or not the capability is supported.
     */
    public function hasCapability($capability)
    {
        try {
            return $this->_params['base_driver']->hasCapability($capability);
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

    /**
     * Automatic authentication.
     *
     * @return boolean  Whether or not the client is allowed.
     */
    public function transparent()
    {
        if (empty($this->_params['transparent_driver'])) {
            return false;
        }
        try {
            return $this->_params['transparent_driver']->transparent();
        } catch (Horde_Auth_Exception $e) {
            return false;
        }
    }

}
