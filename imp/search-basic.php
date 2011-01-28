<?php
/**
 * IMP basic search script.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
Horde_Registry::appInit('imp', array(
    'impmode' => 'imp'
));

/* This is an IMP-only script. */
if ($session->get('imp', 'view') != 'imp') {
    exit;
}

if ($session->get('imp', 'protocol') == 'pop') {
    $notification->push(_("Searching is not available with a POP3 server."), 'horde.error');
    $from_message_page = true;
    $actionID = $start = null;
    require_once IMP_BASE . '/mailbox.php';
    exit;
}

$imp_search = $injector->getInstance('IMP_Search');
$vars = Horde_Variables::getDefaultVariables();

/* If search_basic_mbox is set, we are processing the search query. */
if ($vars->search_basic_mbox) {
    $c_list = array();

    if ($vars->search_criteria_text) {
        switch ($vars->search_criteria) {
        case 'from':
        case 'subject':
            $c_list[] = new IMP_Search_Element_Header(
                $vars->search_criteria_text,
                $vars->search_criteria,
                $vars->search_criteria_not
            );
            break;

        case 'recip':
            $c_list[] = new IMP_Search_Element_Recipient(
                $vars->search_criteria_text,
                $vars->search_criteria_not
            );
            break;

        case 'body':
        case 'text':
            $c_list[] = new IMP_Search_Element_Text(
                $vars->search_criteria_text,
                ($vars->search_criteria == 'body'),
                $vars->search_criteria_not
            );
        break;
        }
    }

    if ($vars->search_criteria_flag) {
        $formdata = $injector->getInstance('IMP_Flags')->parseFormId($vars->search_criteria_flag);
        $c_list[] = new IMP_Search_Element_Flag(
            $formdata['flag'],
            ($formdata['set'] && !$vars->search_criteria_flag_not)
        );
    }

    /* Store the search in the session. */
    $q_ob = $imp_search->createQuery($c_list, array(
        'id' => IMP_Search::BASIC_SEARCH,
        'mboxes' => array($vars->search_basic_mbox),
        'type' => IMP_Search::CREATE_QUERY
    ));

    /* Redirect to the mailbox screen. */
    Horde::url('mailbox.php', true)->add('mailbox', strval($q_ob))->redirect();
}

$flist = $injector->getInstance('IMP_Flags')->getList(array(
    'imap' => true,
    'mailbox' => $vars->search_mailbox
));
$flag_set = array();
foreach ($flist as $val) {
    $flag_set[] = array(
        'val' => $val->form_set,
        'label' => $val->label
    );
}

/* Prepare the search template. */
$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

$t->set('action', Horde::url('search-basic.php'));
$t->set('mbox', htmlspecialchars($vars->search_mailbox));
$t->set('search_title', sprintf(_("Search %s"), htmlspecialchars(IMP::displayFolder($vars->search_mailbox))));
$t->set('flist', $flag_set);

$title = _("Search");
$menu = IMP::menu();
require IMP_TEMPLATES . '/common-header.inc';
echo $menu;
IMP::status();

if ($browser->hasFeature('javascript')) {
    $t->set('advsearch', Horde::link(Horde::url('search.php')->add(array('search_mailbox' => $vars->search_mailbox))));
}

echo $t->fetch(IMP_TEMPLATES . '/imp/search/search-basic.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
