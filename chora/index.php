<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

define('CHORA_BASE', dirname(__FILE__));
$chora_configured = (is_readable(CHORA_BASE . '/config/conf.php') &&
                     is_readable(CHORA_BASE . '/config/sourceroots.php') &&
                     is_readable(CHORA_BASE . '/config/mime_drivers.php') &&
                     is_readable(CHORA_BASE . '/config/prefs.php'));

if (!$chora_configured) {
    /* Chora isn't configured. */
    require CHORA_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Chora', CHORA_BASE,
        array('conf.php', 'prefs.php', 'mime_drivers.php'),
        array('sourceroots.php' => 'This file defines all of the source repositories that you wish Chora to display.'));
}

require CHORA_BASE . '/browse.php';
