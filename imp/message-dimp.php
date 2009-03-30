<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/base.php';

$folder = Util::getFormData('folder');
$index = Util::getFormData('uid');
if (!$index || !$folder) {
    exit;
}

$imp_ui = new IMP_UI_Message();
$readonly = $imp_imap->isReadOnly($folder);

$args = array(
    'headers' => array_diff(array_keys($imp_ui->basicHeaders()), array('subject')),
    'folder' => $folder,
    'index' => $index,
    'preview' => false,
);

$show_msg = new IMP_Views_ShowMessage();
$show_msg_result = $show_msg->showMessage($args);
if (isset($show_msg_result['error'])) {
    echo IMP::wrapInlineScript(array(
        DIMP::notify(false, 'parent.opener.document', 'parent.opener.DimpCore'),
        'parent.close()'
    ));
    exit;
}

$scripts = array(
    array('ContextSensitive.js', 'imp', true),
    array('fullmessage-dimp.js', 'imp', true),
    array('imp.js', 'imp', true)
);

$js_out = array(
    'DIMP.conf.msg_index = "' . $show_msg_result['index'] . '"',
    'DIMP.conf.msg_folder = "' . $show_msg_result['folder'] . '"'
);

foreach (array('from', 'to', 'cc', 'bcc', 'replyTo') as $val) {
    if (!empty($show_msg_result[$val])) {
        $js_out[] = 'DimpFullmessage.' . $val . ' = ' . Horde_Serialize::serialize($show_msg_result[$val], Horde_Serialize::JSON);
    }
}

/* Determine if compose mode is disabled. */
$disable_compose = !empty($conf['hooks']['disable_compose']) &&
    !Horde::callHook('_imp_hook_disable_compose', array(), 'imp');

if (!$disable_compose) {
    $compose_args = array(
        'folder' => $folder,
        'index' => $index,
        'messageCache' => '',
        'popup' => false,
        'qreply' => true,
    );
    $compose_result = IMP_Views_Compose::showCompose($compose_args);

    /* Init IMP_UI_Compose:: object. */
    $imp_ui = new IMP_UI_Compose();

    /* Attach spellchecker & auto completer. */
    $imp_ui->attachAutoCompleter(array('to', 'cc', 'bcc'));
    $imp_ui->attachSpellChecker('dimp');

    $js_out = array_merge($js_out, $compose_result['js']);
    $scripts[] = array('compose-dimp.js', 'imp', true);

    IMP::addInlineScript($compose_result['jsonload'], 'load');
}

IMP::addInlineScript($js_out);
IMP::addInlineScript(array(DIMP::notify()), 'dom');

DIMP::header($show_msg_result['subject'], $scripts);
echo "<body>\n";
require IMP_TEMPLATES . '/chunks/message.php';
IMP::includeScriptFiles();
IMP::outputInlineScript();
if (!$disable_compose) {
    echo $compose_result['jsappend'];
}
$notification->notify(array('listeners' => array('javascript')));
echo "</body>\n</html>";
