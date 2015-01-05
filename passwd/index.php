<?php
/**
 * Main passwd script.
 *
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('passwd');

$ob = new Passwd_Basic($injector->getInstance('Horde_Variables'));

$status = $ob->status();

$page_output->header(array(
    'title' => _("Change Password"),
    'view' => $registry::VIEW_BASIC
));

echo $status;
$ob->render();

$page_output->footer();
