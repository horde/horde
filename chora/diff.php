<?php
/**
 * Diff display.
 *
 * Parameters in:
 *   - r1: Revision 1
 *   - r2: Revision 2 (if empty, will diff to previous revision of r1)
 *   - t: 'colored'
 *   - ty: Diff type
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Anil Madhavapeddy <avsm@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Chora
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('chora');

/* Spawn the repository and file objects */
try {
    $fl = $VC->getFile($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$vars = Horde_Variables::getDefaultVariables();

if (!isset($vars->r2)) {
    $vars->r2 = $vars->r1;
    $vars->r1 = $fl->getPreviousRevision($vars->r1);
}

/* Ensure that we have valid revision numbers. */
if (!$VC->isValidRevision($vars->r1) || !$VC->isValidRevision($vars->r2)) {
    Chora::fatal(_("Malformed Query"), '500 Internal Server Error');
}

/* If no type has been specified, then default to human readable. */
$type = $vars->get('t', 'colored');
if ($vars->ty == 'u') {
    $type = 'unified';
}

/* Cache the output of the diff for a week - it can be longer, since
 * it should never change. */
header('Cache-Control: max-age=604800');

/* All is ok, proceed with the diff. Always make sure there is a newline at
 * the end of the file - patch requires it. */
if ($type != 'colored') {
    header('Content-Type: text/plain');
    echo implode("\n", $VC->diff($fl, $vars->r1, $vars->r2, array('num' => $num, 'type' => $type))) . "\n";
    exit;
}

/* Human-Readable diff. */
$abbrev_r1 = $VC->abbrev($vars->r1);
$abbrev_r2 = $VC->abbrev($vars->r2);
$title = sprintf(_("Diff for %s between version %s and %s"),
                 $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)), $abbrev_r1, $abbrev_r2);

/* Format log entries. */
$log_messages = array();
foreach ($VC->getRevisionRange($fl, $vars->r1, $vars->r2) as $val) {
    $clog = $fl->getLog($val);
    if (!is_null($clog)) {
        $log_messages[] = $clog;
    }
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/diff/header.inc';

$mime_type = Horde_Mime_Magic::filenameToMIME($fullname);
if (substr($mime_type, 0, 6) == 'image/') {
    /* Check for images. */
    $url1 = Chora::url('co', $where, array('r' => $vars->r1, 'p' => 1));
    $url2 = Chora::url('co', $where, array('r' => $vars->r2, 'p' => 1));

    echo "<tr><td><img src=\"$url1\" alt=\"" . htmlspecialchars($vars->r1) . '" /></td>' .
        "<td><img src=\"$url2\" alt=\"" . htmlspecialchars($vars->r2) . '" /></td></tr>';
} else {
    $view = $injector->createInstance('Horde_View');
    $view->addHelper('Chora_Diff_Helper');
    echo $view->diff($fl, $vars->r1, $vars->r2);
    echo $view->diffCaption();
}

$page_output->footer();
