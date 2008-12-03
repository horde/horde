<?php
/**
 * $Id: content_edit.php 229 2008-01-12 19:47:30Z duck $
 *
 * Copyright 2007 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/thomas/LICENSE.
 *
 * @author Duck <duck@obala.net>
 */

define('NEWS_BASE', dirname(__FILE__));
require_once NEWS_BASE . '/lib/base.php';

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
