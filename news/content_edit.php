<?php
/**
 * $Id: content_edit.php 803 2008-08-27 08:29:20Z duck $
 *
 * $Id: content_edit.php 803 2008-08-27 08:29:20Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
require_once dirname(__FILE__) . '/lib/base.php';

// Instantiate the blocks objects.
$blocks = &Horde_Block_Collection::singleton('news_layout', array('news'));
$layout = &Horde_Block_Layout_Manager::singleton('news_layout', $blocks, unserialize($prefs->getValue('news_layout')));

// Handle requested actions.
$layout->handle(Util::getFormData('action'),
                (int)Util::getFormData('row'),
                (int)Util::getFormData('col'));
if ($layout->updated()) {
    $prefs->setValue('news_layout', $layout->serialize());
}

$title = sprintf(_("%s :: Add Content"), $registry->get('name'));
require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';
$notification->notify(array('listeners' => 'status'));
require $registry->get('templates', 'horde') . '/portal/edit.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
