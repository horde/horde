<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
$permission = 'perms';
Horde_Registry::appInit('horde');
if (!$registry->isAdmin() && 
    !$injector->getInstance('Horde_Perms')->hasPermission('horde:administration:'.$permission, $registry->getAuth(), Horde_Perms::SHOW)) {
    $registry->authenticateFailure('horde', new Horde_Exception(sprintf("Not an admin and no %s permission", $permission)));
}

$perm_id = Horde_Util::getFormData('perm_id');

$title = _("Permissions Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

$ui = new Horde_Core_Perms_Ui($injector->getInstance('Horde_Perms'), $injector->getInstance('Horde_Core_Perms'));

echo '<h1 class="header">' . Horde::img('perms.png') . ' ' . _("Permissions") . '</h1>';
$ui->renderTree($perm_id);

require HORDE_TEMPLATES . '/common-footer.inc';
