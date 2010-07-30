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

if (Kronolith::showAjaxView()) {
    Horde::applicationUrl('', true)->setAnchor('calendar:internal')->redirect();
}

require_once KRONOLITH_BASE . '/lib/Forms/CreateCalendar.php';

// Exit if this isn't an authenticated user or if the user can't
// create new calendars (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_share')) {
    Horde::applicationUrl($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Kronolith_CreateCalendarForm($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been created."), $vars->get('name')), 'horde.success');
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::applicationUrl('calendars/', true)->redirect();
}

$title = $form->getTitle();
require KRONOLITH_TEMPLATES . '/common-header.inc';
require KRONOLITH_TEMPLATES . '/menu.inc';
echo $form->renderActive($form->getRenderer(), $vars, 'create.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
