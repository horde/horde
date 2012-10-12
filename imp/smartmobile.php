<?php
/**
 * IMP smartmobile view.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_SMARTMOBILE
));

$ob = new IMP_Smartmobile($injector->getInstance('Horde_Variables'));

$page_output->header(array(
    'title' => _("Mobile Mail"),
    'view' => $registry::VIEW_SMARTMOBILE
));

$ob->render();

$page_output->footer();
