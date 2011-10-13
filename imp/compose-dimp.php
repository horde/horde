<?php
/**
 * Dynamic (dimp) compose display page.
 *
 * List of URL parameters:
 * -----------------------
 *   - bcc: BCC addresses.
 *   - body: Message body text.
 *   - cc: CC addresses.
 *   - identity: Force message to use this identity by default.
 *   - subject: Subject to use.
 *   - type: redirect, reply, reply_auto, reply_all, reply_list,
 *           forward_attach, forward_auto, forward_body, forward_both,
 *           forward_redirect, resume, new, editasnew
 *   - to: Address to send to.
 *   - toname: If set, will be used as personal part of e-mail address
 *             (requires 'to' parameter also).
 *   - uids: UIDs of message to forward (only used when forwarding a message).
 *
 * Copyright 2005-2011 Horde LLC (http://www.horde.org/)
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
Horde_Registry::appInit('imp', array(
    'impmode' => 'dimp',
    'timezone' => true
));

$vars = Horde_Variables::getDefaultVariables();

/* The headers of the message. */
$header = array();
$args = IMP::getComposeArgs();
foreach (array('to', 'cc', 'bcc', 'subject') as $val) {
    if (isset($args[$val])) {
        $header[$val] = $args[$val];
    }
}

/* Check for personal information for 'to' address. */
if (isset($header['to']) &&
    isset($vars->toname) &&
    ($tmp = Horde_Mime_Address::parseAddressList($header['to']))) {
    $header['to'] = Horde_Mime_Address::writeAddress($tmp[0]['mailbox'], $tmp[0]['host'], $vars->toname);
}

$fillform_opts = array('noupdate' => 1);
$get_sig = true;
$msg = $vars->body;

$js = array();

$identity = $injector->getInstance('IMP_Identity');
if (!$prefs->isLocked('default_identity') && isset($vars->identity)) {
    $identity->setDefault($vars->identity);
}

/* Init objects. */
$imp_compose = $injector->getInstance('IMP_Factory_Compose')->create();
$imp_ui = new IMP_Ui_Compose();

$show_editor = false;
$title = _("New Message");

switch ($vars->type) {
case 'reply':
case 'reply_all':
case 'reply_auto':
case 'reply_list':
    try {
        $contents = $imp_ui->getContents($vars);
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
        break;
    }

    $reply_map = array(
        'reply' => IMP_Compose::REPLY_SENDER,
        'reply_all' => IMP_Compose::REPLY_ALL,
        'reply_auto' => IMP_Compose::REPLY_AUTO,
        'reply_list' => IMP_Compose::REPLY_LIST
    );

    $reply_msg = $imp_compose->replyMessage($reply_map[$vars->type], $contents, isset($header['to']) ? $header['to'] : null);

    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    if ($vars->type == 'reply_auto') {
        $fillform_opts['auto'] = array_search($reply_msg['type'], $reply_map);

        if (isset($reply_msg['reply_recip'])) {
            $fillform_opts['reply_recip'] = $reply_msg['reply_recip'];
        }

        if (isset($reply_msg['reply_list_id'])) {
            $fillform_opts['reply_list_id'] = $reply_msg['reply_list_id'];
        }
    }

    if (!empty($reply_msg['lang'])) {
        $fillform_opts['reply_lang'] = array_values($reply_msg['lang']);
    }

    switch ($reply_msg['type']) {
    case IMP_Compose::REPLY_SENDER:
        $title = _("Reply:");
        $vars->type = 'reply';
        break;

    case IMP_Compose::REPLY_ALL:
        $title = _("Reply to All:");
        $vars->type = 'reply_all';
        break;

    case IMP_Compose::REPLY_LIST:
        $title = _("Reply to List:");
        $vars->type = 'reply_list';
        break;
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
    $indices = $vars->uids
        ? new IMP_Indices_Form($vars->uids)
        : null;

    if ($indices && (count($indices) > 1)) {
        if (!in_array($vars->type, array('forward_attach', 'forward_auto'))) {
            $notification->push(_("Multiple messages can only be forwarded as attachments."), 'horde.warning');
        }

        try {
            $header = array(
                'subject' => $imp_compose->attachImapMessage($indices)
            );
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
            break;
        }

        $rte = $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));
    } else {
        try {
            $contents = $imp_ui->getContents($vars);
        } catch (IMP_Compose_Exception $e) {
            $notification->push($e, 'horde.error');
            break;
        }

        $fwd_map = array(
            'forward_attach' => IMP_Compose::FORWARD_ATTACH,
            'forward_auto' => IMP_Compose::FORWARD_AUTO,
            'forward_body' => IMP_Compose::FORWARD_BODY,
            'forward_both' => IMP_Compose::FORWARD_BOTH
        );

        $fwd_msg = $imp_compose->forwardMessage($fwd_map[$vars->type], $contents);
        $msg = $fwd_msg['body'];
        $header = $fwd_msg['headers'];
        $title = $header['title'];
        if ($fwd_msg['format'] == 'html') {
            $show_editor = true;
        }
        if ($vars->type == 'forward_auto') {
            $fillform_opts['auto'] = array_search($fwd_msg['type'], $fwd_map);
        }

        if (!$prefs->isLocked('default_identity') &&
            !is_null($fwd_msg['identity'])) {
            $identity->setDefault($fwd_msg['identity']);
        }
    }

    $vars->type = 'forward';
    break;

case 'forward_redirect':
    try {
        $contents = $imp_ui->getContents($vars);
        $imp_compose->redirectMessage($contents);
        $get_sig = false;
        $title = _("Redirect");
        $vars->type = 'redirect';
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e, 'horde.error');
    }
    break;

case 'editasnew':
case 'resume':
    try {
        $result = $imp_compose->resumeDraft(IMP::$mailbox->getIndicesOb(IMP::$uid), ($vars->type == 'resume'));

        if ($result['mode'] == 'html') {
            $show_editor = true;
        }
        $msg = $result['msg'];
        if (!is_null($result['identity']) &&
            !$prefs->isLocked('default_identity')) {
            $identity->setDefault($result['identity']);
        }
        $header = array_merge($header, $result['header']);
        $fillform_opts['priority'] = $result['priority'];
        $fillform_opts['readreceipt'] = $result['readreceipt'];
    } catch (IMP_Compose_Exception $e) {
        $notification->push($e);
    }
    $get_sig = false;
    break;

case 'new':
    $rte = $show_editor = ($prefs->getValue('compose_html') && $session->get('imp', 'rteavail'));
    break;
}

/* Attach spellchecker & auto completer. */
if ($vars->type == 'redirect') {
    $imp_ui->attachAutoCompleter(array('redirect_to'));
} else {
    $acomplete = array('to', 'redirect_to');
    foreach (array('cc', 'bcc') as $val) {
        if ($prefs->getValue('compose_' . $val)) {
            $acomplete[] = $val;
        }
    }
    $imp_ui->attachAutoCompleter($acomplete);
    $imp_ui->attachSpellChecker();
    $sig = $identity->getSignature($show_editor ? 'html' : 'text');
    if ($get_sig && !empty($sig)) {
        if ($identity->getValue('sig_first')) {
            $msg = $sig . $msg;
        } else {
            $msg .= $sig;
        }
    }

    if ($show_editor) {
        $js['DIMP.conf_compose.show_editor'] = 1;
    }
}

$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('title', $title);

$compose_result = IMP_Views_Compose::showCompose(array(
    'composeCache' => $imp_compose->getCacheId(),
    'fwdattach' => (isset($fwd_msg) && ($fwd_msg['type'] != IMP_Compose::FORWARD_BODY)),
    'redirect' => ($vars->type == 'redirect'),
    'show_editor' => $show_editor
));

$t->set('compose_html', $compose_result['html']);

Horde::addInlineJsVars($js);
Horde::addInlineScript($compose_result['js']);

$fillform_opts['focus'] = (($vars->type == 'new') && isset($args['to']))
    ? 'composeMessage'
    : (in_array($vars->type, array('forward', 'new', 'redirect', 'editasnew')) ? 'to' : 'composeMessage');

if ($vars->type != 'redirect') {
    $compose_result['jsonload'][] = 'DimpCompose.fillForm(' . Horde_Serialize::serialize($msg, Horde_Serialize::JSON) . ',' . Horde_Serialize::serialize($header, Horde_Serialize::JSON) . ',' . Horde_Serialize::serialize($fillform_opts, Horde_Serialize::JSON) . ')';
}
Horde::addInlineScript($compose_result['jsonload'], 'dom');

$scripts = array(
    array('compose-base.js', 'imp'),
    array('compose-dimp.js', 'imp'),
    array('md5.js', 'horde'),
    array('popup.js', 'horde'),
    array('textarearesize.js', 'horde')
);

if (!($prefs->isLocked('default_encrypt')) &&
    ($prefs->getValue('use_pgp') || $prefs->getValue('use_smime'))) {
    $scripts[] = array('dialog.js', 'imp');
    $scripts[] = array('redbox.js', 'horde');
}

Horde::startBuffer();
IMP::status();
$t->set('status', Horde::endBuffer());

IMP_Dimp::header($title, $scripts);
echo $t->fetch(IMP_TEMPLATES . '/dimp/compose/compose-base.html');
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
