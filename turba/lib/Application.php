<?php
/**
 * Turba application API.
 *
 * @package Turba
 */
class Turba_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }

        require_once dirname(__FILE__) . '/base.php';
        require TURBA_BASE . '/config/sources.php';

        $perms['tree']['turba']['sources'] = false;
        $perms['title']['turba:sources'] = _("Sources");

        // Run through every contact source.
        foreach ($cfgSources as $source => $curSource) {
            $perms['tree']['turba']['sources'][$source] = false;
            $perms['title']['turba:sources:' . $source] = $curSource['title'];
            $perms['tree']['turba']['sources'][$source]['max_contacts'] = false;
            $perms['title']['turba:sources:' . $source . ':max_contacts'] = _("Maximum Number of Contacts");
            $perms['type']['turba:sources:' . $source . ':max_contacts'] = 'int';
        }

        return $perms;
    }

    /**
     * Code to run when viewing prefs for this application.
     *
     * @param string $group  The prefGroup name.
     *
     * @return array  A list of variables to export to the prefs display page.
     */
    public function prefsInit($group)
    {
        $out = array();

        /* Assign variables for select lists. */
        if (!$GLOBALS['prefs']->isLocked('default_dir')) {
            require TURBA_BASE . '/config/sources.php';
            $out['default_dir_options'] = array();
            foreach ($cfgSources as $key => $info) {
                $out['default_dir_options'][$key] = $info['title'];
            }
        }

        foreach (Turba::getAddressBooks() as $key => $curSource) {
            if (empty($curSource['map']['__uid'])) {
                continue;
            }
            if (!empty($curSource['browse'])) {
                $GLOBALS['_prefs']['sync_books']['enum'][$key] = $curSource['title'];
            }
            $sync_books = @unserialize($GLOBALS['prefs']->getValue('sync_books'));
            if (empty($sync_books)) {
                $GLOBALS['prefs']->setValue('sync_books', serialize(array(Turba::getDefaultAddressbook())));
            }
        }

        return $out;
    }

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsHandle($item, $updated)
    {
        switch ($item) {
        case 'columnselect':
            $columns = Horde_Util::getFormData('columns');
            if (!empty($columns)) {
                $GLOBALS['prefs']->setValue('columns', $columns);
                return true;
            }
            break;

        case 'addressbookselect':
            $addressbooks = Horde_Util::getFormData('addressbooks');
            $GLOBALS['prefs']->setValue('addressbooks', str_replace("\r", '', $addressbooks));
            return true;
        }

        return $updated;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Turba::getMenu();
    }

    /**
     * Removes user data.
     *
     * @param string $user  Name of user to remove data for.
     *
     * @return mixed  true on success | PEAR_Error on failure
     */
    public function removeUserData($user)
    {
        require_once dirname(__FILE__) . '/base.php';

        if (!Horde_Auth::isAdmin() && $user != Horde_Auth::getAuth()) {
            return PEAR::raiseError(_("You are not allowed to remove user data."));
        }

        /* We need a clean copy of the $cfgSources array here.*/
        require TURBA_BASE . '/config/sources.php';
        $hasError = false;

        foreach ($cfgSources as $source) {
            if (empty($source['use_shares'])) {
                // Shares not enabled for this source
                $driver = Turba_Driver::singleton($source);
                if (is_a($driver, 'PEAR_Error')) {
                    Horde::logMessage($driver, __FILE__, __LINE__, PEAR_LOG_ERR);
                    $hasError = true;
                } else {
                    $result = $driver->removeUserData($user);
                    if (is_a($result, 'PEAR_Error')) {
                        Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                    }
                }
            }
        }

        /* Only attempt share removal if we have shares configured */
        if (!empty($_SESSION['turba']['has_share'])) {
            $shares = &$GLOBALS['turba_shares']->listShares(
                $user, PERMS_EDIT, $user);

            /* Look for the deleted user's default share and remove it */
            foreach ($shares as $share) {
                $params = @unserialize($share->get('params'));
                /* Only attempt to delete the user's default share */
                if (!empty($params['default'])) {
                    $config = Turba::getSourceFromShare($share);
                    $driver = Turba_Driver::singleton($config);
                    $result = $driver->removeUserData($user);
                    if (is_a($result, 'PEAR_Error')) {
                        Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                        $hasError = true;
                    }
                }
            }

            /* Get a list of all shares this user has perms to and remove the perms */
            $shares = $GLOBALS['turba_shares']->listShares($user);
            if (is_a($shares, 'PEAR_Error')) {
                Horde::logMessage($shares, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
            foreach ($shares as $share) {
                $share->removeUser($user);
            }

        }

        if ($hasError) {
            return PEAR::raiseError(sprintf(_("There was an error removing an address book for %s"), $user));
        }

        return true;
    }

}
