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

$wereerrors = 0;

$vars = &Variables::getDefaultVariables();
$formname = $vars->get('formname');
print_r($vars);

$UserDetailsForm = &Horde_Form::singleton('UserDetailsForm', $vars);
$UserDetailsFormValid = $UserDetailsForm->validate($vars, true);
if (!$UserDetailsFormValid) {
    # FIXME Handle invalid forms gracefully
    echo "Invalid Form!";
}

$oldextension = Util::getFormData('oldextension');
$extension = Util::getFormData('extension');

#$user = &new Shout_User();

#$user->define($context, $oldextension);
#$user->set('name', Util::getFormData('name'));
#$user->set('extension', Util::getFormData('extension'));
#$user->set('telephone'
