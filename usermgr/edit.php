<?php
/**
 * $Id$
 *
 * Copyright 2005-2006 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package shout
 */
if (!isset($SHOUT_RUNNING) || !$SHOUT_RUNNING) {
    header('Location: /');
    exit();
}

require_once SHOUT_BASE . '/lib/User.php';
require_once 'Horde/Variables.php';

$RENDERER = &new Horde_Form_Renderer();

$empty = '';
$beendone = 0;
$wereerrors = 0;

$vars = &Variables::getDefaultVariables($empty);

$FormName = 'UserDetailsForm';
$Form = &Horde_Form::singleton($FormName, $vars);
if (is_a($Form, 'PEAR_Error')) {
    $notification->push($Form);
} else {
    $FormValid = $Form->validate($vars, true);
    if (is_a($FormValid, 'PEAR_Error')) {
        $notification->push($FormValid);
    } else {
        $Form->fillUserForm(&$vars, $extension);
    }
}


$notification->notify();

if (!$FormValid || !$Form->isSubmitted()) {
    # Display the form for editing
    $Form->open($RENDERER, $vars, 'index.php', 'post');
    $Form->preserveVarByPost(&$vars, 'extension');
    $Form->preserveVarByPost(&$vars, 'context');
    $Form->preserveVarByPost(&$vars, 'section');
    $RENDERER->beginActive($Form->getTitle());
    $RENDERER->renderFormActive($Form, $vars);
    $RENDERER->submit();
    $RENDERER->end();
    $Form->close($RENDERER);
} else {
    # Process the Valid and Submitted form
$notification->push("How did we get HERE?!", 'horde.error');
$notification->notify();
//     $info = array();
//     $Form->getInfo($vars, $info);
//
//     $name = $info['name'];
//     $extension = $info['extension'];
//     $newextension = $info['newextension'];
//     $email = $info['email'];
//     $pin = $info['pin'];
//
//
//     $limits = $shout->getLimits($context, $extension);
//
//     $userdetails = array("newextension" => $newextension,
//                 "name" => $name,
//                 "pin" => $pin,
//                 "email" => $email);
//
//     $i = 1;
//     $userdetails['telephonenumbers'] = array();
//     while ($i <= $limits['telephonenumbersmax']) {
//         $tmp = $info['telephone'.$i];
//         if (!empty($tmp)) {
//             $userdetails['telephonenumbers'][] = $tmp;
//         }
//         $i++;
//     }
//
//     $userdetails['dialopts'] = array();
//     if ($info['moh']) {
//         $userdetails['dialopts'][] = 'm';
//     }
//     if ($info['transfer']) {
//         $userdetails['dialopts'][] = 't';
//     }
}