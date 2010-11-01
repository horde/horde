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
     *
     * @var string
     */
    protected $_params = array();

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     * <pre>
     * 'user' - (string) The current username.
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
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
