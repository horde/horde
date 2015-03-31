<?php
/**
 * Storage driver for the preferences system.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
abstract class Horde_Prefs_Storage_Base
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
        $this->_params['user'] = (string)$user;
    }

    /**
     * Get the list of driver parameters.
     *
     * @return array  Driver parameters.
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Retrieves the requested preferences scope from the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @return Horde_Prefs_Scope  The modified scope object.
     * @throws Horde_Prefs_Exception
     */
    abstract public function get($scope_ob);

    /**
     * Stores changed preferences in the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @throws Horde_Prefs_Exception
     */
    abstract public function store($scope_ob);

    /**
     * Called whenever a preference value is changed.
     *
     * @param string $scope  Scope specifier.
     * @param string $pref   The preference name.
     */
    public function onChange($scope, $pref)
    {
    }

    /**
     * Removes preferences from the backend.
     *
     * @param string $scope  The scope of the prefs to clear. If null, clears
     *                       all scopes.
     * @param string $pref   The pref to clear. If null, clears the entire
     *                       scope.
     *
     * @throws Horde_Prefs_Exception
     */
    abstract public function remove($scope = null, $pref = null);

    /**
     * Lists all available scopes.
     *
     * @return array The list of scopes stored in the backend.
     */
    public function listScopes()
    {
        throw new Horde_Prefs_Exception('Not implemented!');
    }

}
