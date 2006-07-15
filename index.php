<?php
/**
 * $Id$
 *
 * Copyright 2005-2006 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package shout
 */

@define('SHOUT_BASE', dirname(__FILE__));
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Shout.php';

// Variable handling libraries
require_once 'Horde/Variables.php';
require_once 'Horde/Text/Filter.php';

$context = Util::getFormData("context");
$section = Util::getFormData("section");

$contexts = &$shout->getContexts();
# Check that we are properly initialized
if (is_a($contexts, 'PEAR_Error')) {
    $notification->push($contexts, 'horde.error');
    $contexts = false;
} elseif (count($contexts) == 1) {
    # Default to the user's only context
    $context = $contexts[0];
} elseif (!$context) {
    # Attempt to locate the user's "home" context
    $context = $shout->getHomeContext();
    if (is_a($context, 'PEAR_Error')) {
        $notification->push($context);
    }
    $context = '';
}

$vars = &Variables::getDefaultVariables();
$tabs = &Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);

switch ($section) {
    case "conference":
    case "dialplan":
    case "security":
    case "usermgr":
    case "moh":
        break;
    default:
        $section = $tabs->_tabs[0]['tabname'];
        break;
}
# We've passed the initialization tests.  This flag allows other pages to run.
$SHOUT_RUNNING = true;

require SHOUT_BASE . "/$section.php";

#print '<div style="width:95%;left:10px;position:relative">';

#print '</div>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
