<?php
/**
 * Stats script.
 *
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('chora');

/* Spawn the file object. */
try {
    $fl = $VC->getFile($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$stats = array();
foreach ($fl->getLog() as $lg) {
    $qa = $lg->getAuthor();
    $stats[$qa] = isset($stats[$qa]) ? ($stats[$qa] + 1) : 1;
}
arsort($stats);

$title = _("Statistics for:");
$page_output->addScriptFile('tables.js', 'horde');
Chora::header($title);
echo Chora::getHistoryViews($where)->render('stats');
require CHORA_TEMPLATES . '/stats/stats.inc';
$page_output->footer();
