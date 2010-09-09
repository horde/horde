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

require_once KRONOLITH_BASE . '/lib/Forms/SubscribeRemoteCalendar.php';

// Exit if this isn't an authenticated user or if the user can't
// subscribe to remote calendars (remote_cals is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('remote_cals')) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$form = new Kronolith_SubscribeRemoteCalendarForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $form->execute();
        $notification->push(sprintf(_("You have been subscribed to \"%s\" (%s)."), $vars->get('name'), $url), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::url('calendars/', true)->redirect();
}

$title = $form->getTitle();
$menu = Horde::menu();
require KRONOLITH_TEMPLATES . '/common-header.inc';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, 'remote_subscribe.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
