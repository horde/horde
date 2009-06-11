<?php
/**
 * Kastalia base application file.
 *
 * This product includes software developed by the Horde Project (http://www.horde.org/).
 *
 * This file brings in all of the dependencies that every Kastalia script will
 * need, and sets up objects that all scripts use.
 *
 * @author  Andre Pawlowski aka sqall <sqall@h4des.org>
 */

// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = &Registry::singleton();
if (is_a(($pushed = $registry->pushApp('kastalia', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect();
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}
$conf = &$GLOBALS['conf'];
@define('KASTALIA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = &Notification::singleton();
$notification->attach('status');

// Define the base file path of Kastalia.
@define('KASTALIA_BASE', dirname(__FILE__) . '/..');

// Kastalia base library
require_once KASTALIA_BASE . '/lib/Kastalia.php';

// Start output compression.
Horde::compressOutput();
