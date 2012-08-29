<?php
/**
 * Processes an AJAX request and returns a JSON encoded result.
 *
 * Path Info:
 * ----------
 * http://example.com/horde/services/ajax.php/APP/ACTION
 *
 * 'ACTION' - (string) The AJAX action identifier.
 * 'APP' - (string) The application name.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';

list($app, $action) = explode('/', trim(Horde_Util::getPathInfo(), '/'));
if (empty($action)) {
    // This is the only case where we really don't return anything, since
    // the frontend can be presumed not to make this request on purpose.
    // Other missing data cases we return a response of boolean false.
    exit;
}

try {
    Horde_Registry::appInit($app);
} catch (Horde_Exception_AuthenticationFailure $e) {
    $response = new Horde_Core_Ajax_Response_HordeCore_SessionTimeout($app);
    $response->sendAndExit();
} catch (Exception $e) {
    // Uncaught exception.  Sending backtrace info back via AJAX is just a
    // waste of time.
    exit;
}

// Token checking.
$vars = $injector->getInstance('Horde_Variables');
try {
    $session->checkToken($vars->token);
} catch (Horde_Exception $e) {
    exit;
}

// Open an output buffer to ensure that we catch errors that might break JSON
// encoding.
Horde::startBuffer();

$ajax = $injector->getInstance('Horde_Core_Factory_Ajax')->create($app, $vars, $action);
try {
    $ajax->doAction();

    // Clear the output buffer that we started above, and log any unexpected
    // output at a DEBUG level.
    if ($out = Horde::endBuffer()) {
        Horde::logMessage('Unexpected output when creating AJAX reponse: ' . $out, 'DEBUG');
    }

    // Send the final result.
    $ajax->send();
} catch (Horde_Exception_AuthenticationFailure $e) {
    // If we reach this, authentication to Horde was successful, but
    // authentication to some underlying backend failed. Best to logout
    // immediately, since no way of knowing if error is transient.
    $response = new Horde_Core_Ajax_Response_HordeCore_NoAuth($app, $e->getCode());
    $response->sendAndExit();
} catch (Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
}
