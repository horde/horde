#!@php_bin@
<?php
/**
 *  A filter for incoming mail on a Kolab Server. It checks the
 *  messages for iCal data and handles automatic invitations.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @package Kolab
 */

/** Kolab filter library */
require_once 'Horde/Kolab/Filter/Incoming.php';
require_once 'Horde/Kolab/Filter/Response.php';

/* Parse the mail */
$parser = new Horde_Kolab_Filter_Incoming();
$response = new Horde_Kolab_Filter_Response();

$result = $parser->parse();
if (is_a($result, 'PEAR_Error')) {
    $response->handle($result);
}
exit(0);
