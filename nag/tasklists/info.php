<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('nag');

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    exit;
}

try {
    $tasklist = $nag_shares->getShare(Horde_Util::getFormData('t'));
} catch (Horde_Share_Exception $e) {
    exit;
}

$subscribe_url = Horde::url($registry->get('webroot', 'horde') . '/rpc.php/nag/', true, -1)
    . ($tasklist->get('owner') ? $tasklist->get('owner') : '')
    . '/' . $tasklist->getName() . '.ics';

$identity = $injector->getInstance('Horde_Prefs_Identity')->getIdentity($tasklist->get('owner'));
$owner_name = $identity->getValue('fullname');
if (trim($owner_name) == '') {
    $owner_name = Horde_Auth::getOriginalAuth();
}


require NAG_TEMPLATES . '/tasklist_info.php';
