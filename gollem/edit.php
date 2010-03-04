<?php
/**
 * Gollem edit script.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$actionID = Horde_Util::getFormData('actionID');
$driver = Horde_Util::getFormData('driver');
$filedir = Horde_Util::getFormData('dir');
$filename = Horde_Util::getFormData('file');
$type = Horde_Util::getFormData('type');

if ($driver != $GLOBALS['gollem_be']['driver']) {
    echo Horde::wrapInlineScript(array('window.close();'));
    exit;
}

/* Run through action handlers. */
switch ($actionID) {
case 'save_file':
    $data = Horde_Util::getFormData('content');
    $result = $gollem_vfs->writeData($filedir, $filename, $data);
    if (is_a($result, 'PEAR_Error')) {
        $message = sprintf(_("Access denied to %s"), $filename);
    } else {
        $message = sprintf(_("%s successfully saved."), $filename);
    }
    echo Horde::wrapInlineScript(array(
        'alert("' . addslashes($message) . '");',
        'window.close();'
    ));
    exit;

case 'edit_file':
    $data = $gollem_vfs->read($filedir, $filename);
    if (is_a($data, 'PEAR_Error')) {
        echo Horde::wrapInlineScript(array(
            'alert("' . addslashes(sprintf(_("Access denied to %s"), $filename)) . '");',
            'window.close();'
        ));
        exit;
    }
    $mime_type = Horde_Mime_Magic::extToMIME($type);
    if (strpos($mime_type, 'text/') !== 0) {
        echo Horde::wrapInlineScript(array('window.close();'));
    }
    if ($mime_type == 'text/html') {
        $editor = Horde_Editor::singleton('ckeditor', array('id' => 'content'));
    }
    require GOLLEM_TEMPLATES . '/common-header.inc';
    Gollem::status();
    require GOLLEM_TEMPLATES . '/edit/edit.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

wcho Horde::wrapInlineScript(array('window.close();'));
