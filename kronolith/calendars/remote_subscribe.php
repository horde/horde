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

$form = new Kronolith_Form_SubscribeRemoteCalendar($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $form->execute();
        $notification->push(sprintf(_("You have been subscribed to \"%s\" (%s)."), $vars->get('name'), $url), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    $default->redirect();
}

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/remote_subscribe.php'), 'post');
$page_output->footer();
