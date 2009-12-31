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
$extension = Horde_Util::getFormData('extension');
$extensions = $shout_extensions->getExtensions($context);

$vars = Horde_Variables::getDefaultVariables();

//$tabs = Shout::getTabs($context, $vars);

$RENDERER = new Horde_Form_Renderer();

$section = 'extensions';
$title = _("Extensions: ");

switch ($action) {
case 'save':
    $title .= sprintf(_("Save Extension %s"), $extension);
    $FormName = $vars->get('formname');

    $Form = &Horde_Form::singleton($FormName, $vars);

    $FormValid = $Form->validate($vars, true);

    if ($Form->isSubmitted() && $FormValid) {
        // Form is Valid and Submitted
        try {
            $Form->execute();
        } catch (Exception $e) {
            $notification->push($e);
        }
        $notification->push(_("User information updated."),
                                  'horde.success');
        break;
     } else {
         $action = 'edit';
         // Fall-through to the "edit" action
     }

case 'add':
case 'edit':
    if ($action == 'add') {
        $title .= _("New Extension");
        // Treat adds just like an empty edit
        $action = 'edit';
    } else {
        $title .= sprintf(_("Edit Extension %s"), $extension);

    }

    $FormName = 'ExtensionDetailsForm';
    $vars = new Horde_Variables($extensions[$extension]);
    $Form = &Horde_Form::singleton($FormName, $vars);

    $Form->open($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');

    break;

case 'delete':
    $title .= sprintf(_("Delete Extension %s"), $extension);
    $extension = Horde_Util::getFormData('extension');

    $res = $shout->deleteUser($context, $extension);

    if (!$res) {
        echo "Failed!";
        print_r($res);
    }
    $notification->push("User Deleted.");
    break;

case 'list':
default:
    $action = 'list';
    $title .= _("List Users");
}

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo "<br>\n";

require SHOUT_TEMPLATES . '/extensions/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';