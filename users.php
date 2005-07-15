<?php
/**
 * $Horde: shout/users/index.php,v 0.1 2005/07/13 10:01:01 ben Exp $
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
$section = "users";
$action = Util::getFormData("action");

$contexts = $shout->getContexts();
$vars = &Variables::getDefaultVariables();

if (!isset($context)) {#FIXME || !Shout::checkContext()) {
    $url = Horde::applicationUrl("index.php");
    header("Location: $url");
    exit(0);
}

require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

echo "<br />";

$tabs = &Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);
echo $tabs->render($section);

switch ($action) {
    case "add":
    case "edit":
    case "save":
    case "delete":
        require SHOUT_BASE . "/users/$action.php";
        break;
}