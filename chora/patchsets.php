<?php
/**
 * Patchsets script.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/base.php';

// Exit if patchset feature is not available.
if (!$GLOBALS['VC']->hasFeature('patchsets')) {
    header('Location: ' . Chora::url('browsefile', $where));
    exit;
}

$ps_opts = array();
if ($ps_id = Horde_Util::getFormData('ps')) {
    $ps_opts['range'] = array($ps_id);
}

try {
    $ps = $VC->getPatchsetObject($where, $ps_opts);
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

$title = sprintf(_("Patchsets for %s"), $where);
$extraLink = Chora::getFileViews($where, 'patchsets');

Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('tables.js', 'horde', true);

// JS search not needed if showing a single patchset
if ($ps_id) {
    $full_ps_link = Horde::link(Chora::url('patchsets', $where)) . _("View All Patchsets for File") . '</a>';
} else {
    Horde::addScriptFile('QuickFinder.js', 'horde', true);
}

require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/patchsets/header.inc';

reset($patchsets);
while (list($id, $patchset) = each($patchsets)) {
    // The diff should be from the top of the source tree so as to
    // get all files.
    // @TODO: Fix this (support needs to be written in diff page)
    // $patchset_link = Horde::link(Chora::url('diff', substr($where, 0, strpos($where, '/', 1)), array('r1' => $id - 1, 'r2' => $id, 't' => 'unified'))) . $VC->abbrev($id) . '</a>';
    $patchset_link = htmlspecialchars($VC->abbrev($id));

    $files = $tags = array();
    $diff_img = Horde::img('diff.png', _("Diff"));

    foreach ($patchset['members'] as $member) {
        $file = array();

        $file['file'] = Horde::link(Chora::url('patchsets', $member['file'])) . htmlspecialchars($member['file']) . '</a>';

        if ($member['from'] == Horde_Vcs_Patchset::INITIAL) {
            $file['from'] = '<ins>' . _("New File") . '</ins>';
            $file['diff'] = '';
        } else {
            $file['from'] = Horde::link(Chora::url('co', $member['file'], array('r' => $member['from']))) . htmlspecialchars($VC->abbrev($member['from'])) . '</a>';
            $file['diff'] = Horde::link(Chora::url('diff', $member['file'], array('r1' => $member['from'], 'r2' => $member['to']))) . ' ' . $diff_img . '</a>';
        }

        if ($member['from'] == Horde_Vcs_Patchset::DEAD) {
            $file['to'] = '<del>' . _("Deleted") . '</del>';
            $file['diff'] = '';
        } else {
            $file['to'] = Horde::link(Chora::url('co', $member['file'], array('r' => $member['to']))) . htmlspecialchars($VC->abbrev($member['to'])) . '</a>';
        }

        $files[] = $file;
    }

    $commitDate = Chora::formatDate($patchset['date']);
    $readableDate = Chora::readableTime($patchset['date'], true);
    $author = Chora::showAuthorName($patchset['author'], true);
    $logMessage = Chora::formatLogMessage($patchset['log']);

    if (!empty($patchset['branch'])) {
        $tags = $patchset['branch'];
    }

    if (!empty($patchset['tag'])) {
        $tags = array_merge($tags, $patchset['tag']);
    }

    require CHORA_TEMPLATES . '/patchsets/ps.inc';
}

require CHORA_TEMPLATES . '/patchsets/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
