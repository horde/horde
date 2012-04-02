<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('sam');

/* Request retrieval of related user data. */
try {
    $sam_driver = $injector->getInstance('Sam_Driver');
    $sam_driver->retrieve();
} catch (Sam_Exception $e) {
    $notification->push(sprintf(_("Cannot get options: %s"), $e->getMessage()), 'horde.error');
}

/* Initialize the form. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Sam_Form_Blacklist($vars);
$renderer = new Horde_Form_Renderer();
$defaults = false;

/* Page variables. */
$title = _("Black List Manager");

if ($form->isSubmitted() &&
    $vars->exists('global_defaults') &&
    $vars->get('global_defaults')) {
    if (!$registry->isAdmin()) {
        $notification->push(_("Only an administrator may change the global defaults."), 'horde.error');
        $vars->remove('global_defaults');
        $form->setSubmitted(false);
    } elseif (!$sam_driver->hasCapability('global_defaults')) {
        $notification->push(_("The configured backend does not support global defaults."), 'horde.error');
        $vars->remove('global_defaults');
        $form->setSubmitted(false);
    } else {
        $defaults = true;
    }
}

if ($form->validate($vars)) {
    foreach (array('blacklist_from', 'blacklist_to') as $key) {
        if ($sam_driver->hasCapability($key) && $vars->exists($key)) {
            $sam_driver->setListOption($key, $vars->get($key), $defaults);
        }
    }

    try {
        $sam_driver->store($defaults);
        if ($defaults) {
            $notification->push(_("Updated global blacklists"), 'horde.success');
        } else {
            $notification->push(_("Updated user blacklists"), 'horde.success');
        }
    } catch (Sam_Exception $e) {
        $notification->push(sprintf(_("Cannot set options: %s"), $e->getMessage()), 'horde.error');
    }
}

$page_output->header(array(
    'title' => $title
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, Horde::url('blacklist.php'), 'post');
$page_output->footer();
