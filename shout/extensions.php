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

require_once dirname(__FILE__) . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/ExtensionForm.php';

$action = Horde_Util::getFormData('action');
$curaccount = $_SESSION['shout']['curaccount'];

$RENDERER = new Horde_Form_Renderer();

$section = 'extensions';
$title = _("Extensions: ");

switch ($action) {
case 'add':
case 'edit':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount['code']);
    $Form = new ExtensionDetailsForm($vars);

    if ($Form->isSubmitted() && $Form->validate($vars, true)) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("Extension information updated."),
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
    $extension = Horde_Util::getFormData('extension');
    $extensions = $shout->extensions->getExtensions($curaccount['code']);
    $vars = new Horde_Variables($extensions[$extension]);
    if ($action == 'edit') {
        $vars->set('oldextension', $extension);
    }
    $vars->set('action', $action);
    //$Form = new ExtensionDetailsForm($vars);
    // Make sure we get the right template below.
    $action = 'edit';

    break;

case 'delete':
    $title .= sprintf(_("Delete Extension %s"), $extension);
    $extension = Horde_Util::getFormData('extension');

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('account', $curaccount['code']);
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
    $vars->set('account', $curaccount['code']);
    $Form = new ExtensionDeleteForm($vars);

    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Users");
}


// Fetch the (possibly updated) list of extensions
try {
    $extensions = $shout->extensions->getExtensions($curaccount['code']);
} catch (Exception $e) {
    $notification->push($e);
    $extensions = array();
}

Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('prototype.js', 'horde');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

require SHOUT_TEMPLATES . '/extensions/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
