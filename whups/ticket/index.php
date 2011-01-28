<?php
/**
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

require_once WHUPS_BASE . '/lib/Renderer/Comment.php';

$ticket = Whups::getCurrentTicket();
$vars = Horde_Variables::getDefaultVariables();
$ticket->setDetails($vars);
$linkTags[] = $ticket->feedLink();

$title = '[#' . $ticket->getId() . '] ' . $ticket->get('summary');
require $registry->get('templates', 'horde') . '/common-header.inc';
require WHUPS_TEMPLATES . '/menu.inc';
require WHUPS_TEMPLATES . '/prevnext.inc';

$tabs = Whups::getTicketTabs($vars, $ticket->getId());
echo $tabs->render('history');

$form = new TicketDetailsForm($vars, $ticket);

$renderer = $form->getRenderer();
$renderer->_name = $form->getName();
$renderer->beginInactive($title);
$renderer->renderFormInactive($form, $vars);
$renderer->end();

echo '<br class="spacer" />';

$comment = new Horde_Form_Renderer_Comment();
$comment->begin(_("History"));
$history = Whups::permissionsFilter($whups_driver->getHistory($ticket->getId()),
                                    'comment', Horde_Perms::READ);
$chtml = array();
foreach ($history as $transaction => $comment_values) {
    $chtml[] = $comment->render($transaction, new Horde_Variables($comment_values));
}
if ($prefs->getValue('comment_sort_dir')) {
    $chtml = array_reverse($chtml);
}
echo implode('', $chtml);
$comment->end();

require $registry->get('templates', 'horde') . '/common-footer.inc';
