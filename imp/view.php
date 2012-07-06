<?php
/**
 * Output script for various data elements generated in IMP.
 *
 * URL parameters:
 * ---------------
 *   - actionID: (string) The action ID to perform:
 *     - compose_attach_preview
 *     - print_attach
 *     - view_attach
 *     - view_face
 *     - view_source
 *   - autodetect: (integer) If set, tries to autodetect MIME type when
 *                 viewing based on data.
 *   - composeCache: (string) Cache ID for compose object.
 *   - ctype: (string) The content-type to use instead of the content-type
 *            found in the original Horde_Mime_Part object.
 *   - id: (string) The MIME part ID to display.
 *   - mode: (integer) The view mode to use.
 *           DEFAULT: IMP_Contents::RENDER_FULL
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp');

$vars = $injector->getInstance('Horde_Variables');

/* Run through action handlers */
switch ($vars->actionID) {
case 'compose_attach_preview':
    $view_ob = new IMP_Compose_View($vars->composeCache);
    $res = $view_ob->composeAttachPreview($vars->id, $vars->autodetect, $vars->ctype);
    break;

case 'print_attach':
    $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
    $res = $view_ob->printAttach($vars->id);
    break;

case 'view_attach':
    $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
    $res = $view_ob->viewAttach($vars->id, $vars->mode, $vars->autodetect, $vars->ctype);
    break;

case 'view_face':
    $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
    $res = $view_ob->viewFace();
    break;

case 'view_source':
    $view_ob = new IMP_Contents_View(IMP::mailbox(true), IMP::uid());
    $res = $view_ob->viewSource();
    break;
}

if (empty($res)) {
    exit;
}

if (is_resource($res['data'])) {
    fseek($res['data'], 0, SEEK_END);
    $size = ftell($res['data']);
} else {
    $size = strlen($res['data']);
}

$browser->downloadHeaders(
    isset($res['name']) ? $res['name'] : '',
    isset($res['type']) ? $res['type'] : '',
    true,
    $size
);

if (is_resource($res['data'])) {
    rewind($res['data']);
    while (!feof($res['data'])) {
        echo fread($res['data'], 8192);
    }
    fclose($res['data']);
} else {
    echo $res['data'];
}
