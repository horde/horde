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

require_once dirname(__FILE__) . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/RecordingForm.php';

$action = Horde_Util::getFormData('action');
$curaccount = $_SESSION['shout']['curaccount'];
$recordings = $shout->storage->getRecordings($curaccount['code']);

switch($action) {
case 'add':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount['code']);
    $Form = new RecordingDetailsForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Recording added."),
                                  'horde.success');
            $recordings = $shout->storage->getRecordings($curaccount['code']);
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

Horde::addScriptFile('stripe.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/recordings/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
