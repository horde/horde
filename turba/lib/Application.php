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
    public $version = 'H3 (3.0-git)';

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

}
