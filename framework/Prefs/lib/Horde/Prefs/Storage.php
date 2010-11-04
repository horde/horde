<?php
/**
 * Storage driver for the preferences system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Prefs
 */
abstract class Horde_Prefs_Storage
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
     * Retrieves the requested preferences scope from the storage backend.
     *
     * @param string $scope  Scope specifier.
     *
     * @return mixed  Keys are pref names, values are pref values. Returns
     *                false if no data is available.
     * @throws Horde_Db_Exception
     */
    abstract public function get($scope);

    /**
     * Stores preferences in the storage backend.
     *
     * @param array $prefs  The preference list.
     *
     * @throws Horde_Db_Exception
     */
    abstract public function store($prefs);

    /**
     * Removes preferences from the backend.
     *
     * @param string $scope  The scope of the prefs to clear. If null, clears
     *                       entire cache.
     * @param string $pref   The pref to clear. If null, clears the entire
     *                       scope.
     *
     * @throws Horde_Db_Exception
     */
    abstract public function remove($scope = null, $pref = null);

}
