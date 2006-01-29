<?php
/**
 * $Id$
 *
 * Copyright 2005-2006 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if (!isset($SHOUT_RUNNING) || !$SHOUT_RUNNING) {
    header('Location: /');
    exit();
}

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

$action = Util::getFormData("action");
$extension = Util::getFormData("extension");

$title = _("User Manager: ");
switch ($action) {
    case "add":
        $title .= _("Add User");

        # Treat adds just like an empty edit
        $action = "edit";
        $extension = 0;
        break;
    case "edit":
        $title .= _("Edit User (Extension ") . "$extension)";
        break;
    case "save":
        $title .= _("Save User (Extension ") . "$extension)";
        break;
    case "delete":
        $title .= _("Delete User (Extension ") . "$extension)";
        break;
    case "list":
    default:
        $title .= _("List Users");
        $action = "list";
        break;
}



require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo $tabs->render($section);

require SHOUT_BASE . "/usermgr/$action.php";

require $registry->get('templates', 'horde') . '/common-footer.inc';