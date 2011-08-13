<?php
/**
 * Horde application API.
 *
 * This file defines Horde's core API interface. Other core Horde libraries
 * can interact with Horde through this API.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @package Horde
 */

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once dirname(__FILE__) . '/core.php';

class Horde_Application extends Horde_Registry_Application
{
    /**
     */
    public $version = '4.0.9-git';

    /**
     */
    public function perms()
    {
        $permissions = array(
            'max_blocks' => array(
                'title' => _("Maximum Number of Portal Blocks"),
                'type' => 'int'
            ),
            'administration' => array(
                'title' => _("Administration"),
            )
        );

        try {
            foreach ($GLOBALS['registry']->callByPackage('horde', 'admin_list') as $perm_key => $perm_details) {
                $permissions['administration:' . $perm_key] = array('title' => Horde::stripAccessKey($perm_details['name']));
            }
        } catch (Horde_Exception $e) {/*what to do if this fails?*/}

        return $permissions;
    }

    /**
     */
    public function hasPermission($permission, $allowed, $opts = array())
    {
        if (is_array($allowed)) {
            switch ($permission) {
            case 'max_blocks':
                $allowed = max($allowed);
                break;
            }
        }
        return $allowed;
    }

    /**
     */
    public function menu($menu)
    {
        $menu->add(Horde::url('services/portal/', false, array('app' => 'horde')), _("_Home"), 'horde.png');
    }

    /**
     */
    public function prefsInit($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsInit($ui);
    }

    /**
     */
    public function prefsGroup($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsGroup($ui);
    }

    /**
     */
    public function prefsSpecial($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecial($ui, $item);
    }

    /**
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        return $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsSpecialUpdate($ui, $item);
    }

    /**
     */
    public function prefsCallback($ui)
    {
        $GLOBALS['injector']->getInstance('Horde_Prefs_Ui')->prefsCallback($ui);
    }

    /**
     */
    public function configSpecialValues($what)
    {
        switch ($what) {
        case 'apps':
            $apps = Horde_Array::valuesToKeys($GLOBALS['registry']->listApps(array('active')));
            asort($apps);
            return $apps;

        case 'languages':
            return array_map(create_function('$val', 'return preg_replace(array("/&#x([0-9a-f]{4});/ie", "/(&[^;]+;)/e"), array("Horde_String::convertCharset(pack(\"H*\", \"$1\"), \"ucs-2\", \"UTF-8\")", "Horde_String::convertCharset(html_entity_decode(\"$1\", ENT_COMPAT, \"iso-8859-1\"), \"iso-8859-1\", \"UTF-8\")"), $val);'), $GLOBALS['registry']->nlsconfig->languages);

        case 'blocks':
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_BlockCollection')->create()->getBlocksList();
        }
    }
}
