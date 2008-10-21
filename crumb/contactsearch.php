<?php
/**
 * $Horde$
 *
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Ben Klang <ben@alkaloid.net>
 */

@define('CRUMB_BASE', dirname(__FILE__));
require_once CRUMB_BASE . '/lib/base.php';
require_once 'Horde/Form.php';
require_once 'Horde/Form/Renderer.php';
require_once CRUMB_BASE . '/lib/Forms/ContactSearch.php';
require_once 'Horde/Variables.php';

$vars = Variables::getDefaultVariables();

$searchform = new Horde_Form_ContactSearch($vars);

$info = array();
if ($searchform->validate($vars)) {
echo "Success!";
}

$url = Horde::applicationUrl(basename(__FILE__));
$title = $searchform->getTitle();

require CRUMB_TEMPLATES . '/common-header.inc';
require CRUMB_TEMPLATES . '/menu.inc';

$searchform->renderActive(null, null, $url, 'post');

require $registry->get('templates', 'horde') . '/common-footer.inc';
