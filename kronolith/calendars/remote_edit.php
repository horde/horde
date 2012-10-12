<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('kronolith');

$vars = Horde_Variables::getDefaultVariables();
$url = $vars->get('url');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:remote|' . rawurlencode($url))->redirect();
}

// Exit if this isn't an authenticated user or if the user can't
// subscribe to remote calendars (remote_cals is locked).
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('remote_cals')) {
    $default->redirect();
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
    $default->redirect();
}
$form = new Kronolith_Form_EditRemoteCalendar($vars, $remote_calendar);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been saved."), $vars->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    $default->redirect();
}

$key = $registry->getAuthCredential('password');
$username = $calendar['user'];
$password = $calendar['password'];
if ($key) {
    $secret = $injector->getInstance('Horde_Secret');
    $username = $secret->read($key, base64_decode($username));
    $password = $secret->read($key, base64_decode($password));
}

$vars->set('name', $calendar['name']);
$vars->set('url', $calendar['url']);
if (isset($calendar['desc'])) {
    $vars->set('desc', $calendar['desc']);
}
if (isset($calendar['color'])) {
    $vars->set('color', $calendar['color']);
}
$vars->set('user', $username);
$vars->set('password', $password);
$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/remote_edit.php'), 'post');
$page_output->footer();
