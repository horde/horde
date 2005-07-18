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

$context = Util::getFormData("context");
$section = Util::getFormData("section");

$contexts = &$shout->getContexts();
$vars = &Variables::getDefaultVariables();
#$ticket->setDetails($vars);

if (count($contexts) == 1) {
    $context = $contexts[0];
} elseif (!$context) {
    $context = $shout->getHomeContext();
}

$tabs = &Shout::getTabs($context, $vars);
$tabs->preserve('context', $context);
if (!$section) {
    $section = $tabs->_tabs[0]['tabname'];
}

#require_once SHOUT_TEMPLATES . '/comment.inc';
#require_once 'Horde/Variables.php';
#require_once 'Horde/Text/Filter.php';



#$title = '[#' . $ticket->getId() . '] ' . $ticket->get('summary');
require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';


echo "<br />";
// if (!$section) {
//     $section =

echo $tabs->render($section);
switch ($section) {
    case "conference":
        $title = _('Conferences');
        break;
    case "dialplan":
        $title = _('Dial Plan');
        break;
    case "security":
        $title = _('Security/Access Control');
        break;
    case "system":
        $title = _('System Settings');
        break;
    case "users":
        $title = _('Users');
        break;
    case "moh":
        $title = _('Music on Hold');
        break;

    default:
        require SHOUT_TEMPLATES . '/common-footer.inc';
        exit();
        break;
}

require "main/$section.php";
require $registry->get('templates', 'horde') . '/common-footer.inc';