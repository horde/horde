<?php
/**
 * Patchsets script.
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
    Chora::url('browsefile', $where)->redirect();
}

$ps_opts = array();
if ($ps_id = Horde_Util::getFormData('ps')) {
    $ps_opts['range'] = array($ps_id);
    $title = sprintf(_("Patchset %s"), $ps_id);
}

if ($where) {
    $ps_opts['file'] = $where;
    if (!isset($title)) {
        $title = sprintf(_("Patchsets for %s"), $where);
    }
}

try {
    $ps = $VC->getPatchsetObject($ps_opts);
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

if (empty($patchsets)) {
    Chora::fatal(_("Patchset Not Found"), '400 Bad Request');
}

$extraLink = Chora::getFileViews($where, 'patchsets');

Horde::addScriptFile('tables.js', 'horde');

// JS search not needed if showing a single patchset
if ($ps_id) {
    Horde::addScriptFile('quickfinder.js', 'horde');
}

require CHORA_TEMPLATES . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';


if ($ps_id) {
    require CHORA_TEMPLATES . '/patchsets/header.inc';
} else {
    require CHORA_TEMPLATES . '/headerbar.inc';
    require CHORA_TEMPLATES . '/patchsets/header.inc';
    require CHORA_TEMPLATES . '/patchsets/header_table.inc';
}

$diff_img = Horde::img('diff.png', _("Diff"));

reset($patchsets);
while (list($id, $patchset) = each($patchsets)) {
    $patchset_link = Horde::link(Chora::url('patchsets', $where, array('ps' => $id)), sprintf("Patchset for %s", $id)) . htmlspecialchars($VC->abbrev($id)) . '</a>';

    $files = $tags = array();

    foreach ($patchset['members'] as $member) {
        $file = array();

        $file['file'] = Horde::link(Chora::url('co', $member['file'])) . htmlspecialchars($member['file']) . '</a>';

        if ($member['status'] == Horde_Vcs_Patchset::ADDED) {
            $file['from'] = '<ins>' . _("New File") . '</ins>';
            $file['diff'] = '';
        } else {
            $file['from'] = Horde::link(Chora::url('co', $member['file'], array('r' => $member['from'])), $member['from']) . htmlspecialchars($VC->abbrev($member['from'])) . '</a>';
            $file['diff'] = Horde::link(Chora::url('diff', $member['file'], array('r1' => $member['from'], 'r2' => $member['to'])), _("Diff")) . ' ' . $diff_img . '</a>';
        }

        if ($member['status'] == Horde_Vcs_Patchset::DELETED) {
            $file['to'] = '<del>' . _("Deleted") . '</del>';
            $file['diff'] = '';
        } else {
            $file['to'] = Horde::link(Chora::url('co', $member['file'], array('r' => $member['to'])), $member['to']) . htmlspecialchars($VC->abbrev($member['to'])) . '</a>';
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

    if ($ps_id) {
        require CHORA_TEMPLATES . '/patchsets/ps_single.inc';
    } else {
        require CHORA_TEMPLATES . '/patchsets/ps.inc';
    }
}

if (!$ps_id) {
    require CHORA_TEMPLATES . '/patchsets/footer.inc';
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
