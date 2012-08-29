<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$blocks = $injector->getInstance('Horde_Core_Factory_BlockCollection')->create(array('ansel'), 'myansel_layout');
$layout = $blocks->getLayoutManager();

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'));
if ($layout->updated()) {
    $prefs->setValue('myansel_layout', $layout->serialize());
    if (Horde_Util::getFormData('url')) {
        $url = new Horde_Url(Horde_Util::getFormData('url'));
        $url->unique()->redirect();
    }
}

$page_output->header(array(
    'title' => _("My Photos :: Add Content")
));
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/portal/edit.inc';
$page_output->footer();
