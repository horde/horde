<?php
/**
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
Horde_Registry::appInit('imp');

$folder = Horde_Util::getFormData('folder');
$uid = Horde_Util::getFormData('uid');
if (!$uid || !$folder) {
    exit;
}

$imp_ui = new IMP_Ui_Message();
$readonly = $imp_imap->isReadOnly($folder);

$args = array(
    'headers' => array_diff(array_keys($imp_ui->basicHeaders()), array('subject')),
    'mailbox' => $folder,
    'preview' => false,
    'uid' => $uid
);

$show_msg = new IMP_Views_ShowMessage();
$show_msg_result = $show_msg->showMessage($args);
if (isset($show_msg_result['error'])) {
    echo Horde::wrapInlineScript(array(
        IMP_Dimp::notify(),
        'parent.close()'
    ));
    exit;
}

$scripts = array(
    array('ContextSensitive.js', 'horde'),
    array('fullmessage-dimp.js', 'imp'),
    array('imp.js', 'imp')
);

$js_onload = $js_out = array();
foreach (array('from', 'to', 'cc', 'bcc', 'replyTo', 'log', 'uid', 'mailbox') as $val) {
    if (!empty($show_msg_result[$val])) {
        $js_out[] = 'DimpFullmessage.' . $val . ' = ' . Horde_Serialize::serialize($show_msg_result[$val], Horde_Serialize::JSON);
    }
}

/* Determine if compose mode is disabled. */
$disable_compose = !IMP::canCompose();

if (!$disable_compose) {
    $compose_args = array(
        'folder' => $folder,
        'messageCache' => '',
        'popup' => false,
        'qreply' => true,
        'uid' => $uid,
    );
    $compose_result = IMP_Views_Compose::showCompose($compose_args);

    /* Init IMP_Ui_Compose:: object. */
    $imp_ui = new IMP_Ui_Compose();

    /* Attach spellchecker & auto completer. */
    $imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
    $imp_ui->attachSpellChecker('dimp');

    $js_out = array_merge($js_out, $compose_result['js']);
    $scripts[] = array('compose-dimp.js', 'imp');

    $js_onload = $compose_result['jsonload'];
}

$js_onload[] = IMP_Dimp::notify();
if (isset($show_msg_result['js'])) {
    $js_onload = array_merge($js_onload, $show_msg_result['js']);
}

Horde::addInlineScript($js_out);
Horde::addInlineScript(array_filter($js_onload), 'load');

IMP_Dimp::header($show_msg_result['title'], $scripts);
echo "<body>\n";
require IMP_TEMPLATES . '/chunks/message.php';
Horde::includeScriptFiles();
Horde::outputInlineScript();
if (!$disable_compose) {
    echo $compose_result['jsappend'];
}
echo "</body>\n</html>";
