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

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->setAnchor('calendar:internal')->redirect();
}

// Exit if this isn't an authenticated user or if the user can't
// create new calendars (default share is locked).
if (!$GLOBALS['registry']->getAuth() || $prefs->isLocked('default_share')) {
    Horde::url($prefs->getValue('defaultview') . '.php', true)->redirect();
}

$vars = Horde_Variables::getDefaultVariables();
$form = new Kronolith_Form_CreateCalendar($vars);

// Execute if the form is valid.
if ($form->validate($vars)) {
    try {
        $calendar = $form->execute();
        $notification->push(sprintf(_("The calendar \"%s\" has been created."), $vars->get('name')), 'horde.success');
        Horde::url('calendars/edit.php')
            ->add('c', $calendar->getName())
            ->redirect();
    } catch (Exception $e) {
        $notification->push($e);
    }
}

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_TagAutoCompleter', array(
    'id' => 'tags'
));

$page_output->header(array(
    'title' => $form->getTitle()
));
require KRONOLITH_TEMPLATES . '/javascript_defs.php';
$notification->notify(array('listeners' => 'status'));
echo $form->renderActive($form->getRenderer(), $vars, Horde::url('calendars/create.php'), 'post');
$page_output->footer();
