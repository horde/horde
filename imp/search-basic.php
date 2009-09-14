<?php
/**
 * IMP basic search script.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

if ($_SESSION['imp']['protocol'] == 'pop') {
    if ($_SESSION['imp']['view'] == 'imp') {
        $notification->push(_("Searching is not available with a POP3 server."), 'horde.error');
        $from_message_page = true;
        $actionID = $start = null;
        require_once IMP_BASE . '/mailbox.php';
    }
    exit;
}

$imp_ui_search = new IMP_UI_Search();

/* If search_basic_mbox is set, we are processing the search query. */
$search_mailbox = Horde_Util::getFormData('search_basic_mbox');
if ($search_mailbox) {
    $id = $imp_ui_search->processBasicSearch($search_mailbox, Horde_Util::getFormData('search_criteria'), Horde_Util::getFormData('search_criteria_text'), Horde_Util::getFormData('search_criteria_not'), Horde_Util::getFormData('search_flags'));

    /* Redirect to the mailbox screen. */
    header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('mailbox.php', true), 'mailbox', $GLOBALS['imp_search']->createSearchID($id), false));
    exit;
}

$f_fields = $s_fields = array();
$search_mailbox = Horde_Util::getFormData('search_mailbox');

foreach ($imp_ui_search->searchFields() as $key => $val) {
    if ($val['type'] != IMP_UI_Search::DATE) {
        $s_fields[] = array(
            'val' => $key,
            'label' => $val['label']
        );
    }
}

foreach ($imp_ui_search->flagFields() as $key => $val) {
    $f_fields[] = array(
        'val' => $key,
        'label' => $val
    );
}

/* Prepare the search template. */
$t = new Horde_Template();
$t->setOption('gettext', true);
$t->set('dimpview', $_SESSION['imp']['view'] == 'dimp');

$t->set('action', Horde::applicationUrl('search-basic.php'));
$t->set('mbox', htmlspecialchars($search_mailbox));
$t->set('search_title', sprintf(_("Search %s"), htmlspecialchars(IMP::displayFolder($search_mailbox))));
$t->set('s_fields', $s_fields);
$t->set('f_fields', $f_fields);

if ($t->get('dimpview')) {
    $t->set('hide_criteria', true);
} else {
    $title = _("Search");
    IMP::prepareMenu();
    require IMP_TEMPLATES . '/common-header.inc';
    IMP::menu();
    IMP::status();

    if ($browser->hasFeature('javascript')) {
        $t->set('advsearch', Horde::link(Horde_Util::addParameter(Horde::applicationUrl('search.php'), array('search_mailbox' => $search_mailbox))));
    }
}

echo $t->fetch(IMP_TEMPLATES . '/search/search-basic.html');

if (!$t->get('dimpview')) {
    require $registry->get('templates', 'horde') . '/common-footer.inc';
}
