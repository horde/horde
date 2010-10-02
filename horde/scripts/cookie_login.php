<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$auth = $injector->getInstance('Horde_Auth_Factory')->getAuth();

// Check for COOKIE auth.
if (empty($_COOKIE['user']) ||
    empty($_COOKIE['password']) ||
    !$auth->authenticate($_COOKIE['user'], array('password' => $_COOKIE['password']))) {
    Horde::authenticationFailureRedirect();
}

$GLOBALS['horde_login_url'] = Horde_Util::getFormData('url');
require HORDE_BASE . '/index.php';
