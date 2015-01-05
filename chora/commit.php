<?php
/**
 * Commit view
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

// Cache the commit output for a week - it can be longer, since it should never
// change.
session_cache_expire(10080);
Horde_Registry::appInit('chora', array('session_cache_limiter' => 'public'));

// Exit if patchset feature is not available.
if (!$GLOBALS['VC']->hasFeature('patchsets')) {
    Chora::url('browsedir', $where)->redirect();
}

if (!($commit_id = Horde_Util::getFormData('commit'))) {
    Chora::fatal(_("No commit ID given"));
}

$title = sprintf(_("Commit %s"), $commit_id);

try {
    $ps = $VC->getPatchset(array(
        'range' => array($commit_id),
        'timezone' => $prefs->getValue('timezone')
    ));
    $patchsets = $ps->getPatchsets();
} catch (Horde_Vcs_Exception $e) {
    Chora::fatal($e);
}

if (empty($patchsets)) {
    Chora::fatal(_("Commit Not Found"), '404 Not Found');
}

reset($patchsets);
$patchset = current($patchsets);

$page_output->addScriptFile('tables.js', 'horde');
$commit_page = 1;

Chora::header($title);
require CHORA_TEMPLATES . '/patchsets/ps_single.inc';
$page_output->footer();
