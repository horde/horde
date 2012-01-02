<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Edit the following line to match the filesystem location of your Horde
// installation.
$HORDE_DIR = '/var/www/horde';

require_once $HORDE_DIR . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Check for COOKIE auth.
if (empty($_COOKIE['user']) ||
    empty($_COOKIE['password']) ||
    !$auth->authenticate($_COOKIE['user'], array('password' => $_COOKIE['password']))) {
    Horde::authenticationFailureRedirect();
}

$GLOBALS['horde_login_url'] = Horde_Util::getFormData('url');
require HORDE_BASE . '/index.php';
