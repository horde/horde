<?php
/**
 * Turba smartmobile view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$ob = new Turba_Smartmobile($injector->getInstance('Horde_Variables'));

$page_output->header(array(
    'title' => _("Mobile Addressbook"),
    'view' => $registry::VIEW_SMARTMOBILE
));

$ob->render();

$page_output->footer();
