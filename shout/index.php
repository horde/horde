<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */
require_once dirname(__FILE__) . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

if (!($curaccount = $GLOBALS['session']->get('shout', 'curaccount_code'))) {
    die("Permission denied.");
}

$menus = $shout->storage->getMenus($curaccount);

if (empty($menus)) {
    Horde::url('wizard.php', true)->redirect();
}
Horde::url('dialplan.php', true)->redirect();
