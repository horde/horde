<?php
/**
 * IMP base inclusion file. This file brings in all of the dependencies that
 * every IMP script will need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 * <pre>
 * $imp_authentication - The type of authentication to use:
 *   'horde' - Only use horde authentication
 *   'none'  - Do not authenticate
 *   'throw' - Authenticate to IMAP/POP server; on no auth, throw a
 *             Horde_Exception
 *   [DEFAULT] - Authenticate to IMAP/POP server; on no auth redirect to login
 *               screen
 * $imp_compose_page - If true, we are on IMP's compose page
 * $imp_dimp_logout - Logout and redirect to the login page.
 * $imp_no_compress - Controls whether the page should be compressed
 * $imp_session_control - Sets special session control limitations:
 *   'netscape' - TODO; start read/write session
 *   'none' - Do not start a session
 *   'readonly' - Start session readonly
 *   [DEFAULT] - Start read/write session
 * </pre>
 *
 * Global variables defined:
 *   $imp_imap    - An IMP_Imap object
 *   $imp_mbox    - Current mailbox information
 *   $imp_notify  - A Horde_Notification_Listener object
 *   $imp_search  - An IMP_Search object
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$s_ctrl = 0;
switch (Horde_Util::nonInputVar('imp_session_control')) {
case 'netscape':
    if ($browser->isBrowser('mozilla')) {
        session_cache_limiter('private, must-revalidate');
    }
    break;

case 'none':
    $s_ctrl = Horde_Registry::SESSION_NONE;
    break;

case 'readonly':
    $s_ctrl = Horde_Registry::SESSION_READONLY;
    break;
}
$registry = Horde_Registry::singleton($s_ctrl);

// Determine view mode.
$viewmode = isset($_SESSION['imp']['view'])
    ? $_SESSION['imp']['view']
    : 'imp';

// Handle dimp logout requests.
if (($viewmode == 'dimp') && Horde_Util::nonInputVar('imp_dimp_logout')) {
    Horde::redirect(str_replace('&amp;', '&', Horde::getServiceLink('logout', 'imp')));
}

// Determine imp authentication type.
$authentication = Horde_Util::nonInputVar('imp_authentication');
if ($authentication == 'horde') {
    // Autoloading for imp is not set until pushApp(), so need to add lib
    // path here explicitly.
    Horde_Autoloader::addClassPattern('/^IMP(?:$|_)/i', IMP_BASE . '/lib');
    IMP_Auth::$authType = 'horde';
}

try {
    $registry->pushApp('imp', array('check_perms' => ($authentication != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    if ($e->getCode() == Horde_Registry::AUTH_FAILURE) {
        if ($authentication == 'throw') {
            throw $e;
        }

        if ($viewmode == 'dimp') {
            // Handle session timeouts
            switch (Horde_Util::nonInputVar('session_timeout')) {
            case 'json':
                $GLOBALS['notification']->push(null, 'dimp.timeout');
                Horde::sendHTTPResponse(Horde::prepareResponse(), 'json');
                exit;

            case 'none':
                exit;

            default:
                // TODO: Redirect to login screen
                exit;
            }
        }

        if (Horde_Util::nonInputVar('imp_compose_page')) {
            $imp_compose = IMP_Compose::singleton();
            $imp_compose->sessionExpireDraft();
        }
    }

    Horde_Auth::authenticationFailureRedirect('imp', $e);
}

// All other initialization occurs in IMP::initialize().
IMP::initialize();
