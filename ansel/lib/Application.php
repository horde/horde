<?php
/**
 * Ansel application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Ansel through this API.
 *
 * Copyright 2004-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

if (!defined('ANSEL_BASE')) {
    define('ANSEL_BASE', realpath(__DIR__ . '/..'));
}

if (!defined('HORDE_BASE')) {
    if (file_exists(ANSEL_BASE . '/config/horde.local.php')) {
        include ANSEL_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', realpath(ANSEL_BASE . '/..'));
    }
}
require_once HORDE_BASE . '/lib/core.php';

class Ansel_Application extends Horde_Registry_Application
{
    /**
     */
    public $features = array(
        'smartmobileView' => true
    );

    /**
     */
    public $version = 'H5 (3.0.7)';

    /**
     * Global variables defined:
     *   $ansel_db - TODO  remove this global. Only place left that uses it
     *               are the face objects.
     */
    protected function _init()
    {
        if (!$GLOBALS['conf']['image']['driver']) {
            throw new Ansel_Exception('You must configure a Horde_Image driver to use Ansel');
        }

        // For now, autoloading the Content_* classes depend on there being a
        // registry entry for the 'content' application that contains at least
        // the fileroot entry
        $GLOBALS['injector']
          ->getInstance('Horde_Autoloader')
          ->addClassPathMapper(
            new Horde_Autoloader_ClassPathMapper_Prefix(
              '/^Content_/',
              $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Ansel_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
        }

        $factories = array(
            'Ansel_Styles' => 'Ansel_Factory_Styles',
            'Ansel_Faces' => 'Ansel_Factory_Faces',
            'Ansel_Storage' => 'Ansel_Factory_Storage',
        );
        foreach ($factories as $interface => $v) {
            $GLOBALS['injector']->bindFactory($interface, $v, 'create');
        }

        // Create db, share, and vfs instances.
        // @TODO: This only place that uses the global now are the face methods.
        $GLOBALS['ansel_db'] = $GLOBALS['injector']->getInstance('Horde_Db_Adapter');

        /* Set up a default config */
        $GLOBALS['injector']->bindImplementation('Ansel_Config', 'Ansel_Config');
    }

    /**
     */
    public function perms()
    {
        return array(
            'admin' => array(
                'title' => _("Administrators")
            )
        );
    }

    /**
     */
    public function menu($menu)
    {
        global $conf, $registry;

        /* Browse/Search */
        $menu->add(
            Horde::url('browse.php'),
            _("_Browse"),
            'ansel-browse', null, null, null,
            (($GLOBALS['prefs']->getValue('defaultview') == 'browse' && basename($_SERVER['PHP_SELF']) == 'index.php') ||
             (basename($_SERVER['PHP_SELF']) == 'browse.php')) ? 'current' : '__noselection'
        );

        if ($GLOBALS['registry']->getAuth()) {
            $url = Ansel::getUrlFor(
                'view',
                array(
                    'owner' => $GLOBALS['registry']->getAuth(),
                    'groupby' => 'owner',
                    'view' => 'List')
            );

            $menu->add(
                $url,
                _("_My Galleries"), 'ansel-mygalleries', null, null, null,
                (Horde_Util::getFormData('owner', false) == $GLOBALS['registry']->getAuth())
                    ? 'current'
                    : '__noselection'
            );
        }

        $menu->add(
            Ansel::getUrlFor('view', array('view' => 'List')),
            _("_All Galleries"), 'ansel-allgalleries', null, null, null,
           (($GLOBALS['prefs']->getValue('defaultview') == 'galleries' && basename($_SERVER['PHP_SELF']) == 'index.php') ||
            (basename($_SERVER['PHP_SELF']) == 'group.php' && Horde_Util::getFormData('owner') !== $GLOBALS['registry']->getAuth())
                   ? 'current'
                   : '__noselection')
        );

        if ($conf['faces']['driver'] && $registry->isAuthenticated()) {
            $menu->add(Horde::url('faces/search/all.php'), _("_Faces"), 'ansel-faces');
        }
    }

    /**
     * Adds additional items to the sidebar.
     *
     * @param Horde_View_Sidebar $sidebar  The sidebar object.
     */
    public function sidebar($sidebar)
    {
        /* Let authenticated users create new galleries. */
        if ($GLOBALS['registry']->isAdmin() ||
            (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists('ansel') && $GLOBALS['registry']->getAuth()) ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('ansel', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {


            $sidebar->addNewButton(
                _("_New Gallery"),
                Horde::url('gallery.php')->add('url', Horde::signUrl(Horde::selfUrl(true, false, true)))->add('actionID', 'add')
            );

        }
    }

}
