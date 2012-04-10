<?php
/**
 * Gollem share proxy script.
 *
 * This script is just a proxy for horde/services/shares/edit.php that
 * makes sure that a folder share exists before trying to edit it.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did notcan receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('gollem');

/* Check if the user has permissions to create shares here. */
$share = Horde_Util::getFormData('share');
@list($backend_key, $dir) = explode('|', $share);
$backend = Gollem_Auth::getBackend($backend_key);

if (!$backend || empty($backend['shares']) ||
    strpos($dir, $backend['home']) !== 0) {
    throw new Gollem_Excception(_("You are not allowed to share this folder"));
}

/* Create a folder share if it doesn't exist yet. */
$shares = $injector->getInstance('Gollem_Shares');
if (!$shares->exists($share)) {
    $shareOb = $shares->newShare($registry->getAuth(), $share, basename($dir));
    $shares->addShare($shareOb);
}

/* Proceed with the regular share editing. */
Horde::url('services/shares/edit.php', true, array('app' => 'horde'))
    ->add(array('app' => 'gollem', 'share' => $share))
    ->redirect();
