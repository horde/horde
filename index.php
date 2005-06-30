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

#$title = '[#' . $ticket->getId() . '] ' . $ticket->get('summary');
require SHOUT_TEMPLATES . '/common-header.inc';
require SHOUT_TEMPLATES . '/menu.inc';

$tabs = &Shout::getTabs($vars);
$tabs->preserve('context', $context);
echo $tabs->render();

switch ($section) {
    case "contexts":
        require "contexts.php";
        break;

    case "users":
        require "users.php";
        break;

    case "moh":
        require "moh.php";
        break;

    case "global":
        require "global.php";
        break;

    default:
        break;
}
// $form = &new TicketDetailsForm($vars);
// $form->addAttributes($whups->getAllTicketAttributesWithNames(
//    $ticket->getId()));
//
// $RENDERER = &new Horde_Form_Renderer();
// $RENDERER->beginInactive($title);
// $RENDERER->renderFormInactive($form, $vars);
// $RENDERER->end();
//
// echo '<br />';
//
// $COMMENT = &new Comment();
// $COMMENT->begin(_("History"));
// $history = Whups::permissionsFilter($whups->getHistory($ticket->getId()),
//                                     'comment', PERMS_READ);
// $chtml = array();
// foreach ($history as $comment_values) {
//     $chtml[] = $COMMENT->render(new Variables($comment_values));
// }
// if ($prefs->getValue('comment_sort_dir')) {
//     $chtml = array_reverse($chtml);
// }
// echo implode('', $chtml);
// $COMMENT->end();

require $registry->get('templates', 'horde') . '/common-footer.inc';









#require SHOUT_BASE . '/contexts.php';