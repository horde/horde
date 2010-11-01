<?php
/**
 * Browse view (for files).
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('chora');

if ($atdir) {
    require CHORA_BASE . '/browsedir.php';
    exit;
}

$onb = Horde_Util::getFormData('onb');
try {
    $fl = $VC->getFileObject($where, array('branch' => $onb));
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = sprintf(_("Revisions for %s"), $where);

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

Horde::addScriptFile('tables.js', 'horde');
Horde::addScriptFile('quickfinder.js', 'horde');
Horde::addScriptFile('revlog.js', 'chora');
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/log/header.inc';

$i = 0;
$diff_img = Horde::img('diff.png');
reset($logs);
while (list(,$lg) = each($logs)) {
    $rev = $lg->queryRevision();
    $branch_info = $lg->queryBranch();

    $added = $deleted = null;
    $fileinfo = $lg->queryFiles($where);
    if ($fileinfo && isset($fileinfo['added'])) {
        $added = $fileinfo['added'];
        $deleted = $fileinfo['deleted'];
    }

    // TODO: Remove in favor of getting info from queryFiles()
    $changedlines = $lg->queryChangedLines();

    $textUrl = Chora::url('co', $where, array('r' => $rev));
    $commitDate = Chora::formatDate($lg->queryDate());
    $readableDate = Chora::readableTime($lg->queryDate(), true);

    $author = Chora::showAuthorName($lg->queryAuthor(), true);
    $tags = Chora::getTags($lg, $where);

    if ($prevRevision = $fl->queryPreviousRevision($lg->queryRevision())) {
        $diffUrl = Chora::url('diff', $where, array('r1' => $prevRevision, 'r2' => $rev));
    } else {
        $diffUrl = '';
    }

    $logMessage = Chora::formatLogMessage($lg->queryLog());

    require CHORA_TEMPLATES . '/log/rev.inc';

    if (($i++ > 100) && !Horde_Util::getFormData('all')) {
        break;
    }
}
require CHORA_TEMPLATES . '/log/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
