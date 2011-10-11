<?php
/**
 * Ansel application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Ansel through this API.
 *
 * Copyright 2004-2011 Horde LLC (http://www.horde.org/)
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
    define('ANSEL_BASE', dirname(__FILE__) . '/..');
}

if (!defined('HORDE_BASE')) {
    if (file_exists(ANSEL_BASE . '/config/horde.local.php')) {
        include ANSEL_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', ANSEL_BASE . '/..');
    }
}
require_once HORDE_BASE . '/lib/core.php';

class Ansel_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = 'H4 (2.0-ALPHA1)';

    /**
     * Global variables defined:
     *   $ansel_db - TODO  remove this global. Only place left that uses it
     *               are the face objects.
     */
    protected function _init()
    {
        if (!$GLOBALS['conf']['image']['driver']) {
            throw new Horde_Exception('You must configure a Horde_Image driver to use Ansel');
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
            throw new Horde_Exception('The Content_Tagger class could not be found. Make sure the registry entry for the Content system is present.');
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
     */
    public function prefsGroup($ui)
    {
        global $conf;

        foreach ($ui->getChangeablePrefs() as $val) {
            switch ($val) {
            case 'exif_tags':
                $fields = Horde_Image_Exif::getFields(array($conf['exif']['driver'], !empty($conf['exif']['params']) ? $conf['exif']['params'] : array()), true);
                $ui->override['exif_tags'] = $fields;
                $ui->override['exif_title'] = array_merge(array(
                    'none' => _("None")
                ), $fields);
                break;
            }
        }
    }

    /**
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

    /**
     * Callback, called from common-template-mobile.inc that sets up the jquery
     * mobile init hanler.
     */
    public function mobileInitCallback()
    {
        require ANSEL_TEMPLATES . '/mobile/javascript_defs.php';
        Horde::addScriptFile('mobile.js');
        Horde::addInlineScript(
          '$(window.document).bind("mobileinit", function() {
              $.mobile.page.prototype.options.backBtnText = "' . _("Back") .'";
              $.mobile.loadingMessage = "' . _("loading") . '";
              // TODO: Figure out how to force load the gallerylist page..
              // this does not work
              //$("#imageview").live("pagebeforeshow", function() {
              //    if (!AnselMobile.currentImage) {
              //        $.mobile.changePage("gallerylist", "slide", false, true);
              //    }
              //});
          });'
        );
    }

}
