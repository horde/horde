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

if (empty($_SESSION['shout']['curaccount'])) {
    die("Permission denied.");
}

$curaccount = $_SESSION['shout']['curaccount'];
$menus = $shout->storage->getMenus($curaccount['code']);

if (empty($menus)) {
    Horde::applicationUrl('wizard.php', true)->redirect();
}
Horde::applicationUrl('dialplan.php', true)->redirect();
