<?php
/**
 * Turba base inclusion file.
 *
 * This file brings in all of the dependencies that every Turba script will
 * need, and sets up objects that all scripts use.
 *
 * The following global variables are used:
 * <pre>
 * $ingo_authentication - The type of authentication to use:
 *   'none'  - Do not authenticate
 *   [DEFAULT] - Authenticate; on failed auth redirect to login screen
 * $no_compress - Controls whether the page should be compressed
 * </pre>
 */

// Determine BASE directories.
require_once dirname(__FILE__) . '/base.load.php';

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = Horde_Registry::singleton();
try {
    $registry->pushApp('turba', array('check_perms' => (Horde_Util::nonInputVar('turba_authentication') != 'none'), 'logintasks' => true));
} catch (Horde_Exception $e) {
    Horde_Auth::authenticateFailure('turba', $e);
}
$conf = $GLOBALS['conf'];
define('TURBA_TEMPLATES', $registry->get('templates'));

// Notification system.
$notification = Horde_Notification::singleton();
$notification->attach('status');

// Turba source and attribute configuration.
include TURBA_BASE . '/config/attributes.php';
include TURBA_BASE . '/config/sources.php';

// Ensure we have cfgSources in global scope since base.php might be loaded
// within a function scope and the share hooks require access to cfgSources.
$GLOBALS['cfgSources'] = $cfgSources;

// See if any of our sources are configured to use Horde_Share.
foreach ($cfgSources as $key => $cfg) {
    if (!empty($cfg['use_shares'])) {
        $_SESSION['turba']['has_share'] = true;
        break;
    }
}
if (!empty($_SESSION['turba']['has_share'])) {
    // Create a share instance.
    $GLOBALS['turba_shares'] = Horde_Share::singleton($registry->getApp());
    $GLOBALS['cfgSources'] = Turba::getConfigFromShares($cfgSources);
}
$GLOBALS['cfgSources'] = Turba::permissionsFilter($GLOBALS['cfgSources']);
$GLOBALS['attributes'] = $attributes;

// Build the directory sources select widget.
$default_source = Horde_Util::nonInputVar('source');
if (empty($default_source)) {
    $default_source = empty($_SESSION['turba']['source']) ? Turba::getDefaultAddressBook() : $_SESSION['turba']['source'];
    $default_source = Horde_Util::getFormData('source', $default_source);
}
$GLOBALS['browse_source_options'] = '';
$GLOBALS['browse_source_count'] = 0;
foreach (Turba::getAddressBooks() as $key => $curSource) {
    if (!empty($curSource['browse'])) {
        $selected = ($key == $default_source) ? ' selected="selected"' : '';
        $GLOBALS['browse_source_options'] .= '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' .
            htmlspecialchars($curSource['title']) . '</option>';

        $GLOBALS['browse_source_count']++;

        if (empty($default_source)) {
            $default_source = $key;
        }
    }
}
if (empty($cfgSources[$default_source]['browse'])) {
    $default_source = Turba::getDefaultAddressBook();
}
$_SESSION['turba']['source'] = $default_source;

// Only set $add_source_options if there is at least one editable address book
// that is not the current address book.
$addSources = Turba::getAddressBooks(PERMS_EDIT);
$copymove_source_options = '';
$copymoveSources = $addSources;
unset($copymoveSources[$default_source]);
foreach ($copymoveSources as $key => $curSource) {
    if ($key != $default_source) {
        $copymove_source_options .= '<option value="' . htmlspecialchars($key) . '">' .
            htmlspecialchars($curSource['title']) . '</option>';
    }
}
$GLOBALS['addSources'] = $addSources;

// Start compression, if requested.
if (!Horde_Util::nonInputVar('no_compress')) {
    Horde::compressOutput();
}
