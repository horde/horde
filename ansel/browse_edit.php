<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

// Instantiate the blocks objects.
$blocks = Horde_Block_Collection::singleton(array('ansel'));
$layout = Horde_Block_Layout_Manager::singleton('myphotos', $blocks, @unserialize($prefs->getValue('myansel_layout')));

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'),
                Horde_Util::getFormData('url'));
if ($layout->updated()) {
    $prefs->setValue('myansel_layout', $layout->serialize());
}

$title = _("My Photos :: Add Content");
require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
