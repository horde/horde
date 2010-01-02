<?php
/**
 * Copyright 2009 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
@define('SHOUT_BASE', dirname(__FILE__));
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Forms/DeviceForm.php';

$action = Horde_Util::getFormData('action');
$vars = Horde_Variables::getDefaultVariables();

//$tabs = Shout::getTabs($context, $vars);

$RENDERER = new Horde_Form_Renderer();

$title = _("Devices: ");

switch ($action) {
case 'add':
case 'edit':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('context', $context);
    $Form = new DeviceDetailsForm($vars);

    // Show the list if the save was successful, otherwise back to edit.
    if ($Form->isSubmitted() && $Form->isValid()) {
        // Form is Valid and Submitted
        try {
            $devid = Horde_Util::getFormData('devid');

            $Form->execute();
            $notification->push(_("Device information updated."),
                                  'horde.success');
            $action = 'list';
            break;

        } catch (Exception $e) {
            $notification->push($e);
        }
    } elseif ($Form->isSubmitted()) {
        // Submitted but not valid
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    // Create a new add/edit form
    $devid = Horde_Util::getFormData('devid');
    $devices = $shout_devices->getDevices($context);
    $vars = new Horde_Variables($devices[$devid]);

    $vars->set('action', $action);
    $Form = new DeviceDetailsForm($vars);
    $Form->open($RENDERER, $vars, Horde::applicationUrl('devices.php'), 'post');
    // Make sure we get the right template below.
    $action = 'edit';

    break;
case 'delete':
    $title .= sprintf(_("Delete Devices %s"), $extension);
    $devid = Horde_Util::getFormData('devid');

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('context', $context);
    $Form = new DeviceDeleteForm($vars);

    $FormValid = $Form->validate($vars, true);

    if ($Form->isSubmitted() && $FormValid) {
        try {
            $Form->execute();
            $notification->push(_("Device Deleted."));
            $action = 'list';
        } catch (Exception $e) {
            $notification->push($e);
        }
    } elseif ($Form->isSubmitted()) {
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    $vars = Horde_Variables::getDefaultVariables(array());
    $vars->set('context', $context);
    $Form = new DeviceDeleteForm($vars);
    $Form->open($RENDERER, $vars, Horde::applicationUrl('devices.php'), 'post');

    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Devices");
}

// Fetch the (possibly updated) list of extensions
$devices = $shout_devices->getDevices($context);

Horde::addScriptFile('stripe.js', 'horde');
require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo "<br>\n";

require SHOUT_TEMPLATES . '/devices/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';