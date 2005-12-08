<?php
/**
 * Shout external API interface.
 *
 * $Id$
 *
 * This file defines Shout's external API interface. Other
 * applications can interact with Shout through this API.
 *
 * @package Shout
 */
@define('SHOUT_BASE', dirname(__FILE__) . "/..");
require_once SHOUT_BASE . "/lib/defines.php";

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray'
);

function _shout_perms()
{
    static $perms = array();
    if (!empty($perms)) {
        return $perms;
    }

    @define('SHOUT_BASE', dirname(__FILE__) . '/..');
    require_once SHOUT_BASE . '/lib/base.php';

    $perms['tree']['shout']['superadmin'] = false;
    $perms['title']['shout:superadmin'] = _("Super Administrator");

//     $contexts = $shout->getContexts();

    $perms['tree']['shout']['contexts'] = false;
    $perms['title']['shout:contexts'] = _("Contexts");

    // Run through every contact source.
    foreach ($contexts as $context => $contextInfo) {
        $perms['tree']['shout']['contexts'][$context] = false;
        $perms['title']['shout:contexts:' . $context] = $context;
    }


//     function _shout_getContexts($searchfilters = SHOUT_CONTEXT_ALL,
//                          $filterperms = null)


}