<?php
/**
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

// Instantiate the blocks objects.
$blocks = $injector->getInstance('Horde_Core_Factory_BlockCollection')->create();
$layout_pref = @unserialize($prefs->getValue('portal_layout'));
if (!is_array($layout_pref)) {
    $layout_pref = array();
}
if (!count($layout_pref)) {
    $layout_pref = $blocks->getFixedBlocks();
}

$layout = new Horde_Core_Block_Layout_Manager($blocks, $layout_pref);

// Handle requested actions.
$layout->handle(Horde_Util::getFormData('action'),
                (int)Horde_Util::getFormData('row'),
                (int)Horde_Util::getFormData('col'));

if ($layout->updated()) {
    $prefs->setValue('portal_layout', $layout->serialize());
    if (Horde_Util::getFormData('url')) {
        $url = new Horde_Url(Horde_Util::getFormData('url'));
        $url->unique()->redirect();
    }
}

$title = _("My Portal Layout");
require HORDE_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
require HORDE_TEMPLATES . '/portal/edit.inc';
require HORDE_TEMPLATES . '/common-footer.inc';
