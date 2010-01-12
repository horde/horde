<?php
/**
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/core.php';

$registry = Horde_Registry::singleton();

/* Which application. */
$app = Horde_Util::getFormData('app');
if (!$app) {
    echo '<ul id="app">';
    foreach ($registry->listApps() as $app) {
        echo '<li>' . htmlspecialchars($app) . '</li>';
    }
    echo '</ul>';
    exit;
}

/* Load $app's base environment, but don't request that the app perform
 * authentication beyond Horde's. */
$registry->pushApp($app, array('check_perms' => false));

/* Which preference. */
$pref = Horde_Util::getFormData('pref');
if (!$pref) {
    /* Load prefs config file. */
    try {
        extract(Horde::loadConfiguration('prefs.php', array('_prefs'), $app));
    } catch (Horde_Exception $e) {
        exit;
    }

    echo '<ul id="pref">';
    foreach ($_prefs as $pref => $params) {
        switch ($params['type']) {
        case 'special':
        case 'link':
            break;

        default:
            echo '<li preftype="' . htmlspecialchars($params['type']) . '">' . htmlspecialchars($pref) . '</li>';
        }
    }
    echo '</ul>';
}

/* Which action. */
if (Horde_Util::getPost('pref') == $pref) {
    /* POST for saving a pref. */
    $prefs->setValue($pref, Horde_Util::getPost('value'));
}

/* GET returns the current value, POST returns the new value. */
header('Content-type: text/plain');
echo $prefs->getValue($pref);
