<?php
/**
 * Kronolith base inclusion file.
 *
 * This file brings in all of the dependencies that every Kronolith
 * script will need, and sets up objects that all scripts use.
 *
 * The following variables, defined in the script that calls this one, are
 * used:
 * - $session_control - Sets special session control limitations
 *
 * @package Kronolith
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

/* Load the Horde Framework core. */
require_once HORDE_BASE . '/lib/core.php';

/* Registry. */
$session_control = Horde_Util::nonInputVar('session_control');
if ($session_control == 'none') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_NONE);
} elseif ($session_control == 'readonly') {
    $registry = Horde_Registry::singleton(Horde_Registry::SESSION_READONLY);
} else {
    $registry = Horde_Registry::singleton();
}

if (is_a(($pushed = $registry->pushApp('kronolith', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
define('KRONOLITH_TEMPLATES', $registry->get('templates'));

/* Notification system. */
$notification = &Horde_Notification::singleton();
$GLOBALS['kronolith_notify'] = &$notification->attach('status', null, 'Kronolith_Notification_Listener_Status');

/* Kronolith base library. */
require_once KRONOLITH_BASE . '/lib/Kronolith.php';
require_once KRONOLITH_BASE . '/lib/Driver.php';

/* Start compression, if requested. */
Horde::compressOutput();

/* Set the timezone variable, if available. */
NLS::setTimeZone();

/* Create a share instance. */
$GLOBALS['kronolith_shares'] = &Horde_Share::singleton($registry->getApp());

Kronolith::initialize();

// TODO - Maintenance operations need to be refactored to a more global
//        operation and then wen can get rid of these hackish checks
/* Do login tasks - need to check for a number of conditions to be
 * sure that we aren't here due to alarm notifications (which would occur after
 * headers are sent), we aren't on any of the portal pages, and that we haven't
 * already performed login tasks.
 */
if (empty($no_maint) && Kronolith::loginTasksFlag() &&
    !strstr($_SERVER['PHP_SELF'], 'maintenance.php') &&
    !headers_sent() && !defined('AUTH_HANDLER')) {
    Kronolith::loginTasksFlag(2);

    $tasks = &Horde_LoginTasks::singleton('kronolith', Horde_Util::addParameter(Horde::selfUrl(true, true, true), array('logintasks_done' => true)));
    $tasks->runTasks();

    Kronolith::loginTasksFlag(0);
} elseif (Horde_Util::getFormData('logintasks_done') &&
          Kronolith::loginTasksFlag()) {
    $tasks = &Horde_LoginTasks::singleton('kronolith', Horde_Util::addParameter(Horde::selfUrl(true, true, true), array('logintasks_done' => true)));
    $tasks->runTasks();

    Kronolith::loginTasksFlag(0);
}
