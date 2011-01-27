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

$vars = Horde_Variables::getDefaultVariables();
$url = $vars->get('url');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:remote|' . rawurlencode($url))->redirect();
}

// Exit if this isn't an authenticated user or if the user can't
// subscribe to remote calendars (remote_cals is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('remote_cals')) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$remote_calendar = null;

$remote_calendars = unserialize($GLOBALS['prefs']->getValue('remote_cals'));
foreach ($remote_calendars as $key => $calendar) {
    if ($calendar['url'] == $url) {
        $remote_calendar = $calendar;
        break;
    }
}
if (is_null($remote_calendar)) {
    $notification->push(_("The remote calendar was not found."), 'horde.error');
    Horde::url('calendars/', true)->redirect();
}
$form = new Kronolith_Form_UnsubscribeRemoteCalendar($vars, $remote_calendar);

// Execute if the form is valid (must pass with POST variables only).
if ($form->validate(new Horde_Variables($_POST))) {
    try {
        $calendar = $form->execute();
        $notification->push(sprintf(_("You have been unsubscribed from \"%s\" (%s)."), $calendar['name'], $calendar['url']), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::url('calendars/', true)->redirect();
}

$vars->set('url', $calendar['url']);
$title = $form->getTitle();
$menu = Horde::menu();
require $registry->get('templates', 'horde') . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, 'remote_unsubscribe.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
