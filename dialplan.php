<?php
/**
 * $Horde: shout/dialplan.php,v 0.1 2005/07/17 01:56:48 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

@define('SHOUT_BASE', dirname(__FILE__));
$shout_configured = (@is_readable(SHOUT_BASE . '/config/conf.php'));# &&
    #@is_readable(SHOUT_BASE . '/config/prefs.php'));
if (!$shout_configured) {
    require SHOUT_BASE . '/../lib/Test.php';
    Horde_Test::configFilesMissing('Shout', SHOUT_BASE,
    array('conf.php', 'prefs.php'));
}

require_once SHOUT_BASE . '/lib/base.php';
require_once SHOUT_BASE . '/lib/Shout.php';

$context = Util::getFormData("context");
$section = "dialplan";
$action = Util::getFormData("action");
if ($button = Util::getFormData("submitbutton")) {
    $action = $button;
}
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
        $title = _("Add Extension");
        # Treat adds just like an empty edit
        unset($extension);
        $action = 'edit';
        break;
    case "Add Priority":
        $dialplan = &$shout->getDialplan($context);
        #FIXME Handle added-but-not-yet-saved priorities
        $dialplan['extensions'][$extension][] = '';
        $action = 'edit';
        break;
    case "Add 5 Priorities":
        $dialplan = &$shout->getDialplan($context);
        $dialplan['extensions'][$extension][] = '';
        $dialplan['extensions'][$extension][] = '';
        $dialplan['extensions'][$extension][] = '';
        $dialplan['extensions'][$extension][] = '';
        $dialplan['extensions'][$extension][] = '';
        $action = 'edit';
        break;
    case "edit":
        $title = _("Edit Extension") . "$extension";
        break;
    case "Save":
    case "save":
        $title = _("Save Extension") . "$extension";
        break;
    case "delete":
        $title = _("Delete Extension") . "$extension";
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

require SHOUT_BASE . "/dialplan/$action.php";

require $registry->get('templates', 'horde') . '/common-footer.inc';