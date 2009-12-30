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

$title = _('Security');

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

$tabs->render($section);

Shout::getApplist();

require $registry->get('templates', 'horde') . '/common-footer.inc';