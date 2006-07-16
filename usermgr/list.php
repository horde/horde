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

$users = &$shout->getUsers($context);
