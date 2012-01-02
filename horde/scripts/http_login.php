<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

// Edit the following line to match the filesystem location of your Horde
// installation.
$HORDE_DIR = '/var/www/horde';

require_once $HORDE_DIR . '/lib/Application.php';
Horde_Registry::appInit('horde');

$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

// Check for HTTP auth.
if (empty($_SERVER['PHP_AUTH_USER']) ||
    empty($_SERVER['PHP_AUTH_PW']) ||
    !$auth->authenticate($_SERVER['PHP_AUTH_USER'],
                         array('password' => $_SERVER['PHP_AUTH_PW']))) {

    header('WWW-Authenticate: Basic realm="' . $auth->getParam('realm') . '"');
    header('HTTP/1.0 401 Unauthorized');
    exit('Forbidden');
}

if ($url = Horde_Util::getFormData('url')) {
    $url = new Horde_Url($url);
} else {
    $url = Horde::url('login.php');
}

$url->redirect();
