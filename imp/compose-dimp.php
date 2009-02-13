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

function _removeAutoSaveDraft($index)
{
    if (!empty($index)) {
        $imp_message = &IMP_Message::singleton();
        $imp_message->delete(array($index . IMP::IDX_SEP . IMP::folderPref($GLOBALS['prefs']->getValue('drafts_folder'), true)), true);
    }
}

require_once dirname(__FILE__) . '/lib/base.php';

/* The headers of the message. */
$header = array();
foreach (array('to', 'cc', 'bcc', 'subject', 'in_reply_to', 'references') as $v) {
    $header[$v] = rawurldecode(Util::getFormData($v, ''));
}

$action = Util::getFormData('action');
$get_sig = true;
$msg = '';

require_once 'Horde/Identity.php';
$identity = &Identity::singleton(array('imp', 'imp'));
if (!$prefs->isLocked('default_identity')) {
    $identity_id = Util::getFormData('identity');
    if (!is_null($identity_id)) {
        $identity->setDefault($identity_id);
    }
}

/* Set the current time zone. */
NLS::setTimeZone();

/* Initialize the IMP_Compose:: object. */
$imp_compose = &IMP_Compose::singleton(Util::getFormData('composeCache'));

/* Init IMP_UI_Compose:: object. */
$imp_ui = new IMP_UI_Compose();

if (count($_POST)) {
    $result = new stdClass;
    $result->action = $action;
    $result->success = false;

    /* Update the file attachment information. */
    if ($action == 'add_attachment') {
        if ($_SESSION['imp']['file_upload'] &&
            $imp_compose->addFilesFromUpload('file_')) {
            $info = DIMP::getAttachmentInfo($imp_compose);
            $result->success = true;
            $result->info = end($info);
            $result->imp_compose = $imp_compose->getCacheId();
        }
        Horde::sendHTTPResponse(Horde::prepareResponse($result, $GLOBALS['imp_notify'], false), 'js-json');
        exit;
    }

    /* Set the default charset. */
    $charset = NLS::getEmailCharset();
    if (!$prefs->isLocked('sending_charset')) {
        $charset = Util::getFormData('charset', $charset);
    }

    switch ($action) {
    case 'auto_save_draft':
    case 'save_draft':
        /* Set up the From address based on the identity. */
        $from = $identity->getFromLine(null, Util::getFormData('from'));
        if (is_a($from, 'PEAR_Error')) {
            $notification->push($from);
            break;
        }
        $header['from'] = $from;

        /* Save the draft. */
        $res = $imp_compose->saveDraft($header, Util::getFormData('message', ''), NLS::getCharset(), Util::getFormData('html'));
        if (is_a($res, 'PEAR_Error')) {
            $notification->push($res->getMessage(), 'horde.error');
        } else {
            $result->success = true;

            /* Delete existing draft. */
            _removeAutoSaveDraft(Util::getFormData('draft_index'));

            if ($action == 'auto_save_draft') {
                /* Just update the last draft index so subsequent
                 * drafts are properly replaced. */
                $result->draft_index = (int)$imp_compose->saveDraftIndex();
            } else {
                $notification->push($res);
            }
        }
        break;

    case 'send_message':
        $from = $identity->getFromLine(null, Util::getFormData('from'));
        if (is_a($from, 'PEAR_Error')) {
            $notification->push($from);
            break;
        }
        $header['from'] = $from;
        $header['replyto'] = $identity->getValue('replyto_addr');

        $header['to'] = $imp_ui->getAddressList(Util::getFormData('to'), Util::getFormData('to_list'), Util::getFormData('to_field'), Util::getFormData('to_new'));
        if ($prefs->getValue('compose_cc')) {
            $header['cc'] = $imp_ui->getAddressList(Util::getFormData('cc'), Util::getFormData('cc_list'), Util::getFormData('cc_field'), Util::getFormData('cc_new'));
        }
        if ($prefs->getValue('compose_bcc')) {
            $header['bcc'] = $imp_ui->getAddressList(Util::getFormData('bcc'), Util::getFormData('bcc_list'), Util::getFormData('bcc_field'), Util::getFormData('bcc_new'));
        }

        $message = Util::getFormData('message');
        $html = Util::getFormData('html');

        $result->reply_type = Util::getFormData('reply_type');
        $result->index = Util::getFormData('index');
        $result->reply_folder = Util::getFormData('folder');

        /* Use IMP_Tree to determine whether the sent mail folder was
         * created. */
        $imptree = &IMP_IMAP_Tree::singleton();
        $imptree->eltDiffStart();

        $options = array(
            'save_sent' => (($prefs->isLocked('save_sent_mail'))
                            ? $identity->getValue('save_sent_mail')
                            : (bool)Util::getFormData('save_sent_mail')),
            'sent_folder' => $identity->getValue('sent_mail_folder'),
            'save_attachments' => Util::getFormData('save_attachments_select'),
            'reply_type' => $result->reply_type,
            'reply_index' => $result->index . IMP::IDX_SEP . $result->reply_folder,
            'readreceipt' => Util::getFormData('request_read_receipt')
        );
        $sent = $imp_compose->buildAndSendMessage($message, $header, $charset, $html, $options);

        if (is_a($sent, 'PEAR_Error')) {
            $notification->push($sent, 'horde.error');
            break;
        }
        $result->success = true;

        /* Remove any auto-saved drafts. */
        if ($prefs->getValue('auto_save_drafts') ||
            $prefs->getValue('auto_delete_drafts')) {
            _removeAutoSaveDraft(Util::getFormData('draft_index'));
            $result->draft_delete = true;
        }

        if ($sent && $prefs->getValue('compose_confirm')) {
            $notification->push(_("Message sent successfully."), 'horde.success');
        }

        $res = DIMP::getFolderResponse($imptree);
        if (!empty($res)) {
            $result->folder = $res['a'][0];
        }
    }

    Horde::sendHTTPResponse(Horde::prepareResponse($result, !$result->success || !Util::getFormData('nonotify')  ? $GLOBALS['imp_notify'] : null, false), 'json');
    exit;
}

/* Attach spellchecker & auto completer. */
$imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
$imp_ui->attachSpellChecker('dimp');

$type = Util::getFormData('type');
$index = Util::getFormData('uid');
$folder = Util::getFormData('folder');
$show_editor = false;
$title = _("New Message");

if (in_array($type, array('reply', 'reply_all', 'reply_list', 'forward_all', 'forward_body', 'forward_attachments', 'resume'))) {
    if (!$index || !$folder) {
        $type = 'new';
    }

    $imp_contents = &IMP_Contents::singleton($index . IMP::IDX_SEP . $folder);
    if (is_a($imp_contents, 'PEAR_Error')) {
        $notification->push(_("Requested message not found."), 'horde.error');
        $index = $folder = null;
        $type = 'new';
    }
}

switch ($type) {
case 'reply':
case 'reply_all':
case 'reply_list':
    $reply_msg = $imp_compose->replyMessage($type, $imp_contents, Util::getFormData('to'));
    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    $header['replytype'] = 'reply';

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

case 'forward_all':
case 'forward_body':
case 'forward_attachments':
    $fwd_msg = $imp_ui->getForwardData($imp_compose, $imp_contents, $type, $index . IMP::IDX_SEP . $folder);
    if ($type == 'forward_all') {
        $msg = '';
    } else {
        $msg = $fwd_msg['body'];
    }
    $header = $fwd_msg['headers'];
    $header['replytype'] = 'forward';
    $title = $header['title'];
    if ($fwd_msg['format'] == 'html') {
        $show_editor = true;
    }
    $type = 'forward';

    if (!$prefs->isLocked('default_identity') && !is_null($fwd_msg['identity'])) {
        $identity->setDefault($fwd_msg['identity']);
    }
    break;

case 'resume':
    $result = $imp_compose->resumeDraft($index . IMP::IDX_SEP . $folder);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result->getMessage(), 'horde.error');
    } else {
        if ($result['mode'] == 'html') {
            $show_editor = true;
        }
        $msg = $result['msg'];
        if (!is_null($result['identity']) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
        }
        $header = array_merge($header, $result['header']);
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

$args = array(
    'folder' => $folder,
    'index' => $index,
    'composeCache' => $imp_compose->getCacheId(),
    'qreply' => false,
);

$t = new IMP_Template(IMP_TEMPLATES . '/imp/');
$t->setOption('gettext', true);
$t->set('title', $title);
$t->set('closelink', IMP::img('close.png', 'X', array('id' => 'compose_close'), $registry->getImageDir('horde')));

$compose_result = IMP_Views_Compose::showCompose($args);
$t->set('compose_html', $compose_result['html']);

/* Javscript variables to be set immediately. */
$compose_result['js'][] = 'DIMP.conf_compose.show_editor = ' . intval($show_editor);
if (Util::getFormData('popup')) {
    $compose_result['js'][] = 'DIMP.conf_compose.popup = true';
}
IMP::addInlineScript($compose_result['js']);

/* Some actions, like adding forwards, may return error messages so explicitly
 * display those messages now. */
IMP::addInlineScript(array(DIMP::notify()), 'dom');

/* Javascript to be run on window load. */
$compose_result['js_onload'][] = 'DimpCompose.fillForm(' . Horde_Serialize::serialize($msg, Horde_Serialize::JSON) . ', ' . Horde_Serialize::serialize($header, Horde_Serialize::JSON) . ', "' . (($type == 'new' || $type == 'forward') ? 'to' : 'message') . '", true)';
IMP::addInlineScript($compose_result['js_onload'], 'load');

$scripts = array(
    array('compose-dimp.js', 'imp', true)
);

DIMP::header(_("Message Composition"), $scripts);
echo $t->fetch('compose.html');
IMP::includeScriptFiles();
IMP::outputInlineScript();
echo $compose_result['jsappend'];
echo "</body>\n</html>";
