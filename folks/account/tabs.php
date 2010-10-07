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

$folks_authentication = 'none';
require_once dirname(__FILE__) . '/../lib/base.php';

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

$vars = Horde_Variables::getDefaultVariables();
$tabs = new Horde_Core_Ui_Tabs('what', $vars);
$tabs->addTab(_("Login"), Horde::url('login.php'), 'login');

if ($conf['signup']['allow'] === true && $auth->hasCapability('add')) {
    $tabs->addTab(_("Don't have an account? Sign up."), Horde::url('account/signup.php'), 'signup');
}

if ($auth->hasCapability('resetpassword')) {
    $tabs->addTab(_("Forgot your password?"), Horde::url('account/resetpassword.php'), 'resetpassword');
}

$tabs->addTab(_("Forgot your username?"), Horde::url('account/username.php'), 'username');
