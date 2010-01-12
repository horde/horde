<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
new Horde_Application();

$identity = Horde_Prefs_Identity::singleton();
$fullname = $identity->getValue('fullname');
if (empty($fullname)) {
    $fullname = Horde_Auth::getAuth();
}

$m = new Horde_Mobile(_("Welcome"));
$m->add(new Horde_Mobile_text(sprintf(_("Welcome, %s"), $fullname)));

foreach ($registry->listApps() as $app) {
    if ($registry->hasMobileView($app)) {
        $m->add(new Horde_Mobile_link($registry->get('name', $app), Horde::url($registry->get('webroot', $app) . '/'), $registry->get('name', $app)));
    }
}

$m->display();
