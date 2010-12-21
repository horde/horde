<?php
/**
 * Dynamic (dimp) compose display page.
 *
 * <pre>
 * List of URL parameters:
 * -----------------------
 * 'bcc' - TODO
 * 'cc' - TODO
 * 'folder'
 * 'identity' - TODO
 * 'popup' - Explicitly mark window as popup. Needed if compose page is
 *           opened from a page other than the base DIMP page.
 * 'subject' - TODO
 * 'type' - TODO
 * 'to' - TODO
 * 'uid' - TODO
 * 'uids' - TODO
 * </pre>
 *
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
Horde_Registry::appInit('imp', array(
    'impmode' => 'dimp',
    'timezone' => true
));

$vars = Horde_Variables::getDefaultVariables();

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* The headers of the message. */
$header = array();
foreach (array('to', 'cc', 'bcc', 'subject') as $v) {
    $header[$v] = strval($vars->$v);
}

$fillform_opts = array('noupdate' => 1);
$get_sig = true;
$msg = '';

$js = array();
if ($vars->popup) {
    $js[] = 'DIMP.conf_compose.popup = 1';
}

$identity = $injector->getInstance('IMP_Identity');
if (!$prefs->isLocked('default_identity') && isset($vars->identity)) {
    $identity->setDefault($vars->identity);
}

/* Init objects. */
$imp_compose = $injector->getInstance('IMP_Injector_Factory_Compose')->create();
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

    $reply_msg = $imp_compose->replyMessage($vars->type, $contents, $header['to']);
    $msg = $reply_msg['body'];
    $header = $reply_msg['headers'];
    $header['replytype'] = 'reply';
    if ($vars->type == 'reply_auto') {
        $fillform_opts['auto'] = $reply_msg['type'];
    }
    $vars->type = $reply_msg['type'];

    if ($vars->type == 'reply') {
        $title = _("Reply:");
    } elseif ($vars->type == 'reply_all') {
        $title = _("Reply to All:");
    } elseif ($vars->type == 'reply_list') {
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
    $indices = $vars->uids
        ? new IMP_Indices($vars->uids)
        : null;

    if ($indices && (count($indices) > 1)) {
        if (!in_array($vars->type, array('forward_attach', 'forward_auto'))) {
            $notification->push(_("Multiple messages can only be forwarded as attachments."), 'horde.warning');
        }

        try {
            $header = array(
                'replytype' => 'forward',
                'subject' => $imp_compose->attachImapMessage(new IMP_Indices($vars->uids))
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

        $fwd_msg = $imp_compose->forwardMessage($vars->type, $contents);
        $msg = $fwd_msg['body'];
        $header = $fwd_msg['headers'];
        $header['replytype'] = 'forward';
        $title = $header['title'];
        if ($fwd_msg['format'] == 'html') {
            $show_editor = true;
        }
        if ($vars->type == 'forward_auto') {
            $fillform_opts['auto'] = $fwd_msg['type'];
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

case 'resume':
    try {
        $result = $imp_compose->resumeDraft(new IMP_Indices($vars->folder, $vars->uid));

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
        $js[] = 'DIMP.conf_compose.show_editor = 1';
    }
}

$t = $injector->createInstance('Horde_Template');
$t->setOption('gettext', true);
$t->set('title', $title);

$compose_result = IMP_Views_Compose::showCompose(array(
    'composeCache' => $imp_compose->getCacheId(),
    'redirect' => ($vars->type == 'redirect')
));

$t->set('compose_html', $compose_result['html']);

Horde::addInlineScript(array_merge($compose_result['js'], $js));

$fillform_opts['focus'] = in_array($vars->type, array('forward', 'new', 'redirect')) ? 'to' : 'composeMessage';
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

IMP::status();
IMP_Dimp::header($title, $scripts);
echo $t->fetch(IMP_TEMPLATES . '/dimp/compose/compose-base.html');
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
