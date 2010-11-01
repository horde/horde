<?php
/**
 * Preferences storage implementation for a Kolab IMAP server.
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_KolabImap extends Horde_Prefs_Storage
{
    /**
     * Handle for the current Kolab connection.
     *
     * @var Kolab
     */
    protected $_connection;

    /**
     * ID of the config default share
     *
     * @var string
     */
    protected $_share;

    /**
     */
    public function get($scope)
    {
        $this->_connect();

        $pref = $this->_getPref($scope);

        if (is_null($pref)) {
            /* No preferences saved yet. */
            return false;
        }

        $ret = array();

        foreach ($pref['pref'] as $prefstr) {
            // If the string doesn't contain a colon delimiter, skip it.
            if (strpos($prefstr, ':') !== false) {
                // Split the string into its name:value components.
                list($name, $val) = explode(':', $prefstr, 2);
                $ret[$name] = base64_decode($val);
            }
        }
    }

    /**
     */
    public function store($prefs)
    {
        $this->_connect();

        // Build a hash of the preferences and their values that need
        // to be stored on the IMAP server. Because we have to update
        // all of the values of a multi-value entry wholesale, we
        // can't just pick out the dirty preferences; we must update
        // every scope that has dirty preferences.
        foreach ($prefs as $scope => $vals) {
            $new_values = array();
            foreach ($vals as $name => $pref) {
                $new_values[] = $name . ':' . base64_encode($pref['v']);
            }

            $pref = $this->_getPref($scope);

            if (is_null($pref)) {
                $old_uid = null;
                $prefs_uid = $this->_connection->_storage->generateUID();
            } else {
                $old_uid = $pref['uid'];
                $prefs_uid = $pref['uid'];
            }

            $object = array(
                'uid' => $prefs_uid,
                'application' => $scope,
                'pref' => $new_values
            );

            $result = $this->_connection->_storage->save($object, $old_uid);
            if ($result instanceof PEAR_Error) {
                throw new Horde_Prefs_Exception($result);
            }
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        if (is_null($scope)) {
            $this->_connection->deleteAll();
        } else {
            // TODO
        }
    }

    /* Helper functions. */

    /**
     * Opens a connection to the Kolab server.
     *
     * @throws Horde_Prefs_Exception
     */
    protected function _connect()
    {
        if (isset($this->_connection)) {
            return;
        }

        $shares = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Share')->create('h-prefs');
        $default = $shares->getDefaultShare();
        if ($default instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($default);
        }
        $this->_share = $default->getName();

        require_once 'Horde/Kolab.php';
        $connection = new Kolab('h-prefs');
        if ($connection instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($connection);
        }

        $result = $this->_connection->open($this->_share, 1);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($result);
        }

        $this->_connection = $connection;
    }

    /**
     * Retrieves the requested preference from the user's config folder.
     *
     * @param string $scope  Scope specifier.
     *
     * @return array  The preference value.
     * @throws Horde_Prefs_Exception
     */
    protected function _getPref($scope)
    {
        $this->_connect();

        $prefs = $this->_connection->getObjects();
        if ($prefs instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($prefs);
        }

        foreach ($prefs as $pref) {
            if ($pref['application'] == $scope) {
                return $pref;
            }
        }

        return null;
    }


}
