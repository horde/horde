<?php
/**
 * Copyright 1999-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
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
$vars = $injector->getInstance('Horde_Variables');

// Check for HTTP auth.
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    !$auth->authenticate($_SERVER['PHP_AUTH_USER'],
                         array('password' => $_SERVER['PHP_AUTH_PW']))) {
    header('WWW-Authenticate: Basic realm="' . $auth->getParam('realm') . '"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Forbidden');
}

$url = isset($vars->url)
    ? new Horde_Url($vars->url)
    : Horde::url('login.php');
$url->redirect();
