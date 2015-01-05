<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 */

/**
 * Horde_Cache cache implementation for the preferences system.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Prefs
 * @since     2.6.0
 */
class Horde_Prefs_Cache_HordeCache extends Horde_Prefs_Cache_Base
{
    /**
     * @param array $params  Additional configuration parameters:
     *   - cache: (Horde_Cache) [REQUIRED] Cache object.
     *   - prefix: (string) Cache prefix.
     */
    public function __construct($user, array $params = array())
    {
        if (!isset($params['cache'])) {
            throw new InvalidArgumentException('Missing cache parameter.');
        }

        parent::__construct($user, array_merge(array(
            'prefix' => ''
        ), $params));

        $this->_params['cprefix'] = implode('|', array(
            $this->_params['user'],
            $this->_params['prefix']
        ));
    }

    /**
     */
    public function get($scope)
    {
        return @unserialize(
            $this->_params['cache']->get($this->_cacheId($scope), 0)
        );
    }

    /**
     */
    public function store($scope_ob)
    {
        $this->_params['cache']->set(
            $this->_cacheId($scope_ob->scope),
            serialize($scope_ob)
        );
    }

    /**
     */
    public function remove($scope = null)
    {
        if (!is_null($scope)) {
            $this->_params['cache']->expire($this->_cacheId($scope));
        }
    }

    /**
     * Get cache ID.
     *
     * @param string $scope  Scope ID.
     *
     * @return string Cache ID.
     */
    protected function _cacheId($scope)
    {
        return $this->_params['cprefix'] . '|' . $scope;
    }

}
