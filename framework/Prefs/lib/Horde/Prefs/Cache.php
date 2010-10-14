<?php
/**
 * Cache driver for the preferences system.
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
abstract class Horde_Prefs_Cache
{
    /**
     * The username.
     *
     * @var string
     */
    protected $_user;

    /**
     * Constructor.
     *
     * @param string $user  The current username.
     */
    public function __construct($user)
    {
        $this->_user = $user;
    }

    /**
     * Gets a cache entry.
     *
     * @param string $scope  The scope of the prefs to get.
     *
     * @return mixed  Prefs array on success, false on failure.
     */
    abstract public function get($scope);

    /**
     * Updates a cache entry.
     *
     * @param string $scope  The scope of the prefs being updated.
     * @param array $prefs   The preferences to update.
     */
    abstract public function update($scope, $prefs);

    /**
     * Clear cache entries.
     *
     * @param string $scope  The scope of the prefs to clear. If null, clears
     *                       entire cache.
     * @param string $scope  The pref to clear. If null, clears the entire
     *                       scope.
     */
    abstract public function clear($scope = null, $pref = null);

}
