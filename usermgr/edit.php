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
@define('SHOUT_BASE', dirname(__FILE__) . '/..');
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/User.php';
require_once 'Horde/Variables.php';

$RENDERER = &new Horde_Form_Renderer();

$empty = '';
$beendone = 0;
$wereerrors = 0;

$vars = &Variables::getDefaultVariables($empty);
$formname = $vars->get('formname');

$Form = &Horde_Form::singleton('UserDetailsForm', $vars);

$FormValid = $Form->validate($vars, true);

if ($Form->isSubmitted()) {
    $notification->push('Submitted.', 'horde.message');
}
if ($FormValid) {
    $notification->push('Valid.', 'horde.message');
}
$notification->notify();

if (!$FormValid || !$Form->isSubmitted()) {
    # Display the form for editing
    $Form->open($RENDERER, $vars, 'usermgr.php', 'post');
    $vars->set('section', $section);
    $Form->preserveVarByPost($vars, "section");
    // $Form->preserve($vars);
    $RENDERER->beginActive($Form->getTitle());
    $RENDERER->renderFormActive($Form, $vars);
    $RENDERER->submit();
    $RENDERER->end();
    $Form->close($RENDERER);
} else {
    # Process the Valid and Submitted form

    $info = array();
    $Form->getInfo($vars, $info);

    $name = $info['name'];
    $curextension = $info['curextension'];
    $newextension = $info['newextension'];
    $email = $info['email'];
    $pin = $info['pin'];


    $limits = $shout->getLimits($context, $curextension);

    $userdetails = array("newextension" => $newextension,
                "name" => $name,
                "pin" => $pin,
                "email" => $email);

    $i = 1;
    $userdetails['telephonenumbers'] = array();
    while ($i <= $limits['telephonenumbersmax']) {
        $tmp = $info['telephone'.$i];
        if (!empty($tmp)) {
            $userdetails['telephonenumbers'][] = $tmp;
        }
        $i++;
    }

    $userdetails['dialopts'] = array();
    if ($info['moh']) {
        $userdetails['dialopts'][] = 'm';
    }
    if ($info['transfer']) {
        $userdetails['dialopts'][] = 't';
    }
}