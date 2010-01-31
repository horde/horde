<?php
/**
 * Dynamic (dimp) compose display page.
 *
 * List of URL parameters:
 *   'popup' - Explicitly mark window as popup. Needed if compose page is
 *             opened from a page other than the base DIMP page.
 *   TODO
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('tz' => true));

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* The headers of the message. */
$header = array();
foreach (array('to', 'cc', 'bcc', 'subject') as $v) {
    $header[$v] = rawurldecode(Horde_Util::getFormData($v, ''));
}

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

/* Attach spellchecker & auto completer. */
$imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
$imp_ui->attachSpellChecker('dimp');

$type = Horde_Util::getFormData('type');
$uid = Horde_Util::getFormData('uid');
$folder = Horde_Util::getFormData('folder');
$show_editor = false;
$title = _("New Message");

if (in_array($type, array('reply', 'reply_all', 'reply_auto', 'reply_list', 'forward_attach', 'forward_auto', 'forward_body', 'forward_both', 'resume'))) {
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

case 'forward_attach':
case 'forward_auto':
case 'forward_body':
case 'forward_both':
    $fwd_msg = $imp_compose->forwardMessage($type, $imp_contents);
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

$t = $injector->createInstance('Horde_Template');
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
echo $t->fetch(IMP_TEMPLATES . '/imp/compose.html');
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo $compose_result['jsappend'];
echo "</body>\n</html>";
