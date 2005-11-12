<?php
/**
 * $Shout: shout/main/dialplan.php,v 1.0.0.1 2005/11/03 00:05:08 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if (!defined(SHOUT_BASE)) {
    define(SHOUT_BASE, dirname(__FILE__));
}

require_once SHOUT_BASE . '/lib/Dialplan.php';

$dialplan = &$shout->getDialplan($context);

// Set up the tree.
$dpgui = Shout_Dialplan::singleton('x', $dialplan);
require SHOUT_TEMPLATES . '/dialplan/manager.inc';

// Horde::addScriptFile('httpclient.js', 'horde', true);
// Horde::addScriptFile('hideable.js', 'horde', true);
// require HORDE_TEMPLATES . '/common-header.inc';
// require HORDE_TEMPLATES . '/portal/sidebar.inc';


// require SHOUT_TEMPLATES . "/dialplan/dialplanlist.inc";