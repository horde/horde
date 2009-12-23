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
        print_r($vars);
        $Form = new DeviceDetailsForm($vars);
        print_r($Form);
        // Show the list if the save was successful, otherwise back to edit.
        $success = ($Form->isSubmitted() && $Form->isValid());
        if ($success) {
            $notification->push(_("Device settings saved."));
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