<?php
/**
 * Copyright 2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */

require_once __DIR__ . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/RecordingForm.php';

$action = Horde_Util::getFormData('action');
$curaccount = $GLOBALS['session']->get('shout', 'curaccount_code');
$recordings = $shout->storage->getRecordings($curaccount);

switch($action) {
case 'add':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
    $Form = new RecordingDetailsForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Recording added."),
                                  'horde.success');
            $recordings = $shout->storage->getRecordings($curaccount);
            $action = 'list';
        } catch (Exception $e) {
            $notification->push($e);
        }
        break;
    } elseif ($Form->isSubmitted()) {
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    // Create a new add form
    $vars = new Horde_Variables();
    $vars->set('action', $action);
    break;

case 'list':
default:
    $action = 'list';
    break;
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require SHOUT_TEMPLATES . '/menu.inc';
$notification->notify();
require SHOUT_TEMPLATES . '/recordings/' . $action . '.inc';
$page_output->footer();
