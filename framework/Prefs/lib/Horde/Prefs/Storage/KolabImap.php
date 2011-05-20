<?php
/**
 * Preferences storage implementation for a Kolab IMAP server.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Gunnar Wrobel <p@rdus.de>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
            if ($this->_logger !== null) {
                $this->_logger->debug(
                    sprintf(
                        __CLASS__ . ': Failed retrieving Kolab preferences data storage (%s)',
                        $e->getMessage()
                    )
                );
            }
            return $scope_ob;
        }

        /** This may not fail (or if it does it is okay to pass the error up */
        $query = $data->getQuery(Horde_Kolab_Storage_Data::QUERY_PREFS);

        try {
            $pref = $query->getApplicationPreferences($scope_ob->scope);
        } catch (Horde_Kolab_Storage_Exception $e) {
            if ($this->_logger !== null) {
                $this->_logger->debug(
                    sprintf(
                        __CLASS__ . ': No preference information available for scope %s (%s).',
                        $scope_ob->scope,
                        $e->getMessage()
                    )
                );
            }
            return $scope_ob;
        }

        foreach ($pref['pref'] as $prefstr) {
            /** If the string doesn't contain a colon delimiter, skip it. */
            if (strpos($prefstr, ':') !== false) {
                /** Split the string into its name:value components. */
                list($name, $val) = explode(':', $prefstr, 2);
                $scope_ob->set($name, base64_decode($val));
            }
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
        $data = $this->_getStorage(true);

        // Build a hash of the preferences and their values that need
        // to be stored on the IMAP server. Because we have to update
        // all of the values of a multi-value entry wholesale, we
        // can't just pick out the dirty preferences; we must update
        // the entire dirty scope.
        $new_vals = array();

        foreach ($scope_ob->getDirty() as $name) {
            $new_vals[] = $name . ':' . base64_encode($scope_ob->get($name));
        }

        $pref = $this->_getPref($scope_ob->scope);

        if (is_null($pref)) {
            $old_uid = null;
            $prefs_uid = $this->_connection->_storage->generateUID();
        } else {
            $old_uid = $pref['uid'];
            $prefs_uid = $pref['uid'];
        }

        $object = array(
            'application' => $scope_ob->scope,
            'pref' => $new_vals,
            'uid' => $prefs_uid
        );

        $result = $this->_connection->_storage->save($object, $old_uid);
        if ($result instanceof PEAR_Error) {
            throw new Horde_Prefs_Exception($result);
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
            if ($this->_logger !== null) {
                $this->_logger->debug(
                    sprintf(
                        __CLASS__ . ': Failed retrieving Kolab preferences data storage (%s)',
                        $e->getMessage()
                    )
                );
            }
        }
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
        $this->_kolab->getList()->createFolder($this->_folder, 'h-prefs.default');
        if ($this->_logger !== null) {
            $this->_logger->info(
                sprintf(
                    __CLASS__ . ': Created default Kolab preferences folder "%s".',
                    $this->_folder
                )
            );
        }
        return $this->_kolab->getData($this->_folder);
    }
}
