<?php
/**
 * $Id$
 *
 * Copyright 2005-2009 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
@define('SHOUT_BASE', dirname(__FILE__));
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Forms/ExtensionForm.php';
//require_once SHOUT_BASE . '/lib/Shout.php';

$action = Horde_Util::getFormData('action');

//$tabs = Shout::getTabs($context, $vars);

$RENDERER = new Horde_Form_Renderer();

$section = 'extensions';
$title = _("Extensions: ");

switch ($action) {
case 'add':
case 'edit':
    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('context', $context);
    $Form = new ExtensionDetailsForm($vars);

    $FormValid = $Form->validate($vars, true);

    if ($Form->isSubmitted() && $FormValid) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
            $notification->push(_("User information updated."),
                                  'horde.success');
            $action = 'list';
        } catch (Exception $e) {
            $notification->push($e);
        }
    } else {
        // Create a new add/edit form
        $extension = Horde_Util::getFormData('extension');
        $extensions = $shout_extensions->getExtensions($context);
        $vars = new Horde_Variables($extensions[$extension]);
        if ($action == 'edit') {
            $vars->set('oldextension', $extension);
        }
        $vars->set('action', $action);
        $Form = new ExtensionDetailsForm($vars);
        $Form->open($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');
        // Make sure we get the right template below.
        $action = 'edit';
    }
    break;

case 'delete':
    $title .= sprintf(_("Delete Extension %s"), $extension);
    $extension = Horde_Util::getFormData('extension');

    $vars = Horde_Variables::getDefaultVariables();
    $vars->set('context', $context);
    $Form = new ExtensionDeleteForm($vars);

    $FormValid = $Form->validate($vars, true);

    if ($Form->isSubmitted() && $FormValid) {
        try {
            $Form->execute();
            $notification->push(_("Extension Deleted."));
        } catch (Exception $e) {
            $notification->push($e);
            $action = 'list';
        }
    } else {
        $vars = Horde_Variables::getDefaultVariables(array());
        $Form = new ExtensionDeleteForm($vars);
        $Form->open($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');
    }

    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Users");
}

// Fetch the (possibly updated) list of extensions
$extensions = $shout_extensions->getExtensions($context);

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo "<br>\n";

require SHOUT_TEMPLATES . '/extensions/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';