<?php
/**
 * Patchsets script.
 *
 * Copyright 1999-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Chora
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('chora');

// Exit if patchset feature is not available.
if (!$GLOBALS['VC']->hasFeature('patchsets')) {
    Chora::url('browsefile', $where)->redirect();
}

$ps_opts = array('timezone' => $prefs->getValue('timezone'));
if ($where) {
    $ps_opts['file'] = $where;
    if (!isset($title)) {
        $title = _("Commits to:");
    }
}

try {
    $ps = $VC->getPatchset($ps_opts);
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

if (empty($patchsets)) {
    Chora::fatal(_("Commit Not Found"), '404 Not Found');
}

$page_output->addScriptFile('tables.js', 'horde');
$page_output->addScriptFile('quickfinder.js', 'horde');
Chora::header($title);
echo Chora::getHistoryViews($where)->render('patchsets');
require CHORA_TEMPLATES . '/patchsets/header_table.inc';

$diff_img = Horde::img('diff.png', _("Diff"));

reset($patchsets);
while (list($id, $patchset) = each($patchsets)) {
    $patchset_link = Chora::url('commit', $where, array('commit' => $id))
        ->link(array('title' => $id))
        . htmlspecialchars($VC->abbrev($id)) . '</a>';

    $commitDate = Chora::formatDate($patchset['date']);
    $readableDate = Chora::readableTime($patchset['date'], true);
    $author = Chora::showAuthorName($patchset['author'], true);
    $logMessage = Chora::formatLogMessage($patchset['log']);
    $tags = array_merge($patchset['branch'], $patchset['tags']);

    require CHORA_TEMPLATES . '/patchsets/ps.inc';
}

require CHORA_TEMPLATES . '/patchsets/footer.inc';
$page_output->footer();
