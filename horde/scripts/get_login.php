<?php
/**
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Joel Vandal <joel@scopserv.com>
 */

// Edit the following line to match the filesystem location of your Horde
// installation.
$HORDE_DIR = '/var/www/horde';

require_once $HORDE_DIR . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Check for GET auth.
if (empty($_GET['user']) ||
    !$auth->authenticate($_GET['user'], array('password' => $_GET['pass']))) {
    Horde::authenticationFailureRedirect();
}

require HORDE_BASE . '/index.php';
