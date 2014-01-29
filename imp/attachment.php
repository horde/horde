<?php
/**
 * This file is the world-accessible endpoint for serving hosted (linked)
 * attachments.  It fetchs the file from the VFS and feeds it to the client
 * that wants to download the attachment. This allows for the exchange of
 * massive attachments without causing mail server havoc.
 *
 * URL Parameters:
 *   - d: (string) A token requesting deletion of the attachment
 *   - f: (string) [DEPRECATED] Filename
 *   - id: (string) Attachment ID
 *   - t: (string) [DEPRECATED] Timestamp
 *   - u: (string) Attachment owner
 *
 *
 * Copyright 2004-2007 Andrew Coleman <mercury@appisolutions.net>
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Andrew Coleman <mercury@appisolutions.net>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2004-2007 Andrew Coleman
 * @copyright 2008-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/* We do not need to be authenticated to get the file. */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'authentication' => 'none',
    'session_control' => 'none',
    'timezone' => true
));

$vars = $injector->getInstance('Horde_Variables');

/* This will throw exception if VFS/linked attachments are not available. */
$linked_atc = new IMP_Compose_LinkedAttachment($vars->u, $vars->id);

/* Check for old linked attachment data, and convert if necessary. */
if (isset($vars->t)) {
    $linked_atc->convert($vars->t, $vars->f);
}

/* Check for delete request. */
if ($vars->d) {
    if ($fname = $linked_atc->delete($vars->d)) {
        printf(_("Attachment %s deleted."), htmlspecialchars($fname));
    } else {
        print _("Attachment doesn't exist.");
    }
    exit;
}

/* Send view notification. */
$linked_atc->sendNotification();

/* This will throw exception if file is not available. */
$linked_atc->sendData();
