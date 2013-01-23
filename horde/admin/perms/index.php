<?php
/**
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl LGPL-2
 * @package  Horde
 */

require_once __DIR__ . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:perms')
));

$perm_id = Horde_Util::getFormData('perm_id');

$page_output->header(array(
    'title' => _("Permissions Administration")
));
require HORDE_TEMPLATES . '/admin/menu.inc';

$ui = new Horde_Core_Perms_Ui($injector->getInstance('Horde_Perms'), $injector->getInstance('Horde_Core_Perms'));

echo '<h1 class="header">' . Horde::img('perms.png') . ' ' . _("Permissions") . '</h1>';
$ui->renderTree($perm_id);

$page_output->footer();
