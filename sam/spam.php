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

if (!$conf['enable']['rules']) {
    $notification->push(_("The Spam Rules page is not enabled."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('index.php'));
    exit;
}

/* Request retrieval of related user data. */
$sam_driver = $injector->getInstance('Sam_Driver');
try {
    $sam_driver->retrieve();
} catch (Sam_Exception $e) {
    $notification->push(sprintf(_("Cannot get options: %s"), $e->getMessage()), 'horde.error');
}

/* Initialize the form. */
$vars = Horde_Variables::getDefaultVariables();
$form = new Sam_Form_Options($vars);
$renderer = new Horde_Form_Renderer();
$defaults = false;

/* Page variables. */
$title = _("Spam Options");

if ($form->isSubmitted() &&
    $vars->exists('global_defaults') && $vars->get('global_defaults')) {
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
    $stackedOptions = array();

    foreach (Sam::getAttributes() as $key => $attribute) {
        if ($sam_driver->hasCapability($key) && $vars->exists($key)) {
            $data = $vars->get($key);

            if (isset($attribute['basepref'])) {
               /* SA docs claim that a null value for a rewrite string merely
                * removes any previous changes to the specified header.  This
                * should be harmless, and saves needing to add a DELETE
                * preference call in the backend when the user doesn't use
                * header rewrites. */

                /* Build string with all basepref entries, separated by
                 * newlines */
                if (!isset($stackedOptions[$attribute['basepref']])) {
                    $stackedOptions[$attribute['basepref']] = $attribute['subtype'] . " " . $data;
                } else {
                    $stackedOptions[$attribute['basepref']] .= "\n" . $attribute['subtype'] . " " . $data;
                }
            } elseif ($attribute['type'] == 'boolean') {
                $sam_driver->setOption($key, $sam_driver->booleanToOption($data), $defaults);
            } elseif ($attribute['type'] == 'number') {
                $sam_driver->setOption($key, number_format($data, 1, '.', ''), $defaults);
            } else {
                $sam_driver->setOption($key, $data, $defaults);
            }
        }
    }

    /* All form fields have been processed, so push the resulting strings to
     * the backend. */
    foreach ($stackedOptions as $key => $data) {
        $sam_driver->setStackedOption($key, $data);
    }

    try {
        $sam_driver->store($defaults);
        if ($defaults) {
            $notification->push(_("Updated global default rules"), 'horde.success');
        } else {
            $notification->push(_("Updated user spam rules"), 'horde.success');
        }
    } catch (Sam_Exception($e)) {
        $notification->push(sprintf(_("Cannot set options: %s"), $e->getMessage()), 'horde.error');
    }
}

require SAM_TEMPLATES . '/common-header.inc';
require SAM_TEMPLATES . '/menu.inc';
$form->renderActive($renderer, $vars, 'spam.php', 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
