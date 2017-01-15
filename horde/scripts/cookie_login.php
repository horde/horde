<?php
/**
 * Copyright 2005-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
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
