<?php
/**
 * Ansel application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Ansel through this API.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

if (!defined('ANSEL_BASE')) {
    define('ANSEL_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(ANSEL_BASE . '/config/horde.local.php')) {
        include ANSEL_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', ANSEL_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Ansel_Application extends Horde_Registry_Application
{
    /**
     * The application's version.
     *
     * @var string
     */
    public $version = 'H4 (2.0-git)';

    /**
     * Initialization function.
     *
     * Global variables defined:
     *   $ansel_db - TODO
     *
     * @throws Horde_Exception
     */
    protected function _init()
    {
        if (!$GLOBALS['conf']['image']['driver']) {
            throw new Horde_Exception('You must configure a Horde_Image driver to use Ansel');
        }

        /* For now, autoloading the Content_* classes depend on there being a
         * registry entry for the 'content' application that contains at least
         * the fileroot entry. */
        $GLOBALS['injector']->getInstance('Horde_Autoloader')->addClassPathMapper(new Horde_Autoloader_ClassPathMapper_Prefix('/^Content_/', $GLOBALS['registry']->get('fileroot', 'content') . '/lib/'));
        if (!class_exists('Content_Tagger')) {
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
        }

        $factories = array(
            'Ansel_Styles' => array('Ansel_Injector_Factory_Styles', 'create'),
            'Ansel_Faces' => array('Ansel_Injector_Factory_Faces', 'create'),
            'Ansel_Storage' => array('Ansel_Injector_Factory_Storage', 'create'),
        );
        foreach ($factories as $interface => $v) {
            $GLOBALS['injector']->bindFactory($interface, $v[0], $v[1]);
        }

        // Create db, share, and vfs instances.
        $GLOBALS['ansel_db'] = Ansel::getDb();

        /* Set up a default config */
        $GLOBALS['injector']->bindImplementation('Ansel_Config', 'Ansel_Config');

        /* Set a logger for the Vfs */
        $GLOBALS['injector']->getInstance('Horde_Core_Factory_Vfs')->create('images')->setLogger($GLOBALS['injector']->getInstance('Horde_Log_Logger'));

        /* Build initial Ansel javascript object. */
        Horde::addInlineJsVars(array('var Ansel' => array('ajax' => new stdClass, 'widgets' => new stdClass)));
    }

    /**
     * Returns a list of available permissions.
     *
     * @return array  An array describing all available permissions.
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
     * Add additional items to the menu.
     *
     * @param Horde_Menu $menu  The menu object.
     */
    public function menu($menu)
    {
        global $conf, $registry;

        /* Browse/Search */
        $menu->add(Horde::url('browse.php'), _("_Browse"),
                   'browse.png', null, null, null,
                   (($GLOBALS['prefs']->getValue('defaultview') == 'browse' &&
                    basename($_SERVER['PHP_SELF']) == 'index.php') ||
                    (basename($_SERVER['PHP_SELF']) == 'browse.php'))
                   ? 'current'
                   : '__noselection');

        $menu->add(Ansel::getUrlFor('view', array('view' => 'List')), _("_Galleries"),
                   'galleries.png', null, null, null,
                   (($GLOBALS['prefs']->getValue('defaultview') == 'galleries' &&
                    basename($_SERVER['PHP_SELF']) == 'index.php') ||
                    ((basename($_SERVER['PHP_SELF']) == 'group.php') &&
                     Horde_Util::getFormData('owner') !== $GLOBALS['registry']->getAuth())
                   ? 'current'
                   : '__noselection'));

        if ($GLOBALS['registry']->getAuth()) {
            $url = Ansel::getUrlFor('view', array('owner' => $GLOBALS['registry']->getAuth(),
                                    'groupby' => 'owner',
                                    'view' => 'List'));
            $menu->add($url, _("_My Galleries"), 'mygalleries.png', null, null,
                       null,
                       (Horde_Util::getFormData('owner', false) == $GLOBALS['registry']->getAuth())
                       ? 'current' :
                       '__noselection');
        }

        /* Let authenticated users create new galleries. */
        if ($GLOBALS['registry']->isAdmin() ||
            (!$GLOBALS['injector']->getInstance('Horde_Perms')->exists('ansel') && $GLOBALS['registry']->getAuth()) ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasPermission('ansel', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $menu->add(Horde::url('gallery.php')->add('actionID', 'add'),
                       _("_New Gallery"), 'add.png', null, null, null,
                       (basename($_SERVER['PHP_SELF']) == 'gallery.php' &&
                        Horde_Util::getFormData('actionID') == 'add')
                       ? 'current'
                       : '__noselection');
        }

        if ($conf['faces']['driver'] && $registry->isAuthenticated()) {
            $menu->add(Horde::url('faces/search/all.php'), _("_Faces"), 'user.png');
        }

        /* Print. */
        if ($conf['menu']['print'] &&
            ($pl = Horde_Util::nonInputVar('print_link'))) {
            $menu->add($pl, _("_Print"), 'print.png',
                       null, '_blank',
                       Horde::popupJs($pl, array('urlencode' => true)) . 'return false;');
        }
    }

    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        global $conf, $prefs;

        switch ($ui->group) {
        case 'metadata':
            if (!$prefs->isLocked('exif_tags')) {
                $fields = Horde_Image_Exif::getFields(array($conf['exif']['driver'], !empty($conf['exif']['params']) ? $conf['exif']['params'] : array()), true);
                $ui->override['exif_tags'] = $fields;
                $ui->override['exif_title'] = array_merge(array(
                    'none' => _("None")
                ), $fields);
            }
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'default_gallerystyle_select':
            return _("Default style for galleries") .
                Ansel::getStyleSelect('default_gallerystyle_select', $GLOBALS['prefs']->getValue('default_gallerystyle')) .
                '<br />';
        }

        return '';
    }

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'default_gallerystyle_select':
            if (isset($ui->vars->default_gallerystyle_select)) {
                $GLOBALS['prefs']->setValue('default_gallerystyle', $ui->vars->default_gallerystyle_select);
                return true;
            }
            break;
        }

        return false;
    }

}
