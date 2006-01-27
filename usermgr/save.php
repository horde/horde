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
    $name = $vars->get('name');
    $curexten = $vars->get('curexten');
    $newexten = $vars->get('newexten');
    $email = $vars->get('email');
    $pin = $vars->get('pin');


    $limits = $shout->getLimits($context, $curexten);

    $userdetails = array("newexten" => $newexten,
                "name" => $name,
                "pin" => $pin,
                "email" => $email);

    $i = 1;
    $userdetails['telephonenumbers'] = array();
    while ($i <= $limits['telephonenumbersmax']) {
        $tmp = $vars->get("telephone$i");
        $notification->push('Number: '.$tmp, 'horde.warning');
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
    $notification->notify();
    print_r($userdetails);
}
// $res = $shout->saveUser($context, $curexten, $userdetails);
// if (is_a($res, 'PEAR_Error')) {
//     $notification->push($res);
// }