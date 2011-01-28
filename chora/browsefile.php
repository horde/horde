<?php
/**
 * Browse view (for files).
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
try {
    Horde_Registry::appInit('chora');
} catch (Exception $e) {
    Chora::fatal($e);
}

if ($atdir) {
    require CHORA_BASE . '/browsedir.php';
    exit;
}

$onb = Horde_Util::getFormData('onb', $VC->getDefaultBranch());
try {
    $fl = $VC->getFileObject($where, array('branch' => $onb));
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = $where;

$extraLink = Chora::getFileViews($where, 'browsefile');
$logs = $fl->queryLogs();
$first = end($logs);
$diffValueLeft = $first->queryRevision();
$diffValueRight = $fl->queryRevision();

$sel = '';
foreach ($fl->querySymbolicRevisions() as $sm => $rv) {
    $sel .= '<option value="' . $rv . '">' . $sm . '</option>';
}

$selAllBranches = '';
if ($VC->hasFeature('branches')) {
    foreach (array_keys($fl->queryBranches()) as $sym) {
        $selAllBranches .= '<option value="' . $sym . '"' . (($sym === $onb) ? ' selected="selected"' : '' ) . '>' . $sym . '</option>';
    }
}

Horde::addScriptFile('revlog.js', 'chora');
require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/log/header.inc';

reset($logs);
$view = $injector->createInstance('Horde_View');
$currentDay = null;
echo '<div class="commit-list">';
foreach ($logs as $log) {
    $day = date('Y-m-d', $log->queryDate());
    if ($day != $currentDay) {
        echo '<h3>' . $day . '</h3>';
        $currentDay = $day;
    }
    echo $view->renderPartial('app/views/logMessage', array('object' => $log));
}
echo '</div>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
