<?php
/**
 * $Horde: shout/index.php,v 0.1 2005/06/28 10:35:46 ben Exp $
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
#require_once SHOUT_TEMPLATES . '/comment.inc';
require_once 'Horde/Variables.php';
require_once 'Horde/Text/Filter.php';

$context = Util::getFormData("context");
$section = Util::getFormData("section");

$contexts = $shout->getContexts();
$vars = &Variables::getDefaultVariables();
#$ticket->setDetails($vars);

if (count($contexts) == 1) {
    $context = $contexts[0];
} elseif (!$context) {
    $context = $shout->getHomeContext();
}

#$title = '[#' . $ticket->getId() . '] ' . $ticket->get('summary');
require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$tabs = &Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);

echo "<br />";
// if (!$section) {
//     $section = 
if (!$section) {
    $section = $tabs->_tabs[0]['tabname'];
}

echo $tabs->render($section);
switch ($section) {
    case "conference":
    case "dialplan":
    case "security":
    case "users":
    case "moh":
        require "$section.php";
        break;

    default:
        break;
}


require $registry->get('templates', 'horde') . '/common-footer.inc';









#require SHOUT_BASE . '/contexts.php';