<?php
/**
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once ANSEL_BASE . '/lib/Tags.php';

Horde::addScriptFile('prototype.js', 'horde', true);
$layout = new Horde_Block_Layout_View(
    @unserialize($prefs->getValue('myansel_layout')),
    Horde::applicationUrl('browse_edit.php'),
    Horde::applicationUrl('browse.php', true));

$layout_html = $layout->toHtml();
$title = _("Photo Galleries");
Ansel_Tags::clearSearch();
require ANSEL_BASE . '/templates/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
echo '<div id="menuBottom"><a href="' . Horde::applicationUrl('browse_edit.php') . '">' . _("Add Content") . '</a></div><div class="clear">&nbsp;</div>';
echo $layout_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
