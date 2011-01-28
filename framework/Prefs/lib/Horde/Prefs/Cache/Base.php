<?php
/**
 * Cache driver for the preferences system.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
abstract class Horde_Prefs_Cache_Base
{
    /**
     * Configuration parameters.
     * 'user' is always available as an entry.
     *
     * @var string
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Additional configuration parameters.
     */
    public function __construct($user, array $params = array())
    {
        $this->_params = array_merge($this->_params, $params);
        $this->_params['user'] = $user;
    }

    /**
     * Retrieves the requested preferences scope from the cache backend.
     *
     * @param string $scope  Scope specifier.
     *
     * @return mixed  Returns false if no data is available, otherwise the
     *                Horde_Prefs_Scope object.
     * @throws Horde_Prefs_Exception
     */
    abstract public function get($scope);

    /**
     * Stores preferences in the cache backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object to store.
     *
     * @throws Horde_Prefs_Exception
     */
    abstract public function store($scope_ob);

    /**
     * Removes preferences from the cache.
     *
     * @param string $scope  The scope to remove. If null, clears entire
     *                       cache.
     *
     * @throws Horde_Prefs_Exception
     */
    abstract public function remove($scope = null);

}
