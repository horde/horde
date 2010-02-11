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
$registry = new Horde_Registry();
try {
    $registry->pushApp('kastalia', array('logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('kastalia', $e);
}
$conf = &$GLOBALS['conf'];
@define('KASTALIA_TEMPLATES', $registry->get('templates'));

// Define the base file path of Kastalia.
@define('KASTALIA_BASE', dirname(__FILE__) . '/..');

// Start output compression.
Horde::compressOutput();
