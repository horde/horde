<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';

// Exit if cvsps isn't active or it's not a subversion repository.
if (empty($conf['paths']['cvsps']) && !is_a($VC, 'VC_svn')) {
    header('Location: ' . Chora::url('', $where));
    exit;
}

if (@is_dir($fullname)) {
    Chora::fatal(_("No patchsets for directories yet."), '501 Not Implemented');
}

if (!$VC->isFile($fullname)) {
    Chora::fatal(sprintf(_("%s: no such file or directory"), $where), '404 Not Found');
}

$ps = $VC->getPatchsetObject($where, $cache);
Chora::checkError($ps);

$title = sprintf(_("Patchsets for %s"), $where);
$extraLink = Chora::getFileViews();

Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);
Horde::addScriptFile('QuickFinder.js', 'horde', true);
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/patchsets/header.inc';

$patchsets = $ps->_patchsets;
krsort($patchsets);
foreach ($patchsets as $id => $patchset) {
    $commitDate = Chora::formatTime($patchset['date']);
    $readableDate = Chora::readableTime($patchset['date'], true);
    $author = Chora::showAuthorName($patchset['author'], true);
    if (is_a($VC, 'VC_svn')) {
        // The diff should be from the top of the source tree so as to
        // get all files.
        $topDir = substr($where, 0, strpos($where, '/', 1));

        // Subversion supports patchset diffs natively.
        $patchset_link = Horde::link(Chora::url('diff', $topDir, array('r1' => $id - 1, 'r2' => $id, 't' => 'unified'))) .
            $id . '</a>';
    } else {
        // Not supported in any other VC systems yet.
        $patchset_link = $id;
    }

    $files = array();
    $dir = dirname($where);
    foreach ($patchset['members'] as $member) {
        $file = array();
        $mywhere = is_a($VC, 'VC_svn') ? $member['file'] : $dir . '/' . $member['file'];
        $file['file'] = Horde::link(Chora::url('patchsets', $mywhere)) . htmlspecialchars($member['file']) . '</a>';
        if ($member['from'] == 'INITIAL') {
            $file['from'] = '<ins>' . _("New File") . '</ins>';
            $file['diff'] = '';
        } else {
            $file['from'] = Horde::link(Chora::url('co', $mywhere, array('r' => $member['from']))) . htmlspecialchars($member['from']) . '</a>';
            $file['diff'] = Horde::link(Chora::url('diff', $mywhere, array('r1' => $member['from'], 'r2' => $member['to'], 't' => 'unified'))) . ' ' . Horde::img('diff.png', _("Diff")) . '</a>';
        }
        if (substr($member['to'], -6) == '(DEAD)') {
            $file['to'] = '<del>' . _("Deleted") . '</del>';
            $file['diff'] = '';
        } else {
            $file['to'] = Horde::link(Chora::url('co', $mywhere, array('r' => $member['to']))) . htmlspecialchars($member['to']) . '</a>';
        }

        $files[] = $file;
    }

    $logMessage = Chora::formatLogMessage($patchset['log']);
    require CHORA_TEMPLATES . '/patchsets/ps.inc';
}

require CHORA_TEMPLATES . '/patchsets/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
