<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'dimp'));

$vars = Horde_Variables::getDefaultVariables();

if (!$vars->uid || !$vars->folder) {
    exit;
}

$imp_ui = new IMP_Ui_Message();
$js_onload = $js_vars = array();
$readonly = $injector->getInstance('IMP_Injector_Factory_Imap')->create()->isReadOnly($vars->folder);

switch ($vars->actionID) {
case 'strip_attachment':
    try {
        $indices = $injector->getInstance('IMP_Message')->stripPart(new IMP_Indices($vars->folder, $vars->uid), $vars->id);
        $js_vars['-DimpFullmessage.strip'] = 1;
        list(,$vars->uid) = $indices->getSingle();
        $notification->push(_("Attachment successfully stripped."), 'horde.success');
    } catch (IMP_Exception $e) {
        $notification->push($e);
    }
    break;
}

$args = array(
    'headers' => array_diff(array_keys($imp_ui->basicHeaders()), array('subject')),
    'mailbox' => $vars->folder,
    'preview' => false,
    'uid' => $vars->uid
);

$show_msg = new IMP_Views_ShowMessage();
$show_msg_result = $show_msg->showMessage($args);
if (isset($show_msg_result['error'])) {
    IMP::status();
    echo Horde::wrapInlineScript(array(
        'parent.close()'
    ));
    exit;
}

$scripts = array(
    array('contextsensitive.js', 'horde'),
    array('textarearesize.js', 'horde'),
    array('fullmessage-dimp.js', 'imp'),
    array('imp.js', 'imp'),
);

foreach (array('from', 'to', 'cc', 'bcc', 'replyTo', 'log', 'uid', 'mailbox') as $val) {
    if (!empty($show_msg_result[$val])) {
        $js_vars['DimpFullmessage.' . $val] = $show_msg_result[$val];
    }
}
$js_out = Horde::addInlineJsVars($js_vars, true);

/* Determine if compose mode is disabled. */
$disable_compose = !IMP::canCompose();

if (!$disable_compose) {
    $compose_args = array(
        'folder' => $vars->folder,
        'messageCache' => '',
        'popup' => false,
        'qreply' => true,
        'uid' => $vars->uid,
    );
    $compose_result = IMP_Views_Compose::showCompose($compose_args);

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

    $scripts = array_merge($scripts, array(
        array('compose-base.js', 'imp'),
        array('compose-dimp.js', 'imp'),
        array('md5.js', 'horde'),
        array('popup.js', 'horde')
    ));

    if (!($prefs->isLocked('default_encrypt')) &&
        ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
        $scripts[] = array('dialog.js', 'imp');
        $scripts[] = array('redbox.js', 'horde');
    }

    $js_onload = $compose_result['jsonload'];
}

if (isset($show_msg_result['js'])) {
    $js_onload = array_merge($js_onload, $show_msg_result['js']);
}

Horde::addInlineScript($js_out);
Horde::addInlineScript(array_filter($js_onload), 'load');

Horde::noDnsPrefetch();

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
    (!$conf['spam']['spamfolder'] ||
     ($vars->folder != IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $t->set('spam_button', IMP_Dimp::actionButton(array(
        'icon' => 'Spam',
        'id' => 'button_spam',
        'title' => _("Spam")
    )));
}

if (!empty($conf['notspam']['reporting']) &&
    (!$conf['notspam']['spamfolder'] ||
    ($vars->folder == IMP::folderPref($prefs->getValue('spam_folder'), true)))) {
    $t->set('ham_button', IMP_Dimp::actionButton(array(
        'icon' => 'Ham',
        'id' => 'button_ham',
        'title' => _("Innocent")
    )));
}

if (!$readonly) {
    $t->set('delete_button', IMP_Dimp::actionButton(array(
        'icon' => 'Delete',
        'id' => 'button_deleted',
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

if (isset($show_msg_result['atc_label'])) {
    $t->set('atc_label', $show_msg_result['atc_label']);
    if (isset($show_msg_result['atc_list'])) {
        $t->set('atc_list', $show_msg_result['atc_list']);
    }
    $t->set('atc_download', isset($show_msg_result['atc_download']) ? $show_msg_result['atc_download'] : '');
}

$t->set('msgtext', $show_msg_result['msgtext']);

if (!$disable_compose) {
    $t->set('html', $compose_result['html']);
    $t->set('reply_list', $show_msg_result['list_info']['exists']);
    $t->set('forward_select', !$prefs->isLocked('forward_default'));
}

IMP::status();
IMP_Dimp::header($show_msg_result['title'], $scripts);

echo $t->fetch(IMP_TEMPLATES . '/dimp/message/message.html');

Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
