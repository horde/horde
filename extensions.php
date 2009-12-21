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
//require_once SHOUT_BASE . '/lib/Shout.php';

$action = Horde_Util::getFormData('action');
$extension = Horde_Util::getFormData('extension');

$vars = Horde_Variables::getDefaultVariables();

$tabs = Shout::getTabs($context, $vars);

$RENDERER = new Horde_Form_Renderer();

$section = 'extensions';
$title = _("Extensions: ");

switch ($action) {
    case 'add':
        $title .= _("Add Extension");

        # Treat adds just like an empty edit
        $action = 'edit';
        $extension = 0;
    case 'edit':
        $title .= sprintf(_("Edit Extension %s"), $extension);

        $beendone = 0;
        $wereerrors = 0;

        $FormName = 'UserDetailsForm';
        $Form = &Horde_Form::singleton($FormName, $vars);
        if (is_a($Form, 'PEAR_Error')) {
            $notification->push($Form);
        } else {
            $FormValid = $Form->validate($vars, true);
            if (is_a($FormValid, 'PEAR_Error')) {
                $notification->push($FormValid);
            } else {
                $Form->fillUserForm($vars, $extension);
            }
        }


        if (!$FormValid || !$Form->isSubmitted()) {
            # Display the form for editing
            $Form->open($RENDERER, $vars, 'index.php', 'post');
            $Form->preserveVarByPost($vars, 'extension');
            $Form->preserveVarByPost($vars, 'context');
            $Form->preserveVarByPost($vars, 'section');
            $RENDERER->beginActive($Form->getTitle());
            $RENDERER->renderFormActive($Form, $vars);
            $RENDERER->submit();
            $RENDERER->end();
            $Form->close($RENDERER);
        } else {
            # Process the Valid and Submitted form
            $notification->push("How did we get HERE?!", 'horde.error');
        }
        
        break;
    case 'save':
        $title .= sprintf(_("Save Extension %s"), $extension);
        $FormName = $vars->get('formname');

        $Form = &Horde_Form::singleton($FormName, $vars);

        $FormValid = $Form->validate($vars, true);

        if (!$FormValid || !$Form->isSubmitted()) {
            require SHOUT_BASE . '/usermgr/edit.php';
        } else {
            # Form is Valid and Submitted
            $extension = $vars->get('extension');

            $limits = $shout->getLimits($context, $extension);

            # FIXME: Input Validation (Text::??)
            $userdetails = array(
                "newextension" => $vars->get('newextension'),
                "name" => $vars->get('name'),
                "mailboxpin" => $vars->get('mailboxpin'),
                "email" => $vars->get('email'),
                "uid" => $vars->get('uid'),
            );

            $userdetails['telephonenumber'] = array();
            $telephonenumber = $vars->get("telephonenumber");
            if (!empty($telephonenumber) && is_array($telephonenumber)) {
                $i = 1;
                while ($i <= $limits['telephonenumbersmax']) {
                    if (!empty($telephonenumber[$i])) {
                        $userdetails['telephonenumber'][] = $telephonenumber[$i++];
                    } else {
                        $i++;
                    }
                }
            }

            $userdetails['dialopts'] = array();

            $res = $shout->saveUser($context, $extension, $userdetails);
            if (is_a($res, 'PEAR_Error')) {
                $notification->push($res);
            } else {
                $notification->push(_("User information updated."),
                                      'horde.success');
            }

        }

        break;
    case 'delete':
        $title .= sprintf(_("Delete Extension %s"), $extension);
        $context = Horde_Util::getFormData('context');
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
        $extensions = $shout_extensions->getExtensions($context);
}

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo $tabs->render($section);

require SHOUT_TEMPLATES . '/extensions/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
