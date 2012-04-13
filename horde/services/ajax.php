<?php
/**
 * Processes an AJAX request and returns a JSON encoded result.
 *
 * Path Info:
 * ----------
 * http://example.com/horde/services/ajax.php/APP/ACTION
 *
 * 'APP' - (string) The application name.
 * 'ACTION' - (string) The AJAX action identifier.
 *
 * Reserved 'ACTION' strings:
 * 'logOut' - Logs user out of Horde.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde
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
    if ($action != 'logOut') {
        /* Handle session timeouts when they come from an AJAX request. */
        $injector->getInstance('Horde_Core_Factory_Ajax')->create($app, $injector->getInstance('Horde_Variables'))->sessionTimeout();
        throw $e;
    }
} catch (Exception $e) {
    // Uncaught exception.  Sending backtrace info back via AJAX is just a
    // waste of time.
    exit;
}

// Open an output buffer to ensure that we catch errors that might break JSON
// encoding.
Horde::startBuffer();

$ajax = $injector->getInstance('Horde_Core_Factory_Ajax')->create($app, $injector->getInstance('Horde_Variables'), $action);
try {
    $ajax->doAction();
} catch (Exception $e) {
    $notification->push($e->getMessage(), 'horde.error');
}

// Clear the output buffer that we started above, and log any unexpected
// output at a DEBUG level.
if ($out = Horde::endBuffer()) {
    Horde::logMessage('Unexpected output when creating AJAX reponse: ' . $out, 'DEBUG');
}

// Send the final result.
$ajax->send();
