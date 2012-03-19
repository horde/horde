<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

$folks_authentication = 'none';
require_once __DIR__ . '/../lib/base.php';

$users = $folks_driver->getOnlineUsers();
if ($users instanceof PEAR_Error) {
    $users = array();
} else {
    $users = array_flip($users);
}

$title = _("Online users");
$link = Folks::getUrlFor('list', 'online', true);
$rss_link = Horde::url('rss/online.php', true);

require FOLKS_TEMPLATES . '/feed/feed.php';
