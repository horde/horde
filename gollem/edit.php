<?php
/**
 * Gollem edit script.
 *
 * Copyright 2006-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('gollem');

$vars = Horde_Variables::getDefaultVariables();

if ($vars->driver != Gollem::$backend['driver']) {
    echo Horde::wrapInlineScript(array('window.close();'));
    exit;
}

/* Run through action handlers. */
switch ($vars->actionID) {
case 'save_file':
    try {
        $injector
            ->getInstance('Gollem_Vfs')
            ->writeData($vars->filedir, $vars->filename, $vars->content);
        $message = sprintf(_("%s successfully saved."), $vars->filename);
    } catch (Horde_Vfs_Exception $e) {
        $message = sprintf(_("Access denied to %s"), $vars->filename);
    }
    echo Horde::wrapInlineScript(array(
        'alert("' . addslashes($message) . '")'
    ));
    break;

case 'edit_file':
    try {
        $data = $injector
            ->getInstance('Gollem_Vfs')
            ->read($vars->filedir, $vars->filename);
    } catch (Horde_Vfs_Exception $e) {
        echo Horde::wrapInlineScript(array(
            'alert("' . addslashes(sprintf(_("Access denied to %s"), $vars->filename)) . '")'
        ));
        break;
    }

    $mime_type = Horde_Mime_Magic::extToMIME($vars->type);
    if (strpos($mime_type, 'text/') !== 0) {
        break;
    }

    if ($mime_type == 'text/html') {
        $injector->getInstance('Horde_Editor')->initialize(array('id' => 'content'));
    }
    require $registry->get('templates', 'horde') . '/common-header.inc';
require GOLLEM_TEMPLATES . '/javascript_defs.php';
    Gollem::status();
    require GOLLEM_TEMPLATES . '/edit/edit.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

echo Horde::wrapInlineScript(array('window.close()'));
