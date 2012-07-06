<?php
/**
 * IMP basic search script.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_BASIC
));

if (!$injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_SEARCH)) {
    $notification->push(_("Searching is not available."), 'horde.error');
    $from_message_page = true;
    $actionID = $start = null;
    require_once IMP_BASE . '/mailbox.php';
    exit;
}

$imp_search = $injector->getInstance('IMP_Search');
$vars = $injector->getInstance('Horde_Variables');

/* If search_basic is set, we are processing the search query. */
if ($vars->search_basic) {
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

    if (empty($c_list)) {
        $notification->push(_("No search criteria specified."), 'horde.error');
    } else {
        /* Store the search in the session. */
        $q_ob = $imp_search->createQuery($c_list, array(
            'id' => IMP_Search::BASIC_SEARCH,
            'mboxes' => array(IMP::mailbox()),
            'type' => IMP_Search::CREATE_QUERY
        ));

        /* Redirect to the mailbox screen. */
        IMP_Mailbox::get($q_ob)->url('mailbox.php')->redirect();
    }
}

$flist = $injector->getInstance('IMP_Flags')->getList(array(
    'imap' => true,
    'mailbox' => IMP::mailbox()
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
$t->set('advsearch', Horde::link(IMP::mailbox()->url('search.php')));
$t->set('mbox', IMP::mailbox()->form_to);
$t->set('search_title', sprintf(_("Search %s"), IMP::mailbox()->display_html));
$t->set('flist', $flag_set);

$menu = IMP::menu();
IMP::header(_("Search"));
echo $menu;
IMP::status();

echo $t->fetch(IMP_TEMPLATES . '/imp/search/search-basic.html');
$page_output->footer();
