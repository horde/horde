<?php
/**
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chris Bowlby <cbowlby@tenthpowertech.com>
 */

/* Determine base directory. */
@define('SAM_BASE', dirname(__FILE__));
require_once SAM_BASE . '/lib/base.php';

/* Request retrieval of related user data. */
$result = $sam_driver->retrieve();
if (is_a($result, 'PEAR_Error')) {
    $notification->push(sprintf(_("Cannot get options: %s"), $result->getMessage()), 'horde.error');
}

/* Initialize the form. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Sam_Form_Blacklist($vars);
$renderer = new Horde_Form_Renderer();
$defaults = false;

/* Page variables. */
$title = _("Black List Manager");

if ($form->isSubmitted() &&
    $vars->exists('global_defaults') && $vars->get('global_defaults')) {
    if (!Horde_Auth::isAdmin()) {
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

    $result = $sam_driver->store($defaults);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push(sprintf(_("Cannot set options: %s"), $result->getMessage()), 'horde.error');
    } elseif ($defaults) {
        $notification->push(_("Updated global blacklists"), 'horde.success');
    } else {
        $notification->push(_("Updated user blacklists"), 'horde.success');
    }
}

require SAM_TEMPLATES . '/common-header.inc';
require SAM_TEMPLATES . '/menu.inc';
$form->renderActive($renderer, $vars, 'blacklist.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
