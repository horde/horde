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
$default = Horde::url($prefs->getValue('defaultview') . '.php', true);
if (!$GLOBALS['registry']->getAuth()) {
    $default->redirect();
}

try {
    $calendar = $injector->getInstance('Kronolith_Shares')->getShare($vars->get('c'));
} catch (Exception $e) {
    $notification->push($e, 'horde.error');
    $default->redirect();
}
$owner = $calendar->get('owner') == $GLOBALS['registry']->getAuth() ||
    (is_null($calendar->get('owner')) && $GLOBALS['registry']->isAdmin());
if (!$owner &&
    !$calendar->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You are not allowed to see this calendar."), 'horde.error');
    $default->redirect();
}
$form = new Kronolith_Form_EditCalendar($vars, $calendar);

// Execute if the form is valid.
if ($owner && $form->validate($vars)) {
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
    $default->redirect();
}

$vars->set('name', $calendar->get('name'));
$vars->set('color', $calendar->get('color'));
$vars->set('system', is_null($calendar->get('owner')));
$vars->set('description', $calendar->get('desc'));
$tagger = Kronolith::getTagger();
$vars->set('tags', implode(',', array_values($tagger->getTags($calendar->getName(), 'calendar'))));

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
if ($owner) {
    $injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_TagAutoCompleter', array(
        'id' => 'tags'
    ));
    echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/edit.php'), 'post');
} else {
    echo $form->renderInactive($form->getRenderer(), $vars);
}
$page_output->footer();
