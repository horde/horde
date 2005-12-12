<?php
/**
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SHOUT_BASE', dirname(__FILE__));
require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Shout.php';

// Form libraries.
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';

// Variable handling libraries
require_once 'Horde/Variables.php';
require_once 'Horde/Text/Filter.php';


$context = Util::getFormData("context");
$section = "users";
$action = Util::getFormData("action");
$extension = Util::getFormData("extension");

$contexts = &$shout->getContexts();
$vars = &Variables::getDefaultVariables();

if (!isset($context)) {#FIXME || !Shout::checkContext()) {
    $url = Horde::applicationUrl("index.php");
    header("Location: $url");
    exit(0);
}


switch ($action) {
    case "add":
        $title = _("Add User");
        # Treat adds just like an empty edit
        unset($extension);
        break;
    case "edit":
        $title = _("Edit User (Extension") . "$extension)";
        break;
    case "save":
        $title = _("Save User (Extension") . "$extension)";
        break;
    case "delete":
        $title = _("Delete User (Extension") . "$extension)";
        break;
    default:
        $url = Horde::applicationUrl('/');
        header("Location: $url");
        exit();
}

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

echo "<br />";

$tabs = &Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);
echo $tabs->render($section);

require SHOUT_BASE . "/users/$action.php";

require $registry->get('templates', 'horde') . '/common-footer.inc';