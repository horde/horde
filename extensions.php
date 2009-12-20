<?php
/**
 * $Id$
 *
 * Copyright 2005-2006 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
@define('SHOUT_BASE', dirname(__FILE__));
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Shout.php';

if (!isset($SHOUT_RUNNING) || !$SHOUT_RUNNING) {
    # FIXME! This needs to redirect somewhere more sensical
    header('Location: /');
    exit();
}

$action = Horde_Util::getFormData('action');
$extension = Horde_Util::getFormData('extension');

$vars = Horde_Variables::getDefaultVariables();

$tabs = Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);

$section = 'usermgr';
$title = _("User Manager: ");

switch ($action) {
    case 'add':
        $title .= _("Add User");

        # Treat adds just like an empty edit
        $action = 'edit';
        $extension = 0;
        break;
    case 'edit':
        $title .= sprintf(_("Edit User (Extension %s)"), $extension);
        break;
    case 'save':
        $title .= sprintf(_("Save User (Extension %s)"), $extension);
        break;
    case 'delete':
        $title .= sprintf(_("Delete User (Extension %s)"), $extension);
        break;
    case 'list':
    default:
        $title .= _("List Users");
        $action = 'list';
        break;
}

require SHOUT_BASE . '/usermgr/' . $action . '.php';

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$notification->notify();

echo $tabs->render($section);

require SHOUT_TEMPLATES . '/usermgr/' . $action . '.inc';

require $registry->get('templates', 'horde') . '/common-footer.inc';
