<?php
/**
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('chora');
} catch (Exception $e) {
    Chora::fatal($e);
}

/* Spawn the repository and file objects */
try {
    $fl = $VC->getFileObject($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

/* Initialise the form variables correctly. */
$r1 = Horde_Util::getFormData('r1');
$r2 = Horde_Util::getFormData('r2');

/* Ensure that we have valid revision numbers. */
if (!$VC->isValidRevision($r1) || !$VC->isValidRevision($r2)) {
    Chora::fatal(_("Malformed Query"), '500 Internal Server Error');
}

/* If no type has been specified, then default to human readable. */
$type = Horde_Util::getFormData('t', 'colored');
if (Horde_Util::getFormData('ty') == 'u') {
    $type = 'unified';
}

/* Cache the output of the diff for a week - it can be longer, since
 * it should never change. */
header('Cache-Control: max-age=604800');

/* All is ok, proceed with the diff. Always make sure there is a newline at
 * the end of the file - patch requires it. */
if ($type != 'colored') {
    header('Content-Type: text/plain');
    echo implode("\n", $VC->diff($fl, $r1, $r2, array('num' => $num, 'type' => $type))) . "\n";
    exit;
}

/* Human-Readable diff. */
$abbrev_r1 = $VC->abbrev($r1);
$abbrev_r2 = $VC->abbrev($r2);
$title = sprintf(_("Diff for %s between version %s and %s"),
                 $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)), $abbrev_r1, $abbrev_r2);

/* Format log entries. */
$log_messages = array();
foreach ($VC->getRevisionRange($fl, $r1, $r2) as $val) {
    $clog = $fl->queryLogs($val);
    if (!is_null($clog)) {
        $log_messages[] = $clog;
    }
}

Horde::addScriptFile('stripe.js', 'horde');
require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/diff/header.inc';

$mime_type = Horde_Mime_Magic::filenameToMIME($fullname);
if (substr($mime_type, 0, 6) == 'image/') {
    /* Check for images. */
    $url1 = Chora::url('co', $where, array('r' => $r1, 'p' => 1));
    $url2 = Chora::url('co', $where, array('r' => $r2, 'p' => 1));

    echo "<tr><td><img src=\"$url1\" alt=\"" . htmlspecialchars($r1) . '" /></td>' .
        "<td><img src=\"$url2\" alt=\"" . htmlspecialchars($r2) . '" /></td></tr>';
} else {
    $view = $injector->createInstance('Horde_View');
    $view->addHelper('Chora_Diff_Helper');
    echo $view->diff($fl, $r1, $r2);
    echo $view->diffCaption();
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
