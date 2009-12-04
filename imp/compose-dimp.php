<?php
/**
 * Dynamic (dimp) compose display page.
 *
 * List of URL parameters:
 *   'popup' - Explicitly mark window as popup. Needed if compose page is
 *             opened from a page other than the base DIMP page.
 *   TODO
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

function _removeAutoSaveDraft($uid)
{
    if (!empty($uid)) {
        $imp_message = IMP_Message::singleton();
        $imp_message->delete(array($uid . IMP::IDX_SEP . IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true)), array('nuke' => true));
    }
}

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true, 'tz' => true));

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* The headers of the message. */
$header = array();
foreach (array('to', 'cc', 'bcc', 'subject') as $v) {
    $header[$v] = rawurldecode(Horde_Util::getFormData($v, ''));
}

$action = Horde_Util::getFormData('action');
$get_sig = true;
$msg = '';

$identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
if (!$prefs->isLocked('default_identity')) {
    $identity_id = Horde_Util::getFormData('identity');
    if (!is_null($identity_id)) {
        $identity->setDefault($identity_id);
    }
}

/* Initialize the IMP_Compose:: object. */
$imp_compose = IMP_Compose::singleton(Horde_Util::getFormData('composeCache'));

/* Init IMP_Ui_Compose:: object. */
$imp_ui = new IMP_Ui_Compose();

if (count($_POST)) {
    $result = new stdClass;
    $result->action = $action;
    $result->success = 0;

    /* Update the file attachment information. */
    if ($action == 'add_attachment') {
        if ($_SESSION['imp']['file_upload'] &&
            $imp_compose->addFilesFromUpload('file_')) {
            $info = IMP_Dimp::getAttachmentInfo($imp_compose);
            $result->success = 1;
            $result->info = end($info);
            $result->imp_compose = $imp_compose->getCacheId();
        }
        Horde::sendHTTPResponse(Horde::prepareResponse($result, $GLOBALS['imp_notify']), 'js-json');
        exit;
    }

    /* Set the default charset. */
    $charset = Horde_Nls::getEmailCharset();
    if (!$prefs->isLocked('sending_charset')) {
        $charset = Horde_Util::getFormData('charset', $charset);
    }

    switch ($action) {
    case 'auto_save_draft':
    case 'save_draft':
        /* Set up the From address based on the identity. */
        try {
            $from = $identity->getFromLine(null, Horde_Util::getFormData('from'));
        } catch (Horde_Exception $e) {
            $notification->push($e);
            break;
        }
        $header['from'] = $from;

        /* Save the draft. */
        try {
            $old_uid = $imp_compose->getMetadata('draft_uid');

            $res = $imp_compose->saveDraft($header, Horde_Util::getFormData('message', ''), Horde_Nls::getCharset(), Horde_Util::getFormData('html'));
            $result->success = 1;

            /* Delete existing draft. */
            _removeAutoSaveDraft($old_uid);

            if ($action == 'auto_save_draft') {
                $notification->push(_("Draft automatically saved."), 'horde.message');
            } else {
                $notification->push($res);
            }
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
        }
        break;

    case 'send_message':
        if ($compose_disable) {
            break;
        }

        try {
            $from = $identity->getFromLine(null, Horde_Util::getFormData('from'));
        } catch (Horde_Exception $e) {
            $notification->push($e);
            break;
        }
        $header['from'] = $from;
        $header['replyto'] = $identity->getValue('replyto_addr');

        $header['to'] = $imp_ui->getAddressList(Horde_Util::getFormData('to'), Horde_Util::getFormData('to_list'), Horde_Util::getFormData('to_field'), Horde_Util::getFormData('to_new'));
        if ($prefs->getValue('compose_cc')) {
            $header['cc'] = $imp_ui->getAddressList(Horde_Util::getFormData('cc'), Horde_Util::getFormData('cc_list'), Horde_Util::getFormData('cc_field'), Horde_Util::getFormData('cc_new'));
        }
        if ($prefs->getValue('compose_bcc')) {
            $header['bcc'] = $imp_ui->getAddressList(Horde_Util::getFormData('bcc'), Horde_Util::getFormData('bcc_list'), Horde_Util::getFormData('bcc_field'), Horde_Util::getFormData('bcc_new'));
        }

        $message = Horde_Util::getFormData('message');
        $html = Horde_Util::getFormData('html');

        $result->uid = $imp_compose->getMetadata('uid');

        if ($reply_type = $imp_compose->getMetadata('reply_type')) {
            $result->reply_folder = $imp_compose->getMetadata('mailbox');
            $result->reply_type = $reply_type;
        }

        /* Use IMP_Tree to determine whether the sent mail folder was
         * created. */
        $imptree = IMP_Imap_Tree::singleton();
        $imptree->eltDiffStart();

        $options = array(
            'priority' => Horde_Util::getFormData('priority'),
            'readreceipt' => Horde_Util::getFormData('request_read_receipt'),
            'save_attachments' => Horde_Util::getFormData('save_attachments_select'),
            'save_sent' => (($prefs->isLocked('save_sent_mail'))
                            ? $identity->getValue('save_sent_mail')
                            : (bool)Horde_Util::getFormData('save_sent_mail')),
            'sent_folder' => (($prefs->isLocked('save_sent_mail'))
                              ? $identity->getValue('sent_mail_folder')
                              : Horde_Util::getFormData('save_sent_mail_folder', $identity->getValue('sent_mail_folder')))
        );

        try {
            $sent = $imp_compose->buildAndSendMessage($message, $header, $charset, $html, $options);
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e->getMessage(), 'horde.error');
            break;
        }
        $result->success = 1;

        /* Remove any auto-saved drafts. */
        if ($prefs->getValue('auto_save_drafts') ||
            $prefs->getValue('auto_delete_drafts')) {
            _removeAutoSaveDraft($imp_compose->getMetadata('draft_uid'));
            $result->draft_delete = 1;
        }

        if ($sent && $prefs->getValue('compose_confirm')) {
            $notification->push(empty($header['subject']) ? _("Message sent successfully.") : sprintf(_("Message \"%s\" sent successfully."), Horde_String::truncate($header['subject'])), 'horde.success');
        }

        /* Update maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            $in_reply_to = $imp_compose->getMetadata('in_reply_to');
            if (!empty($in_reply_to) &&
                ($tmp = IMP_Dimp::getMsgLogInfo($in_reply_to))) {
                $result->log = $tmp;
            }
        }

        $res = IMP_Dimp::getFolderResponse($imptree);
        if (!empty($res)) {
            $result->folder = $res['a'][0];
        }
    }

    Horde::sendHTTPResponse(Horde::prepareResponse($result, $GLOBALS['imp_notify']), 'json');
    exit;
}

/* Attach spellchecker & auto completer. */
$imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
$imp_ui->attachSpellChecker('dimp');

$type = Horde_Util::getFormData('type');
$uid = Horde_Util::getFormData('uid');
$folder = Horde_Util::getFormData('folder');
$show_editor = false;
$title = _("New Message");

if (in_array($type, array('reply', 'reply_all', 'reply_auto', 'reply_list', 'forward', 'resume'))) {
    if (!$uid || !$folder) {
        $type = 'new';
    }

    try {
        $imp_contents = IMP_Contents::singleton($uid . IMP::IDX_SEP . $folder);
    } catch (Horde_Exception $e) {
        $notification->push(_("Requested message not found."), 'horde.error');
        $uid = $folder = null;
        $type = 'new';
    }
}

switch ($type) {
case 'reply':
case 'reply_all':
case 'reply_auto':
case 'reply_list':
    $reply_msg = $imp_compose->replyMessage($type, $imp_contents, Horde_Util::getFormData('to'));
    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    $header['replytype'] = 'reply';
    $type = $reply_msg['type'];

    if ($type == 'reply') {
        $title = _("Reply:");
    } elseif ($type == 'reply_all') {
        $title = _("Reply to All:");
    } elseif ($type == 'reply_list') {
        $title = _("Reply to List:");
    }
    $title .= ' ' . $header['subject'];

    if ($reply_msg['format'] == 'html') {
        $show_editor = true;
    }

    if (!$prefs->isLocked('default_identity') && !is_null($reply_msg['identity'])) {
        $identity->setDefault($reply_msg['identity']);
    }
    break;

case 'forward':
    $fwd_msg = $imp_ui->getForwardData($imp_compose, $imp_contents, $uid . IMP::IDX_SEP . $folder);
    $msg = $fwd_msg['body'];
    $header = $fwd_msg['headers'];
    $header['replytype'] = 'forward';
    $title = $header['title'];
    if ($fwd_msg['format'] == 'html') {
        $show_editor = true;
    }
    $type = 'forward';

    if (!$prefs->isLocked('default_identity') &&
        !is_null($fwd_msg['identity'])) {
        $identity->setDefault($fwd_msg['identity']);
    }
    break;

case 'resume':
    try {
        $result = $imp_compose->resumeDraft($uid . IMP::IDX_SEP . $folder);

        if ($result['mode'] == 'html') {
            $show_editor = true;
        }
        $msg = $result['msg'];
        if (!is_null($result['identity']) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
        }
        $header = array_merge($header, $result['header']);
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e->getMessage(), 'horde.error');
    }
    $get_sig = false;
    break;

case 'new':
    $rte = $show_editor = ($prefs->getValue('compose_html') && $_SESSION['imp']['rteavail']);
    break;
}

$sig = $identity->getSignature();
if ($get_sig && !empty($sig)) {
    if ($show_editor) {
        $sig = '<p><!--begin_signature-->' . $imp_compose->text2html(trim($sig)) . '<!--end_signature--></p>';
    }

    $msg = ($identity->getValue('sig_first'))
        ? "\n" . $sig . $msg
        : $msg . "\n" . $sig;
}

$t = new Horde_Template(IMP_TEMPLATES . '/imp/');
$t->setOption('gettext', true);
$t->set('title', $title);

$compose_result = IMP_Views_Compose::showCompose(array(
    'composeCache' => $imp_compose->getCacheId(),
    'folder' => $folder,
    'qreply' => false,
    'uid' => $uid
));

$t->set('compose_html', $compose_result['html']);

/* Javscript variables to be set immediately. */
if ($show_editor) {
    $compose_result['js'][] = 'DIMP.conf_compose.show_editor = 1';
}
if (Horde_Util::getFormData('popup')) {
    $compose_result['js'][] = 'DIMP.conf_compose.popup = 1';
}
Horde::addInlineScript($compose_result['js']);

/* Some actions, like adding forwards, may return error messages so explicitly
 * display those messages now. */
Horde::addInlineScript(array(IMP_Dimp::notify()), 'dom');

/* Javascript to be run on window load. */
$compose_result['js_onload'][] = 'DimpCompose.fillForm(' . Horde_Serialize::serialize($msg, Horde_Serialize::JSON) . ', ' . Horde_Serialize::serialize($header, Horde_Serialize::JSON) . ', "' . (($type == 'new' || $type == 'forward') ? 'to' : 'composeMessage') . '", true)';
Horde::addInlineScript($compose_result['js_onload'], 'load');

$scripts = array(
    array('compose-dimp.js', 'imp')
);

IMP_Dimp::header(_("Message Composition"), $scripts);
echo $t->fetch('compose.html');
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo $compose_result['jsappend'];
echo "</body>\n</html>";
