<?php
/**
 * Dynamic display (DIMP) base page.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array(
    'impmode' => Horde_Registry::VIEW_DYNAMIC
));

/* Get site specific menu items. */
$dimp_menu = new IMP_Menu_Dimp(Horde_Menu::MASK_BASE);
$render_sidebar = $dimp_menu->render();
$dimp_menu->addJs();

$page_output->noDnsPrefetch();
$injector->getInstance('IMP_Ajax')->init('main');

require IMP_TEMPLATES . '/dimp/index.inc';
