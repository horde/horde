<?php
/**
 * Commit view
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Chora
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('chora');

// Exit if patchset feature is not available.
if (!$GLOBALS['VC']->hasFeature('patchsets')) {
    Chora::url('browsedir', $where)->redirect();
}

$commit_id = Horde_Util::getFormData('commit');
$title = sprintf(_("Commit %s"), $commit_id);

try {
    $ps = $VC->getPatchsetObject(array('range' => array($commit_id)));
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

if (empty($patchsets)) {
    Chora::fatal(_("Commit Not Found"), '404 Not Found');
}

$extraLink = Chora::getFileViews($where, 'patchsets');

Horde::addScriptFile('tables.js', 'horde');
require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';

$diff_img = Horde::img('diff.png', _("Diff"));

$files = $tags = array();

reset($patchsets);
$patchset = current($patchsets);
foreach ($patchset['members'] as $member) {
    $file = array();

    $file['file'] = Chora::url('co', $member['file'])->link()
        . htmlspecialchars($member['file']) . '</a>';

    if ($member['status'] == Horde_Vcs_Patchset::ADDED) {
        $file['from'] = '<ins>' . _("New File") . '</ins>';
        $file['diff'] = '';
    } else {
        $file['from'] = Chora::url('co', $member['file'], array('r' => $member['from']))
            ->link(array('title' => $member['from']))
            . htmlspecialchars($VC->abbrev($member['from'])) . '</a>';
        $file['diff'] = Chora::url('diff', $member['file'], array('r1' => $member['from'], 'r2' => $member['to']))
            ->link(array('title' => _("Diff")))
            . ' ' . $diff_img . '</a>';
    }

    if ($member['status'] == Horde_Vcs_Patchset::DELETED) {
        $file['to'] = '<del>' . _("Deleted") . '</del>';
        $file['diff'] = '';
    } else {
        $file['to'] = Chora::url('co', $member['file'], array('r' => $member['to']))
            ->link(array('title' => $member['to']))
            . htmlspecialchars($VC->abbrev($member['to'])) . '</a>';
    }

    if (isset($member['added'])) {
        $file['added'] = $member['added'];
        $file['deleted'] = $member['deleted'];
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

require CHORA_TEMPLATES . '/patchsets/ps_single.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
