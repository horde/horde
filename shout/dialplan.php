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

if (!isset($SHOUT_RUNNING) || !$SHOUT_RUNNING) {
    header('Location: /');
    exit();
}

require_once SHOUT_BASE . '/lib/Dialplan.php';
$dialplan = &$shout->getDialplan($context);

// Set up the tree.
$dpgui = Shout_Dialplan::singleton('x', $dialplan);

//$action = Horde_Util::getFormData("action");
// $action = 'manager';

$title = _("Dialplan Manager");

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo $tabs->render($section);

// require SHOUT_BASE . "/dialplan/$action.php";

require SHOUT_TEMPLATES . '/dialplan/manager.inc';

// Horde::addScriptFile('httpclient.js', 'horde', true);
// Horde::addScriptFile('hideable.js', 'horde', true);
// require HORDE_TEMPLATES . '/common-header.inc';
// require HORDE_TEMPLATES . '/portal/sidebar.inc';


// require SHOUT_TEMPLATES . "/dialplan/dialplanlist.inc";










require $registry->get('templates', 'horde') . '/common-footer.inc';