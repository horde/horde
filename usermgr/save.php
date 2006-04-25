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

$vars = &Variables::getDefaultVariables();
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

    if ($vars->get('moh')) {
        $userdetails['dialopts'][] = 'm';
    }
    if ($vars->get('transfer')) {
        $userdetails['dialopts'][] = 't';
    }
    if ($vars->get('eca')) {
        $userdetails['dialopts'][] = 'e';
    }
    $res = $shout->saveUser($context, $extension, $userdetails);
    if (is_a($res, 'PEAR_Error')) {
        $notification->push($res);
    } else {
        $notification->push('User information updated.  '.
            'Changes will take effect within 10 minutes',
            'horde.success');
    }

    $notification->notify();
}
