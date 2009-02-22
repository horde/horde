<?php
/**
 * $Id: tabs.php 918 2008-09-25 02:18:59Z duck $
 *
 * Copyright 2007 Obala d.o.o. (http://www.obala.si/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Duck <duck@obala.net>
 */

define('AUTH_HANDLER', true);
define('FOLKS_BASE', dirname(__FILE__) . '/..');
require_once FOLKS_BASE . '/lib/base.php';
require_once 'Horde/Variables.php';

$auth = Auth::singleton($conf['auth']['driver']);

$vars = Variables::getDefaultVariables();
$tabs = new Horde_UI_Tabs('what', $vars);
$tabs->addTab(_("Login"), Horde::applicationUrl('login.php'), 'login');

if ($conf['signup']['allow'] === true && $auth->hasCapability('add')) {
    $tabs->addTab(_("Don't have an account? Sign up."), Horde::applicationUrl('account/signup.php'), 'signup');
}

if ($auth->hasCapability('resetpassword')) {
    $tabs->addTab(_("Forgot your password?"), Horde::applicationUrl('account/resetpassword.php'), 'resetpassword');
}

$tabs->addTab(_("Forgot your username?"), Horde::applicationUrl('account/username.php'), 'username');
