<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/AccountForm.php';

$action = Horde_Util::getFormData('action');
$curaccount = $GLOBALS['session']->get('shout', 'curaccount');

$RENDERER = new Horde_Form_Renderer();

$title = _("Accounts: ");

switch ($action) {
case 'add':
case 'edit':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
    $Form = new AccountDetailsForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Account information saved."),
                                  'horde.success');
            $action = 'list';
        } catch (Exception $e) {
            $notification->push($e);
        }
        break;
    } elseif ($Form->isSubmitted()) {
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    // Create a new add/edit form
    $account = Horde_Util::getFormData('extension');
    $accounts = $shout->storage->getAccounts();
    $vars = new Horde_Variables(array('code' => $account, 'name' => $accounts[$account]['name']));
    $vars->set('action', $action);

    // Make sure we get the right template below.
    $action = 'edit';
    break;

case 'delete':
    $title .= sprintf(_("Delete Extension %s"), $extension);
    $extension = Horde_Util::getFormData('extension');

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount);
    $Form = new ExtensionDeleteForm($vars);

    $FormValid = $Form->validate($vars, true);

    if ($Form->isSubmitted() && $FormValid) {
        try {
            $Form->execute();
            $notification->push(_("Extension Deleted."));
            $action = 'list';
        } catch (Exception $e) {
            $notification->push($e);
        }
    } elseif ($Form->isSubmitted()) {
        // Submitted but not valid
        $notification->push(_("Problem processing the form.  Please check below and try again."), 'horde.warning');
    }

    $vars = Horde_Variables::getDefaultVariables(array());
    $vars->set('account', $curaccount);
    $Form = new ExtensionDeleteForm($vars);

    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Accounts");
}


// Fetch the (possibly updated) list of extensions
try {
    $accounts = $shout->storage->getAccounts();
} catch (Exception $e) {
    $notification->push($e);
    $extensions = array();
}

Horde::addScriptFile('stripe.js', 'horde');

require $registry->get('templates', 'horde') . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

Shout::getAdminTabs();

require SHOUT_TEMPLATES . '/admin/accounts/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
