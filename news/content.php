<?php
/**
 * $Id: content.php 479 2008-03-30 17:07:34Z duck $
 *
 * Copyright 2007 The Horde Project(http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information(GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */

require_once dirname(__FILE__) . '/lib/base.php';

// Default layout.
$layout = new Horde_Block_Layout_View(
    unserialize($prefs->getValue('news_layout')),
    Horde::applicationUrl('content_edit.php'));
$layout_html = $layout->toHtml();

$title = $registry->get('name');
require NEWS_TEMPLATES . '/common-header.inc';
require NEWS_TEMPLATES . '/menu.inc';
echo '<div id="menuBottom"><a href="' . Horde::applicationUrl('content_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
echo $layout_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
