<?php
/**
 * Beatnik application API.
 *
 * @package Beatnik
 */
class Beatnik_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';

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

    $perms['title']['beatnik:domains'] = _("Domains");

    // Run through every domain
    foreach ($GLOBALS['beatnik_driver']->getDomains() as $domain) {
        $perms['tree']['beatnik']['domains'][$domain['zonename']] = false;
        $perms['title']['beatnik:domains:' . $domain['zonename']] = $domain['zonename'];
    }

    return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Beatnik::getMenu();
    }
}
