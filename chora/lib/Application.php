<?php
/**
 * Chora application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Chora through this API.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Chora
 */

if (!defined('CHORA_BASE')) {
    define('CHORA_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(CHORA_BASE . '/config/horde.local.php')) {
        include CHORA_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', CHORA_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Chora_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (3.0-git)';

    /**
     * Permissions cache.
     *
     * @var array
     */
    protected $_permsCache = array();

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $sourceroots
     */
    protected function _init()
    {
        global $conf;
        global $acts, $defaultActs, $where, $atdir, $fullname, $sourceroot;

        try {
            $GLOBALS['sourceroots'] = Horde::loadConfiguration('sourceroots.php', 'sourceroots');
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $GLOBALS['sourceroots'] = array();
        }
        $sourceroots = Chora::sourceroots();

        /**
         * Variables we wish to propagate across web pages
         *  sbt = Sort By Type (name, age, author, etc)
         *  ha  = Hide Attic Files
         *  ord = Sort order
         *
         * Obviously, defaults go into $defaultActs :)
         * TODO: defaults of 1 will not get propagated correctly - avsm
         * XXX: Rewrite this propagation code, since it sucks - avsm
         */
        $defaultActs = array(
            'sbt' => constant($conf['options']['defaultsort']),
            'sa'  => 0,
            'ord' => Horde_Vcs::SORT_ASCENDING,
            'ws'  => 1,
            'onb' => 0,
            'rev' => 0,
        );

        /* Use the last sourceroot used as the default value if the user has
         * that preference. */
        $last_sourceroot = $GLOBALS['prefs']->getValue('last_sourceroot')
            ? $GLOBALS['prefs']->getValue('last_sourceroot')
            : null;

        if (!empty($last_sourceroot) &&
            !empty($sourceroots[$last_sourceroot]) &&
            is_array($sourceroots[$last_sourceroot])) {
            $defaultActs['rt'] = $last_sourceroot;
        } else {
            foreach ($sourceroots as $key => $val) {
                if (isset($val['default']) || !isset($defaultActs['rt'])) {
                    $defaultActs['rt'] = $key;
                }
            }
        }

        $acts = array();
        if (!isset($defaultActs['rt'])) {
            throw new Chora_Exception(_("No repositories found."));
        }

        /* See if any have been passed as GET variables, and if so, assign
         * them into the acts array. */
        foreach ($defaultActs as $key => $default) {
            $acts[$key] = Horde_Util::getFormData($key, $default);
        }

        if (!isset($sourceroots[$acts['rt']])) {
            throw new Chora_Exception(_("Malformed URL"), '400 Bad Request');
        }

        $sourcerootopts = $sourceroots[$acts['rt']];
        $sourceroot = $acts['rt'];

        // Cache.
        $cache = empty($conf['caching'])
            ? null
            : $GLOBALS['injector']->getInstance('Horde_Cache');

        $conf['paths']['temp'] = Horde::getTempDir();

        $GLOBALS['VC'] = Horde_Vcs::factory(Horde_String::ucfirst($sourcerootopts['type']),
            array('cache' => $cache,
                  'sourceroot' => $sourcerootopts['location'],
                  'paths' => $conf['paths'],
                  'username' => isset($sourcerootopts['username']) ? $sourcerootopts['username'] : '',
                  'password' => isset($sourcerootopts['password']) ? $sourcerootopts['password'] : ''));

        $conf['paths']['sourceroot'] = $sourcerootopts['location'];
        $conf['paths']['cvsusers'] = $sourcerootopts['location'] . '/' . (isset($sourcerootopts['cvsusers']) ? $sourcerootopts['cvsusers'] : '');
        $conf['paths']['introText'] = CHORA_BASE . '/config/' . (isset($sourcerootopts['intro']) ? $sourcerootopts['intro'] : '');
        $conf['options']['introTitle'] = isset($sourcerootopts['title']) ? $sourcerootopts['title'] : '';
        $conf['options']['sourceRootName'] = $sourcerootopts['name'];

        $where = Horde_Util::getFormData('f', '/');

        /* Location relative to the sourceroot. */
        $where = preg_replace(array('|^/|', '|\.\.|'), '', $where);

        /* Store last repository viewed */
        $GLOBALS['prefs']->setValue('last_sourceroot', $acts['rt']);

        $fullname = $sourcerootopts['location'] . (substr($sourcerootopts['location'], -1) == '/' ? '' : '/') . $where;

        if ($sourcerootopts['type'] == 'cvs') {
            $fullname = preg_replace('|/$|', '', $fullname);
            $atdir = @is_dir($fullname);
        } else {
            $atdir = !$where || (substr($where, -1) == '/');
        }
        $where = preg_replace('|/$|', '', $where);

        if (($sourcerootopts['type'] == 'cvs') &&
            !@is_dir($sourcerootopts['location'])) {
            throw new Chora_Exception(_("Sourceroot not found. This could be a misconfiguration by the server administrator, or the server could be having temporary problems. Please try again later."), '500 Internal Server Error');
        }

        if (Chora::isRestricted($where)) {
            throw new Chora_Exception(sprintf(_("%s: Forbidden by server configuration"), $where), '403 Forbidden');
        }
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        if (!empty($this->_permsCache)) {
            return $this->_permsCache;
        }

        $perms['tree']['chora']['sourceroots'] = false;
        $perms['title']['chora:sourceroots'] = _("Repositories");

        // Run through every source repository
        require dirname(__FILE__) . '/../config/sourceroots.php';
        foreach ($sourceroots as $sourceroot => $srconfig) {
            $perms['tree']['chora']['sourceroots'][$sourceroot] = false;
            $perms['title']['chora:sourceroots:' . $sourceroot] = $srconfig['name'];
        }

        $this->_permsCache = $perms;

        return $perms;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Chora::getMenu();
    }

}
