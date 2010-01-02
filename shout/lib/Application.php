<?php
/**
 * Vilma application interface.
 *
 * This file defines Vilma's application interface.
 *
 * Copyright 2006-2010 Alkaloid Networks <http://www.alkaloid.net/>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did not
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */
define('SHOUT_BASE', dirname(__FILE__) . '/..');

class Shout_Application extends Horde_Registry_Application
{
    public $version = 'H4 (1.0-git)';
    protected $_contexts = null;
    protected $_extensions = null;
    protected $_devices = null;

    public function __construct($args = array())
    {

    }

    public function perms()
    {
        static $perms = array();
        if (!empty($perms)) {
            return $perms;
        }
        
        require_once dirname(__FILE__) . '/base.php';
        $shout_contexts = Shout_Driver::factory('storage');

        $perms['tree']['shout']['superadmin'] = false;
        $perms['title']['shout:superadmin'] = _("Super Administrator");

        $contexts = $shout_contexts->getContexts();

        $perms['tree']['shout']['contexts'] = false;
        $perms['title']['shout:contexts'] = _("Contexts");

        // Run through every contact source.
        foreach ($contexts as $context) {
            $perms['tree']['shout']['contexts'][$context] = false;
            $perms['title']['shout:contexts:' . $context] = $context;

            foreach(
                array(
                    'extensions' => 'Extensions',
                    'devices' => 'Devices',
                    'conferences' => 'Conference Rooms',
                )
                as $module => $modname) {
                $perms['tree']['shout']['contexts'][$context][$module] = false;
                $perms['title']["shout:contexts:$context:$module"] = $modname;
            }
        }

    //     function _shout_getContexts($searchfilters = SHOUT_CONTEXT_ALL,
    //                          $filterperms = null)

        return $perms;
    }
}
