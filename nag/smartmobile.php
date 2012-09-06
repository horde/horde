<?php
/**
 * Nag smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Nag
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$ob = new Nag_Smartmobile($injector->getInstance('Horde_Variables'));

$page_output->header(array(
    'title' => _("My Tasks"),
    'view' => $registry::VIEW_SMARTMOBILE
));

$ob->render();
$page_output->footer();