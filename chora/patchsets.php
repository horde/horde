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
try {
    Horde_Registry::appInit('chora');
} catch (Exception $e) {
    Chora::fatal($e);
}

// Exit if patchset feature is not available.
if (!$GLOBALS['VC']->hasFeature('patchsets')) {
    Chora::url('browsefile', $where)->redirect();
}

$ps_opts = array();
if ($where) {
    $ps_opts['file'] = $where;
    if (!isset($title)) {
        $title = sprintf(_("Commits to %s"), $where);
    }
}

try {
    $ps = $VC->getPatchsetObject($ps_opts);
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

if (empty($patchsets)) {
    Chora::fatal(_("Commit Not Found"), '404 Not Found');
}

$extraLink = Chora::getFileViews($where, 'patchsets');

Horde::addScriptFile('tables.js', 'horde');
Horde::addScriptFile('quickfinder.js', 'horde');
require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/patchsets/header_table.inc';

$diff_img = Horde::img('diff.png', _("Diff"));

reset($patchsets);
while (list($id, $patchset) = each($patchsets)) {
    $patchset_link = Chora::url('patchsets', $where, array('ps' => $id))
        ->link(array('title' => sprintf("Commits to %s", $id)))
        . htmlspecialchars($VC->abbrev($id)) . '</a>';

    $commitDate = Chora::formatDate($patchset['log']->queryDate());
    $readableDate = Chora::readableTime($patchset['log']->queryDate(), true);
    $author = Chora::showAuthorName($patchset['log']->queryAuthor(), true);
    $logMessage = Chora::formatLogMessage($patchset['log']->queryLog());
    $tags = array_merge(
        $patchset['log']->queryBranch(),
        $patchset['log']->queryTags()
    );

    require CHORA_TEMPLATES . '/patchsets/ps.inc';
}

require CHORA_TEMPLATES . '/patchsets/footer.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
