<?php
/**
 * Gollem application API.
 *
 * This file defines Gollem's external API interface. Other
 * applications can interact with Gollem through this API.
 *
 * @author  Amith Varghese (amith@xalan.com)
 * @author  Michael Slusarz (slusarz@curecanti.org)
 * @author  Ben Klang (bklang@alkaloid.net)
 * @package Gollem
 */
class Gollem_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

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

        require_once dirname(__FILE__) . '/base.load.php';
        require GOLLEM_BASE . '/config/backends.php';

        $perms['tree']['gollem']['backends'] = false;
        $perms['title']['gollem:backends'] = _("Backends");

        // Run through every backend.
        foreach ($backends as $backend => $curBackend) {
            $perms['tree']['gollem']['backends'][$backend] = false;
            $perms['title']['gollem:backends:' . $backend] = $curBackend['name'];
        }

        return $perms;
    }

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecial($item, $updated)
    {
        switch ($item) {
        case 'columnselect':
            $columns = Horde_Util::getFormData('columns');
            if (!empty($columns)) {
                $GLOBALS['prefs']->setValue('columns', $columns);
                return true;
            }
            break;
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
        return Gollem::getMenu();
    }

}
