<?php
/**
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('wicked');

if (!($text = Horde_Util::getFormData('page_text'))) {
    exit;
}

$page = new Wicked_Page();
$wiki = &$page->getProcessor();
$text = $wiki->transform($text);

$title = sprintf(_("Edit %s"), Horde_Util::getFormData('age'));
require $registry->get('templates', 'horde') . '/common-header.inc';
require WICKED_TEMPLATES . '/menu.inc';
require WICKED_TEMPLATES . '/edit/preview.inc';
require $registry->get('templates', 'horde') . '/common-footer.inc';
