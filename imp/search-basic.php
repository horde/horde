<?php
/**
 * IMP basic search script.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp');

if ($_SESSION['imp']['protocol'] == 'pop') {
    if ($_SESSION['imp']['view'] == 'imp') {
        $notification->push(_("Searching is not available with a POP3 server."), 'horde.error');
        $from_message_page = true;
        $actionID = $start = null;
        require_once IMP_BASE . '/mailbox.php';
    }
    exit;
}

$imp_search = $injector->getInstance('IMP_Search');

/* If search_basic_mbox is set, we are processing the search query. */
$search_mailbox = Horde_Util::getFormData('search_basic_mbox');
if ($search_mailbox) {
    $imp_ui_search = new IMP_Ui_Search();
    $id = $imp_ui_search->processBasicSearch($search_mailbox, Horde_Util::getFormData('search_criteria'), Horde_Util::getFormData('search_criteria_text'), Horde_Util::getFormData('search_criteria_not'), Horde_Util::getFormData('search_flags'));

    /* Redirect to the mailbox screen. */
    header('Location: ' . Horde::applicationUrl('mailbox.php', true)->setRaw(true)->add('mailbox', $imp_search->createSearchID($id)));
    exit;
}

$f_fields = $s_fields = array();
$search_mailbox = Horde_Util::getFormData('search_mailbox');

foreach ($imp_search->searchFields() as $key => $val) {
    if (!in_array($val['type'], array('customhdr', 'date', 'within'))) {
        $s_fields[] = array(
            'val' => $key,
            'label' => $val['label']
        );
    }
}

foreach ($imp_search->flagFields() as $key => $val) {
    $f_fields[] = array(
        'val' => $key,
        'label' => $val
    );
}

/* Prepare the search template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

$t->set('action', Horde::applicationUrl('search-basic.php'));
$t->set('mbox', htmlspecialchars($search_mailbox));
$t->set('search_title', sprintf(_("Search %s"), htmlspecialchars(IMP::displayFolder($search_mailbox))));
$t->set('s_fields', $s_fields);
$t->set('f_fields', $f_fields);

$title = _("Search");
IMP::prepareMenu();
require IMP_TEMPLATES . '/common-header.inc';
IMP::menu();
IMP::status();

if ($browser->hasFeature('javascript')) {
    $t->set('advsearch', Horde::link(Horde::applicationUrl('search.php')->add(array('search_mailbox' => $search_mailbox))));
}

echo $t->fetch(IMP_TEMPLATES . '/imp/search/search-basic.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
