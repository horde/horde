<?php
/**
 * Deletes a history entry from the ticket.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
if (!Whups::hasPermission($ticket->get('queue'), 'queue', Horde_Perms::DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$result = $whups_driver->deleteHistory($vars->get('transaction'));
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result, 'horde.error');
} else {
    $notification->push(_("Entry deleted."), 'horde.success');
}

if ($url = Horde_Util::getFormData('url')) {
    header('Location: ' . $url);
} else {
    Horde::url($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}
