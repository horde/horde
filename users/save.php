<?php
/**
 * $Horde: shout/users/add.php,v 1.0 2005/07/14 01:06:48 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 */
@define('SHOUT_BASE', dirname(__FILE__) . '/..');
require_once SHOUT_BASE . '/lib/User.php';
require_once 'Horde/Variables.php';

$RENDERER = &new Horde_Form_Renderer();

$vars = &Variables::getDefaultVariables();
$formname = $vars->get('formname');

$UserDetailsForm = &Horde_Form::singleton('UserDetailsForm', $vars);
$UserDetailsFormValid = $UserDetailsForm->validate($vars, true);
if (!$UserDetailsFormValid) {
    # FIXME Handle invalid forms gracefully
    echo "Invalid Form!";
}

$name = Util::getFormData('name');
$curextension = Util::getFormData('curextension');
$newextension = Util::getFormData('newextension');
$email = Util::getFormData('email');
$pin = Util::getFormData('pin');


$limits = $shout->getLimits($context, $curextension);

$userdetails = array("newextension" => $newextension,
              "name" => $name,
              "pin" => $pin,
              "email" => $email);

$i = 1;
$userdetails['telephonenumbers'] = array();
while ($i <= $limits['telephonenumbersmax']) {
    $tmp = Util::getFormData("telephone$i");
    if (!empty($tmp)) {
        $userdetails['telephonenumbers'][] = $tmp;
    }
    $i++;
}

$userdetails['dialopts'] = array();
if (Util::getFormData('moh')) {
    $userdetails['dialopts'][] = 'm';
}
if (Util::getFormData('transfer')) {
    $userdetails['dialopts'][] = 't';
}

// $res = $shout->saveUser($context, $curextension, $userdetails);
// if (is_a($res, 'PEAR_Error')) {
//     print $res->getMessage();
// }