<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$layout = new Horde_Core_Block_Layout_View(
    $injector->getInstance('Horde_Core_Factory_BlockCollection')->create(array('ansel'), 'myansel_layout')->getLayout(),
    Horde::url('browse_edit.php'),
    Horde::url('browse.php', true)
);

$layout_html = $layout->toHtml();
$title = _("Photo Galleries");
Ansel_Search_Tag::clearSearch();

Ansel::initJSVariables();
require $registry->get('templates', 'horde') . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
echo '<div id="menuBottom"><a href="' . Horde::url('browse_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
echo $layout_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
