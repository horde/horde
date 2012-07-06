<?php
/**
 * Copyright 2009-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */

require_once __DIR__ . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/ConferenceForm.php';

$curaccount = $GLOBALS['session']->get('shout', 'curaccount_code');
$action = Horde_Util::getFormData('action');
$vars = Horde_Variables::getDefaultVariables();

$RENDERER = new Horde_Form_Renderer();

$title = _("Conferences: ");

switch ($action) {
case 'add':
case 'edit':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
    $Form = new ConferenceDetailsForm($vars);

    // Show the list if the save was successful, otherwise back to edit.
    if ($Form->isSubmitted() && $Form->isValid()) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Conference information saved."),
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
    $roomno = Horde_Util::getFormData('roomno');
    $conferences = $shout->storage->getConferences($curaccount);
    $vars = new Horde_Variables($conferences[$roomno]);

    $vars->set('action', $action);

    $Form = new ConferenceDetailsForm($vars);

    // Make sure we get the right template below.
    $action = 'edit';
    break;

case 'delete':
    $title .= sprintf(_("Delete Devices %s"), $extension);
    $devid = Horde_Util::getFormData('devid');

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
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
    $vars->set('account', $curaccount);
    $Form = new DeviceDeleteForm($vars);
    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Conferences");
}

// Fetch the (possibly updated) list of extensions
try {
    $conferences = $shout->devices->getConferences($curaccount);
} catch (Exception $e) {
    $notification->push($e);
    $devices = array();
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require SHOUT_TEMPLATES . '/menu.inc';
$notification->notify();
require SHOUT_TEMPLATES . '/conferences/' . $action . '.inc';
$page_output->footer();
