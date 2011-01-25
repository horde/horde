<?php
/**
 * Stats script.
 *
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

try {
    $fl = $VC->getFileObject($where);
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$extraLink = Chora::getFileViews($where, 'stats');

$stats = array();
foreach ($fl->queryLogs() as $lg) {
    $qa = $lg->queryAuthor();
    $stats[$qa] = isset($stats[$qa]) ? ($stats[$qa] + 1) : 1;
}
arsort($stats);

$title = sprintf(_("Statistics for %s"), $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($where, 'space2html', array('encode' => true, 'encode_all' => true)));
Horde::addScriptFile('tables.js', 'horde');
require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/stats/stats.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
