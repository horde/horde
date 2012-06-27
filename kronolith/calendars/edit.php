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

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:internal|' . $vars->get('c'))->redirect();
}

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

try {
    $calendar = $injector->getInstance('Kronolith_Shares')->getShare($vars->get('c'));
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    Horde::url('calendars/', true)->redirect();
}
if ($calendar->get('owner') != $GLOBALS['registry']->getAuth() &&
    (!is_null($calendar->get('owner')) || !$registry->isAdmin())) {
    $notification->push(_("You are not allowed to change this calendar."), 'horde.error');
    Horde::url('calendars/', true)->redirect();
}
$form = new Kronolith_Form_EditCalendar($vars, $calendar);

// Execute if the form is valid.
if ($form->validate($vars)) {
    $original_name = $calendar->get('name');
    try {
        $form->execute();
        if ($calendar->get('name') != $original_name) {
            $notification->push(sprintf(_("The calendar \"%s\" has been renamed to \"%s\"."), $original_name, $calendar->get('name')), 'horde.success');
        } else {
            $notification->push(sprintf(_("The calendar \"%s\" has been saved."), $original_name), 'horde.success');
        }
    } catch (Exception $e) {
        $notification->push($e, 'horde.error');
    }
    Horde::url('calendars/', true)->redirect();
}

$vars->set('name', $calendar->get('name'));
$vars->set('color', $calendar->get('color'));
$vars->set('description', $calendar->get('desc'));
$tagger = Kronolith::getTagger();
$vars->set('tags', implode(',', array_values($tagger->getTags($calendar->getName(), 'calendar'))));
$vars->set('system', is_null($calendar->get('owner')));

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_TagAutoCompleter', array(
    'id' => 'tags'
));

$menu = Kronolith::menu();
$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
echo $menu;
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/edit.php'), 'post');
$page_output->footer();
