<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

require_once KRONOLITH_BASE . '/lib/Forms/DeleteCalendar.php';

// Exit if this isn't an authenticated user.
if (!Horde_Auth::getAuth()) {
    header('Location: ' . Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$calendar_id = $vars->get('c');
if ($calendar_id == Horde_Auth::getAuth()) {
    $notification->push(_("This calendar cannot be deleted."), 'horde.warning');
    header('Location: ' . Horde::applicationUrl('calendars/', true));
    exit;
}
$calendar = $kronolith_shares->getShare($calendar_id);
if (is_a($calendar, 'PEAR_Error')) {
    $notification->push($calendar, 'horde.error');
    header('Location: ' . Horde::applicationUrl('calendars/', true));
    exit;
} elseif ($calendar->get('owner') != Horde_Auth::getAuth() &&
          (!is_null($calendar->get('owner')) || !Horde_Auth::isAdmin())) {
    $notification->push(_("You are not allowed to delete this calendar."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('calendars/', true));
    exit;
}
$form = new Kronolith_DeleteCalendarForm($vars, $calendar);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    $result = $form->execute();
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } elseif ($result) {
        $notification->push(sprintf(_("The calendar \"%s\" has been deleted."), $calendar->get('name')), 'horde.success');
    }

    header('Location: ' . Horde::applicationUrl('calendars/', true));
    exit;
}

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
