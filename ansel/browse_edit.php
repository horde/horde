<?php
/**
 * Copyright 1999-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$blocks = $injector->getInstance('Horde_Core_Factory_BlockCollection')->create(array('ansel'), 'myansel_layout');
$layout = $blocks->getLayoutManager();

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'),
                Horde_Util::getFormData('url'));
if ($layout->updated()) {
    $prefs->setValue('myansel_layout', $layout->serialize());
}

$title = _("My Photos :: Add Content");
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
