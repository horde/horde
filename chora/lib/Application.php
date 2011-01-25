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
     * Initialization function.
     *
     * Global variables defined:
     *   $chora_conf
     *   $sourceroots
     */
    protected function _init()
    {
        global $acts, $conf, $defaultActs, $where, $atdir, $fullname, $sourceroot;

        try {
            $GLOBALS['sourceroots'] = Horde::loadConfiguration('backends.php', 'sourceroots');
        } catch (Horde_Exception $e) {
            $GLOBALS['notification']->push($e);
            $GLOBALS['sourceroots'] = array();
        }
        $sourceroots = Chora::sourceroots();

        /**
         * Variables we wish to propagate across web pages
         *  ha  = Hide Attic Files
         *  ord = Sort order
         *  sbt = Sort By Type (name, age, author, etc)
         *
         * Obviously, defaults go into $defaultActs :)
         * TODO: defaults of 1 will not get propagated correctly - avsm
         * XXX: Rewrite this propagation code, since it sucks - avsm
         */
        $acts = array(
            'onb' => 0,
            'ord' => Horde_Vcs::SORT_ASCENDING,
            'rev' => 0,
            'rt'  => null,
            'sa'  => 0,
            'sbt' => constant($conf['options']['defaultsort']),
            'ws'  => 1,
        );

        /* See if any actions have been passed as GET variables, and if so,
         * assign them into the acts array. */
        $vars = Horde_Variables::getDefaultVariables();
        foreach (array_keys($acts) as $key) {
            if (isset($vars->$key)) {
                $acts[$key] = $vars->$key;
            }
        }

        /* Use the value of the 'rt' form value for the sourceroot. If not
         * present, use the last sourceroot used as the default value if the
         * user has that preference. Otherwise, use default sourceroot. */
        if (is_null($acts['rt'])) {
            $last_sourceroot = $GLOBALS['prefs']->getValue('last_sourceroot');

            if (!empty($last_sourceroot) &&
                !empty($sourceroots[$last_sourceroot]) &&
                is_array($sourceroots[$last_sourceroot])) {
                $acts['rt'] = $last_sourceroot;
            } else {
                foreach ($sourceroots as $key => $val) {
                    if (isset($val['default'])) {
                        $acts['rt'] = $key;
                        break;
                    }
                }

                if (is_null($acts['rt'])) {
                    Chora::fatal(new Chora_Exception(_("No repositories found.")));
                }
            }
        }

        if (!isset($sourceroots[$acts['rt']])) {
            Chora::fatal(new Chora_Exception(sprintf(_("The repository with the slug '%s' was not found"), $acts['rt'])));
        }

        $sourcerootopts = $sourceroots[$acts['rt']];
        $sourceroot = $acts['rt'];

        // Cache.
        $cache = empty($conf['caching'])
            ? null
            : $GLOBALS['injector']->getInstance('Horde_Cache');

        $GLOBALS['chora_conf'] = array(
            'cvsusers' => $sourcerootopts['location'] . '/' . (isset($sourcerootopts['cvsusers']) ? $sourcerootopts['cvsusers'] : ''),
            'introText' => CHORA_BASE . '/config/' . (isset($sourcerootopts['intro']) ? $sourcerootopts['intro'] : ''),
            'introTitle' => (isset($sourcerootopts['title']) ? $sourcerootopts['title'] : ''),
            'sourceRootName' => $sourcerootopts['name']
        );
        $chora_conf = &$GLOBALS['chora_conf'];

        try {
            $GLOBALS['VC'] = Horde_Vcs::factory(Horde_String::ucfirst($sourcerootopts['type']), array(
                'cache' => $cache,
                'sourceroot' => $sourcerootopts['location'],
                'paths' => array_merge($conf['paths'], array('temp' => Horde::getTempDir())),
                'username' => isset($sourcerootopts['username']) ? $sourcerootopts['username'] : '',
                'password' => isset($sourcerootopts['password']) ? $sourcerootopts['password'] : ''
            ));
        } catch (Horde_Vcs_Exception $e) {
            Chora::fatal($e);
        }

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
                Chora::fatal(new Chora_Exception(_("Sourceroot not found. This could be a misconfiguration by the server administrator, or the server could be having temporary problems. Please try again later.")));
        }

        if (Chora::isRestricted($where)) {
            Chora::fatal(new Chora_Exception(sprintf(_("%s: Forbidden by server configuration"), $where)));
        }
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
     */
    public function perms()
    {
        $perms = array(
            'sourceroots' => array(
                'title' => _("Repositories")
            )
        );

        // Run through every source repository
        require dirname(__FILE__) . '/../config/backends.php';
        foreach ($sourceroots as $sourceroot => $srconfig) {
            $perms['sourceroots:' . $sourceroot] = array(
                'title' => $srconfig['name']
            );
        }

        return $perms;
    }

    /**
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        $menu->add(Chora::url('browsedir'), _("_Browse"), 'chora.png');
    }

    /* Sidebar method. */

    /**
     * Add node(s) to the sidebar tree.
     *
     * @param Horde_Tree_Base $tree  Tree object.
     * @param string $parent         The current parent element.
     * @param array $params          Additional parameters.
     *
     * @throws Horde_Exception
     */
    public function sidebarCreate(Horde_Tree_Base $tree, $parent = null,
                                  array $params = array())
    {
        define('CHORA_ERROR_HANDLER', true);

        $arr = array();
        asort($GLOBALS['sourceroots']);

        foreach ($GLOBALS['sourceroots'] as $key => $val) {
            if (Chora::checkPerms($key)) {
                $tree->addNode($parent . $key,
                    $parent,
                    $val['name'],
                    1,
                    false,
                    array(
                        'icon' => Horde_Themes::img('tree/folder.png'),
                        'url' => Chora::url('browsedir', '', array('rt' => $key))
                    )
                );
            }
        }
    }

}
