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
 * </pre>
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
Horde_Registry::appInit('imp', array('impmode' => 'dimp'));

Horde_Nls::setTimeZone();
$vars = Horde_Variables::getDefaultVariables();

/* Determine if compose mode is disabled. */
$compose_disable = !IMP::canCompose();

/* The headers of the message. */
$header = array();
foreach (array('to', 'cc', 'bcc', 'subject') as $v) {
    $header[$v] = rawurldecode($vars->$v);
}

$fillform_opts = array('noupdate' => 1);
$get_sig = true;
$js = array();
$msg = '';

$identity = Horde_Prefs_Identity::singleton(array('imp', 'imp'));
if (!$prefs->isLocked('default_identity') && isset($vars->identity)) {
    $identity->setDefault($vars->identity);
}

/* Initialize the IMP_Compose:: object. */
$imp_compose = IMP_Compose::singleton();

/* Init IMP_Ui_Compose:: object. */
$imp_ui = new IMP_Ui_Compose();

/* Attach spellchecker & auto completer. */
$imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
$imp_ui->attachSpellChecker('dimp');

$show_editor = false;
$title = _("New Message");

if (in_array($vars->type, array('reply', 'reply_all', 'reply_auto', 'reply_list', 'forward_attach', 'forward_auto', 'forward_body', 'forward_both', 'resume'))) {
    if (!$vars->uid || !$vars->folder) {
        $vars->type = 'new';
    }

    try {
        $imp_contents = IMP_Contents::singleton($vars->uid . IMP::IDX_SEP . $vars->folder);
    } catch (Horde_Exception $e) {
        $notification->push(_("Requested message not found."), 'horde.error');
        $vars->uid = $vars->folder = null;
        $vars->type = 'new';
    }
}

switch ($vars->type) {
case 'reply':
case 'reply_all':
case 'reply_auto':
case 'reply_list':
    $reply_msg = $imp_compose->replyMessage($vars->type, $imp_contents, $header['to']);
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
    $fwd_msg = $imp_compose->forwardMessage($vars->type, $imp_contents);
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
    $vars->type = 'forward';

    if (!$prefs->isLocked('default_identity') &&
        !is_null($fwd_msg['identity'])) {
        $identity->setDefault($fwd_msg['identity']);
    }
    break;

case 'resume':
    try {
        $result = $imp_compose->resumeDraft($vars->uid . IMP::IDX_SEP . $vars->folder);

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
    'folder' => $vars->folder,
    'qreply' => false,
    'uid' => $vars->uid
));

$t->set('compose_html', $compose_result['html']);

/* Javscript variables to be set immediately. */
if ($show_editor) {
    $js[] = 'DIMP.conf_compose.show_editor = 1';
}
if ($vars->popup) {
    $js[] = 'DIMP.conf_compose.popup = 1';
}
Horde::addInlineScript(array_merge($compose_result['js'], $js));

/* Javascript to be run on window load. */
$fillform_opts['focus'] = ($vars->type == 'new' || $vars->type == 'forward') ? 'to' : 'composeMessage';
$compose_result['js_onload'][] = 'DimpCompose.fillForm(' . Horde_Serialize::serialize($msg, Horde_Serialize::JSON) . ',' . Horde_Serialize::serialize($header, Horde_Serialize::JSON) . ',' . Horde_Serialize::serialize($fillform_opts, Horde_Serialize::JSON) . ')';
Horde::addInlineScript($compose_result['js_onload'], 'load');

$scripts = array(
    array('compose-dimp.js', 'imp'),
    array('md5.js', 'horde')
);

IMP::status();
IMP_Dimp::header(_("Message Composition"), $scripts);
echo $t->fetch(IMP_TEMPLATES . '/imp/compose.html');
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo $compose_result['jsappend'];
echo "</body>\n</html>";
