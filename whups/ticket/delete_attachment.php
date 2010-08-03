<?php
/**
 * Displays and handles the form to delete an attachment from the ticket.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

$ticket = Whups::getCurrentTicket();
if (!Whups::hasPermission($ticket->get('queue'), 'queue', Horde_Perms::DELETE)) {
    $notification->push(_("Permission Denied"), 'horde.error');
    Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}

$file = basename(Horde_Util::getFormData('file'));
$ticket->change('delete-attachment', $file);
$result = $ticket->commit();
if (is_a($result, 'PEAR_Error')) {
    $notification->push($result, 'horde.error');
} else {
    $notification->push(sprintf(_("Attachment %s deleted."), $file), 'horde.success');
}

if ($url = Horde_Util::getFormData('url')) {
    header('Location: ' . $url);
} else {
    Horde::applicationUrl($prefs->getValue('whups_default_view') . '.php', true)
        ->redirect();
}
