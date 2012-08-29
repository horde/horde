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

if (!$conf['enable']['rules']) {
    $notification->push(_("The Spam Rules page is not enabled."), 'horde.error');
    Horde::url('index.php', true)->redirect();
}

/* Request retrieval of related user data. */
try {
    $sam_driver = $injector->getInstance('Sam_Driver');
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
                    $stackedOptions[$attribute['basepref']] = '';
                } else {
                    $stackedOptions[$attribute['basepref']] .= "\n";
                }
                $stackedOptions[$attribute['basepref']] .=
                    $attribute['subtype'] . ' ' . $data;
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
    } catch (Sam_Exception $e) {
        $notification->push(sprintf(_("Cannot set options: %s"), $e->getMessage()), 'horde.error');
    }
}

$page_output->header(array(
    'title' => $title
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$form->renderActive($renderer, $vars, Horde::url('spam.php'), 'post');
$page_output->footer();
