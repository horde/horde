<?php
/**
 * $Id$
 *
 * Copyright 2005-2006 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package shout
 */
@define('SHOUT_BASE', dirname(__FILE__) . '/..');
//require_once 'Horde/Variables.php';

$context = Horde_Util::getFormData('context');
$extension = Horde_Util::getFormData('extension');

$res = $shout->deleteUser($context, $extension);

if (!$res) {
    echo "Failed!";
    print_r($res);
}
$notification->push("User Deleted.");
$notification->notify();
require SHOUT_TEMPLATES . '/common-footer.inc';