<?php
/**
 * Stats script.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Anil Madhavapeddy <avsm@horde.org>
 * @package Chora
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('chora');

$extraLink = Chora::getFileViews($where, 'stats');

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

$title = sprintf(_("Statistics for %s"), $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)));
$page_output->addScriptFile('tables.js', 'horde');
$page_output->header(array(
    'title' => $title
));
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/stats/stats.inc';
$page_output->footer();
