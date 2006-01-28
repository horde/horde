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

    $userdetails = array(
        "newextension" => $vars->get('newextension'),
        "name" => $vars->get('name'),
        "pin" => $vars->get('pin'),
        "email" => $vars->get('email'),
    );

    $i = 1;
    $userdetails['telephonenumbers'] = array();
    while ($i <= $limits['telephonenumbersmax']) {
        $tmp = $vars->get("telephone$i");
        if (!empty($tmp)) {
            $userdetails['telephonenumbers'][] = $tmp;
        }
        $i++;
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
    $res = $shout->saveUser($context, $extension, $userdetails);
    if (is_a($res, 'PEAR_Error')) {
        $notification->push($res);
    } else {
        $notification->push('User information updated.', 'horde.success');
    }
    $notification->notify();

    require SHOUT_BASE . '/usermgr/edit.php';
}
