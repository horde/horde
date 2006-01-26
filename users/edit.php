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

$title = _("FIXME " . __FILE__.":".__LINE__);

$UserDetailsForm = &Horde_Form::singleton('UserDetailsForm', $vars);

$UserDetailsFormValid = $UserDetailsForm->validate($vars, true);

if (!$UserDetailsFormValid) {
    $UserDetailsForm->open($RENDERER, $vars, 'users.php', 'post');
    $vars->set('section', $section);
    $UserDetailsForm->preserveVarByPost($vars, "section");
    // $UserDetailsForm->preserve($vars);
    $RENDERER->beginActive($UserDetailsForm->getTitle());
    $RENDERER->renderFormActive($UserDetailsForm, $vars);
    $RENDERER->submit();
    $RENDERER->end();
    $UserDetailsForm->close($RENDERER);
} else {

//     require WHUPS_TEMPLATES . '/common-header.inc';
//     require WHUPS_TEMPLATES . '/menu.inc';
    $info = array();
    $UserDetailsForm->getInfo($vars, $info);

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