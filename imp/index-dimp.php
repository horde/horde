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

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'dimp'));

/* Get site specific menu items. */
$dimp_menu = new IMP_Menu_Dimp(Horde_Menu::MASK_BASE);
$render_sidebar = $dimp_menu->render();
$dimp_menu->addJs();

Horde::noDnsPrefetch();
IMP_Dimp::header('', array(
    array('dimpbase.js', 'imp'),
    array('viewport.js', 'imp'),
    array('dialog.js', 'imp'),
    array('mailbox-dimp.js', 'imp'),
    array('imp.js', 'imp'),
    array('contextsensitive.js', 'horde'),
    array('dragdrop2.js', 'horde'),
    array('popup.js', 'horde'),
    array('redbox.js', 'horde'),
    array('slider2.js', 'horde'),
    array('form_ghost.js', 'horde'),
    array('toggle_quotes.js', 'horde'),
));

require IMP_TEMPLATES . '/dimp/index.inc';
