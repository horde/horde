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
$devices = $shout_devices->getDevices($context);
$devid = Horde_Util::getFormData('devid');
$vars = Horde_Variables::getDefaultVariables();

//$tabs = Shout::getTabs($context, $vars);

$RENDERER = new Horde_Form_Renderer();

$section = 'devices';
$title = _("Devices: ");

switch ($action) {
    case 'save':
        $Form = new DeviceDetailsForm($vars);

        // Show the list if the save was successful, otherwise back to edit.
        if ($Form->isSubmitted() && $Form->isValid()) {
            try {
                $shout_devices->saveDevice($Form->getVars());
                $notification->push(_("Device settings saved."));
            } catch (Exception $e) {
                $notification->push($e);
            }
            $action = 'list';
            break;
        } else {
            $action = 'edit';
        }
    case 'add':
    case 'edit':
        if ($action == 'add') {
            $title .= _("New Device");
            // Treat adds just like an empty edit
            $action = 'edit';
        } else {
            $title .= sprintf(_("Edit Device %s"), $extension);

        }

        $FormName = 'DeviceDetailsForm';
        $vars = new Horde_Variables($devices[$devid]);
        $Form = new DeviceDetailsForm($vars);

        $Form->open($RENDERER, $vars, Horde::applicationUrl('devices.php'), 'post');

        break;


    case 'delete':
        $notification->push("Not supported.");
        break;

    case 'list':
    default:
        $action = 'list';
        $title .= _("List Users");
}

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

//echo $tabs->render($section);

require SHOUT_TEMPLATES . '/devices/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';