<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$calendar_id = $vars->get('c');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:internal|' . $calendar_id)->redirect();
}

try {
    $calendar = $kronolith_shares->getShare($calendar_id);
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('calendars/', true)->redirect();
}
if ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($calendar->get('owner')) || !$registry->isAdmin())) {
    $notification->push(_("You are not allowed to delete this calendar."), 'horde.error');
    Horde::url('calendars/', true)->redirect();
}
$form = new Kronolith_Form_DeleteCalendar($vars, $calendar);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been deleted."), $calendar->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::url('calendars/', true)->redirect();
}

$title = $form->getTitle();
$menu = Horde::menu();
require $registry->get('templates', 'horde') . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, 'delete.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
