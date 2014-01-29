<?php
/**
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Joel Vandal <joel@scopserv.com>
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

// Check for GET auth.
if (empty($_GET['user']) ||
    !$auth->authenticate($_GET['user'], array('password' => $_GET['pass']))) {
    $e = new Horde_Exception_AuthenticationFailure();
    $e->application = 'horde';
    throw $e;
}

require HORDE_BASE . '/index.php';
