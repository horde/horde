<?php
/**
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

/* Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
 * definition for site customization). */
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/../..');
}

if (!defined('BABEL_BASE')) {
    @define('BABEL_BASE', dirname(__FILE__) . '/..');
}

/* Load the Horde Framework core, and set up inclusion paths. */
require_once HORDE_BASE . '/lib/core.php';

/* Notification system. */
$notification = &Notification::singleton();
$notification->attach('status');

/* Registry. */
$registry = &Registry::singleton();

if (is_a(($pushed = $registry->pushApp('babel', !defined('AUTH_HANDLER'))), 'PEAR_Error')) {
    if ($pushed->getCode() == 'permission_denied') {
        Horde::authenticationFailureRedirect(); 
    }
    Horde::fatal($pushed, __FILE__, __LINE__, false);
}

$conf = &$GLOBALS['conf'];
@define('BABEL_TEMPLATES', $registry->get('templates'));

/* Horde base libraries */
require_once 'Horde/Secret.php';

/* Babel base library */
require_once BABEL_BASE . '/lib/Babel.php';
require_once BABEL_BASE . '/lib/Translate.php';
require_once BABEL_BASE . '/lib/Translate_Help.php';
require_once BABEL_BASE . '/lib/Display.php';

/* Help */
require_once 'Horde/Help.php';

/* Menu */
require_once 'Horde/Menu.php';

/* Gettext (PO) */
require_once BABEL_BASE . '/lib/Gettext/PO.php';

/* Form and Variables */
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once 'Horde/Form/Action.php';

/* Tabs and Pager UI */
require_once 'Horde/UI/Tabs.php';
require_once 'Horde/UI/Pager.php';

/* Templates */
require_once 'Horde/Template.php';
$template = &new Horde_Template();

/* Module selection */
$app = Horde_Util::getFormData('module');
  
/* Language selection */
if (($lang = Horde_Util::getFormData('display_language')) !== null) {
    $_SESSION['babel']['language'] = $lang;
} elseif (isset($_SESSION['babel']['language'])) {
    $lang = $_SESSION['babel']['language'];
} else {
    
    $tests =  $nls['languages'];
    
    // Unset English
    unset($tests['en_US']);
    
    foreach($tests as $dir => $desc) {
	if (!Babel::hasPermission("language:$dir")) {
	    continue;
	} else {
	    $lang = $dir;
	    break;
	}
    }
    $_SESSION['babel']['language'] = $lang;
}
						  
/* Set up the template fields. */
$template->set('menu', Babel::getMenu('string'));
$template->set('notify', Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'status')));
$template->set('lang', Babel::displayLanguage());
$fmenu = Babel::LanguageSelection();

// Only display the Module Selection widget if an application has been set
if ($app) {
    $fmenu .= Babel::ModuleSelection();
}
$template->set('fmenu', $fmenu);

if ($lang && !Babel::hasPermission("language:$lang")) {
    Horde::fatal(sprintf(_("Access forbidden to '%s'."), $lang), __FILE__, __LINE__, true);
}

if ($app && !Babel::hasPermission("module:$app")) {
    Horde::fatal(sprintf(_("Access forbidden to '%s'."), $app), __FILE__, __LINE__, true);
}

/* Custom sort function */
function my_usort_function($a, $b)
{
    if ($a[1] > $b[1]) { return -1; }
    if ($a[1] < $b[1]) { return 1; }
    return 0;
}
