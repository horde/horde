<?php
/**
 * Commit view
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
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
    Chora::url('browsedir', $where)->redirect();
}

if (!($commit_id = Horde_Util::getFormData('commit'))) {
    Chora::fatal(_("No commit ID given"));
}

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
reset($patchsets);
$patchset = current($patchsets);

// Cache the commit output for a week - it can be longer, since it should never
// change.
header('Cache-Control: max-age=604800');

Horde::addScriptFile('tables.js', 'horde');
require $registry->get('templates', 'horde') . '/common-header.inc';
require CHORA_TEMPLATES . '/menu.inc';
require CHORA_TEMPLATES . '/headerbar.inc';
require CHORA_TEMPLATES . '/patchsets/ps_single.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
