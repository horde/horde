<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */

/**
 * Preferences storage implementation for a Kolab IMAP server.
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Prefs
 */
class Horde_Prefs_Storage_KolabImap extends Horde_Prefs_Storage_Base
{
    /**
     * Handle for the current Kolab connection.
     *
     * @var Horde_Kolab_Storage
     */
    protected $_kolab;

    /**
     * Name of the preferences default folder
     *
     * @var string
     */
    protected $_folder;

    /**
     * Log handler.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration parameters.
     * <pre>
     * 'kolab'  - (Horde_Kolab_Storage) [REQUIRED] The storage backend.
     * 'folder' - (string) The default name of the preferences folder.
     *            DEFAULT: _('Preferences')
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct($user, array $params = array())
    {
        if (!isset($params['kolab'])) {
            throw new InvalidArgumentException('Missing "kolab" parameter.');
        }
        $this->_kolab = $params['kolab'];
        unset($params['kolab']);

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }

        if (isset($params['folder'])) {
            $this->_folder = $params['folder'];
        } else {
            $this->_folder = Horde_Prefs_Translation::t("Preferences");
        }

        parent::__construct($user, $params);
    }

    /**
     * Retrieves the requested preferences scope from the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @return Horde_Prefs_Scope  The modified scope object.
     * @throws Horde_Prefs_Exception
     */
    public function get($scope_ob)
    {
        try {
            $data = $this->_getStorage();
        } catch (Horde_Prefs_Exception $e) {
            $this->_logMissingStorage($e);
            return $scope_ob;
        }

        /** This may not fail (or if it does it is okay to pass the error up */
        $query = $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);

        try {
            $pref = $query->getApplicationPreferences($scope_ob->scope);
        } catch (Horde_Kolab_Storage_Exception $e) {
            $this->_logMissingScope($e, $scope_ob->scope);
            return $scope_ob;
        }

        foreach ($this->_prefToArray($pref['pref']) as $key => $value) {
            $scope_ob->set($key, $value);
        }
        return $scope_ob;
    }

    /**
     * Stores changed preferences in the storage backend.
     *
     * @param Horde_Prefs_Scope $scope_ob  The scope object.
     *
     * @throws Horde_Prefs_Exception
     */
    public function store($scope_ob)
    {
        /** This *must* succeed */
        $data = $this->_getStorage(true);
        $query = $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);

        try {
            $pref = $query->getApplicationPreferences($scope_ob->scope);
        } catch (Horde_Kolab_Storage_Exception $e) {
            $pref = array('application' => $scope_ob->scope);
        }

        if (isset($pref['pref'])) {
            $new = $this->_prefToArray($pref['pref']);
        } else {
            $new = array();
        }
        foreach ($scope_ob->getDirty() as $name) {
            $new[$name] = $scope_ob->get($name);
        }
        $pref['pref'] = $this->_arrayToPref($new);
        try {
            if (!isset($pref['uid'])) {
                $data->create($pref);
            } else {
                $data->modify($pref);
            }
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }
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
    public function remove($scope = null, $pref = null)
    {
        try {
            $data = $this->_getStorage();
        } catch (Horde_Prefs_Exception $e) {
            $this->_logMissingStorage($e);
            return;
        }

        if ($scope === null) {
            $data->deleteAll();
            return;
        }

        $query = $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);

        try {
            $pref = $query->getApplicationPreferences($scope);
        } catch (Horde_Kolab_Storage_Exception $e) {
            $this->_logMissingScope($e, $scope);
            return;
        }

        if ($pref === null) {
            $data->delete($pref['uid']);
            return;
        }

        $new = $this->_prefToArray($pref);
        unset($new[$pref]);
        $pref['pref'] = $this->_arrayToPref($new);

        try {
            $data->modify($pref);
        } catch (Horde_Kolab_Storage_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }
    }

    /**
     * Lists all available scopes.
     *
     * @return array The list of scopes stored in the backend.
     */
    public function listScopes()
    {
        try {
            $data = $this->_getStorage();
        } catch (Horde_Prefs_Exception $e) {
            $this->_logMissingStorage($e);
            return;
        }

        return $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS)
            ->getApplications();
    }

    /* Helper functions. */

    /**
     * Opens a connection to the Kolab server.
     *
     * @param boolean $create_missing Create a preferences folder if it is
     *                                missing.
     *
     * @return Horde_Kolab_Storage_Data The storage backend.
     *
     * @throws Horde_Prefs_Exception
     */
    protected function _getStorage($create_missing = false)
    {
        $query = $this->_kolab->getList()->getQuery();
        if ($folder = $query->getDefault('h-prefs')) {
            return $this->_kolab->getData($folder);
        }
        $folders = $query->listByType('h-prefs');
        if (!empty($folders)) {
            return $this->_kolab->getData($folders[0]);
        }
        if (!$create_missing) {
            throw new Horde_Prefs_Exception(
                'No Kolab storage backend available.'
            );
        }
        $params = $this->getParams();
        $folder = $this->_kolab->getList()
            ->getNamespace()
            ->constructFolderName($params['user'], $this->_folder);
        $this->_kolab->getList()->getListManipulation()->createFolder($folder, 'h-prefs.default');
        if ($this->_logger !== null) {
            $this->_logger->info(
                sprintf(
                    __CLASS__ . ': Created default Kolab preferences folder "%s".',
                    $this->_folder
                )
            );
        }
        return $this->_kolab->getData($folder);
    }

    /**
     * Convert Kolab preferences data to an array.
     *
     * @param array $pref The preferences list.
     *
     * @return array The preferences data as array.
     */
    private function _prefToArray($pref)
    {
        $result = array();
        foreach ($pref as $prefstr) {
            /** If the string doesn't contain a colon delimiter, skip it. */
            if (strpos($prefstr, ':') !== false) {
                /** Split the string into its name:value components. */
                list($name, $val) = explode(':', $prefstr, 2);
                $result[$name] = base64_decode($val);
            }
        }
        return $result;
    }

    /**
     * Convert a key => value list of preferences to the Kolab preferences.
     *
     * @param array $pref The preferences.
     *
     * @return array The preferences data as list.
     */
    private function _arrayToPref($pref)
    {
        $result = array();
        foreach ($pref as $name => $value) {
            if ($value !== null) {
                $result[] = $name . ':' . base64_encode($value);
            }
        }
        return $result;
    }

    /**
     * Log the missing backend.
     *
     * @param Exception $e The exception that occurred.
     *
     * @return NULL
     */
    private function _logMissingStorage(Exception $e)
    {
        if ($this->_logger !== null) {
            $this->_logger->debug(
                sprintf(
                    __CLASS__ . ': Failed retrieving Kolab preferences data storage (%s)',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Log the missing scope.
     *
     * @param Exception $e     The exception that occurred.
     * @param string    $scope The scope that was attempted to get.
     *
     * @return NULL
     */
    private function _logMissingScope(Exception $e, $scope)
    {
        if ($this->_logger !== null) {
            $this->_logger->debug(
                sprintf(
                    __CLASS__ . ': No preference information available for scope %s (%s).',
                    $scope,
                    $e->getMessage()
                )
            );
        }
    }
}
