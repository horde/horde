<?php
/**
 * Single message display for the dynamic view (dimp).
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'dimp'));

$page_output = $injector->getInstance('Horde_PageOutput');
$vars = Horde_Variables::getDefaultVariables();

if (!IMP::uid() || !IMP::mailbox()) {
    exit;
}

$imp_ui = new IMP_Ui_Message();
$js_onload = $js_vars = array();
$readonly = IMP::mailbox()->readonly;
$uid = IMP::uid();

switch ($vars->actionID) {
case 'strip_attachment':
    try {
        $indices = $injector->getInstance('IMP_Message')->stripPart(IMP::mailbox()->getIndicesOb($uid), $vars->id);
        $js_vars['-DimpMessage.strip'] = 1;
        list(,$uid) = $indices->getSingle();
        $notification->push(_("Attachment successfully stripped."), 'horde.success');
    } catch (IMP_Exception $e) {
        $notification->push($e);
    }
    break;
}

$show_msg = new IMP_Views_ShowMessage(IMP::mailbox(), $uid);
try {
    $show_msg_result = $show_msg->showMessage(array(
        'headers' => array_diff(array_keys($imp_ui->basicHeaders()), array('subject')),
        'preview' => false
    ));
} catch (IMP_Exception $e) {
    IMP::status();
    echo Horde::wrapInlineScript(array(
        'parent.close()'
    ));
    exit;
}

$ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
$ajax_queue->poll(IMP::mailbox());

foreach (array('from', 'to', 'cc', 'bcc', 'replyTo', 'log', 'uid', 'mbox', 'addr_limit') as $val) {
    if (!empty($show_msg_result[$val])) {
        $js_vars['DimpMessage.' . $val] = $show_msg_result[$val];
    }
}
$js_vars['DimpMessage.reply_list'] = $show_msg_result['list_info']['exists'];
$js_vars['DimpMessage.tasks'] = $injector->getInstance('Horde_Core_Factory_Ajax')->create('imp', $vars)->getTasks();

$js_out = $page_output->addInlineJsVars($js_vars, array('ret_vars' => true));

/* Determine if compose mode is disabled. */
$disable_compose = !IMP::canCompose();

if (!$disable_compose) {
    $compose_result = IMP_Views_Compose::showCompose(array(
        'qreply' => true
    ));

    /* Attach spellchecker & auto completer. */
    $imp_ui = new IMP_Ui_Compose();
    $acomplete = array('to', 'redirect_to');
    foreach (array('cc', 'bcc') as $val) {
        if ($prefs->getValue('compose_' . $val)) {
            $acomplete[] = $val;
        }
    }
    $imp_ui->attachAutoCompleter($acomplete);
    $imp_ui->attachSpellChecker();

    $js_out = array_merge($js_out, $compose_result['js']);
    $js_onload = $compose_result['jsonload'];
}

if (isset($show_msg_result['js'])) {
    $js_onload = array_merge($js_onload, $show_msg_result['js']);
}

$page_output->addInlineScript($js_out);
$page_output->addInlineScript(array_filter($js_onload), true);

$page_output->noDnsPrefetch();

$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);

$t->set('reply_button', IMP_Dimp::actionButton(array(
    'class' => 'hasmenu',
    'icon' => 'Reply',
    'id' => 'reply_link',
    'title' => _("Reply")
)));
$t->set('forward_button', IMP_Dimp::actionButton(array(
    'class' => 'hasmenu',
    'icon' => 'Forward',
    'id' => 'forward_link',
    'title' => _("Forward")
)));

if (!empty($conf['spam']['reporting']) &&
    (!$conf['spam']['spamfolder'] || !IMP::mailbox()->spam)) {
    $t->set('spam_button', IMP_Dimp::actionButton(array(
        'icon' => 'Spam',
        'id' => 'button_spam',
        'title' => _("Spam")
    )));
}

if (!empty($conf['notspam']['reporting']) &&
    (!$conf['notspam']['spamfolder'] || IMP::mailbox()->spam)) {
    $t->set('ham_button', IMP_Dimp::actionButton(array(
        'icon' => 'Ham',
        'id' => 'button_ham',
        'title' => _("Innocent")
    )));
}

if (IMP::mailbox()->access_deletemsgs) {
    $t->set('delete_button', IMP_Dimp::actionButton(array(
        'icon' => 'Delete',
        'id' => 'button_delete',
        'title' => _("Delete")
    )));
}

$t->set('view_source', !empty($conf['user']['allow_view_source']));
$t->set('save_as', $show_msg_result['save_as']);
$t->set('subject', $show_msg_result['subject']);

$hdrs = array();
foreach ($show_msg_result['headers'] as $val) {
    $hdrs[] = array_filter(array(
        'id' => (isset($val['id']) ? 'msgHeader' . $val['id'] : null),
        'label' => $val['name'],
        'val' => $val['value']
    ));
}
$t->set('hdrs', $hdrs);

$t->set('atc_list', '');
if (isset($show_msg_result['atc_label'])) {
    $t->set('atc_label', $show_msg_result['atc_label']);
    if (isset($show_msg_result['atc_list'])) {
        $t->set('atc_list', $show_msg_result['atc_list']);
    }
    $t->set('atc_download', isset($show_msg_result['atc_download']) ? $show_msg_result['atc_download'] : '');
} else {
    $t->set('atc_download', '');
    $t->set('atc_label', '');
}

$t->set('view_all_parts', empty($show_msg_result['onepart']));

$t->set('msgtext', $show_msg_result['msgtext']);

if (!$disable_compose) {
    $t->set('html', $compose_result['html']);
}

Horde::startBuffer();
IMP::status();
$t->set('status', Horde::endBuffer());

$injector->getInstance('IMP_Ajax')->header('message', $show_msg_result['title']);

Horde::startBuffer();
$page_output->includeScriptFiles();
$page_output->outputInlineScript();
$t->set('script', Horde::endBuffer());

Horde::startBuffer();
require IMP_TEMPLATES . '/dimp/common.inc';
$t->set('common', Horde::endBuffer());

echo $t->fetch(IMP_TEMPLATES . '/dimp/message/message.html');
