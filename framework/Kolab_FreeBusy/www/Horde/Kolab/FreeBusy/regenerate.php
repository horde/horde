<?php
/**
 * A script for regenerating the Kolab Free/Busy cache.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * @author  Gunnar Wrobel <p@rdus.de>
 * @author  Thomas Arendsen Hein <thomas@intevation.de>
 * @package Kolab_FreeBusy
 */

/** Report all errors */
error_reporting(E_ALL);

/** requires safe_mode to be turned off */
ini_set('memory_limit', -1);

/**
 * Load the required free/busy libraries - this also loads Horde:: and
 * Horde_Util:: as well as the PEAR constants
 */
require_once 'Horde/Kolab/FreeBusy.php';
require_once 'Horde/Kolab/FreeBusy/Report.php';

/** Load the configuration */
require_once 'config.php';

$conf['kolab']['misc']['allow_special'] = true;

if (empty($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = $conf['kolab']['imap']['server'];
}

$fb_reporter = new Horde_Kolab_FreeBusy_Report();

$fb_reporter->start();

$fb = new Horde_Kolab_FreeBusy();
$result = $fb->regenerate($fb_reporter);
if (is_a($result, 'PEAR_Error')) {
    echo $result->getMessage();
    exit(1);
}
$result = $fb_reporter->stop();
if ($result === false) {
    exit(1);
}
exit(0);
