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
$shout_configured = (is_readable(SHOUT_BASE . '/config/conf.php'));

if (!$shout_configured) {
require SHOUT_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Shout', SHOUT_BASE,
        array('conf.php'));
}

require_once SHOUT_BASE . '/lib/base.php';
header('Location: ' . Horde::applicationUrl('extensions.php'));
