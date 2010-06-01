<?php
/**
 * $Id: renew.php 918 2008-09-25 02:18:59Z duck $
 *
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/tabs.php';

$title = _("Renew account");

// Get supplied code
$code = Horde_Util::getGet('code');
if (empty($code)) {
    $notification->push(_("You must supply a confirmation code."));
    $registry->authenticateFailure('folks');
}

// Get supplied username
$user = Horde_Util::getGet('user');
if (empty($code)) {
    $notification->push(_("You must supply a username."));
    $registry->authenticateFailure('folks');
}

// Get user profile
$profile = $folks_driver->getProfile($user);
if ($profile instanceof PEAR_Error) {
    $notification->push($profile);
    $registry->authenticateFailure('folks');
}

// This pages is only to activate users
if ($profile['user_status'] != 'deleted') {
    $notification->push(_("User \"%s\" is not market to be in the removal process."));
    $registry->authenticateFailure('folks');
}

// Get internal confirmation code
$internal_code = $folks_driver->getConfirmationCode($user, 'renew');
if ($internal_code instanceof PEAR_Error) {
    $notification->push($internal_code);
    $registry->authenticateFailure('folks');
}

// Check code
if ($internal_code == $code) {
    $update = $folks_driver->saveProfile(array('user_status' => 'active', $user));
    if ($update instanceof PEAR_Error) {
        $notification->push($update);
    } else {
        $notification->push(_("You account is activated, you can login now."), 'horde.success');
    }
} else {
    $notification->push(_("The code is not right. If you copy and paste the link from your email, please check if you copied the whole string."), 'horde.warning');
}

$registry->authenticateFailure('folks');
