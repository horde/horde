<?php
/**
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$perm_id = Horde_Util::getFormData('perm_id');

$title = _("Permissions Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

$ui = new Horde_Core_Perms_Ui($injector->getInstance('Horde_Perms'));

echo '<h1 class="header">' . Horde::img('perms.png') . ' ' . _("Permissions") . '</h1>';
$ui->renderTree($perm_id);

require HORDE_TEMPLATES . '/common-footer.inc';
