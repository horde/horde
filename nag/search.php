<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$page_output->addInlineScript(array(
    '$("search_pattern")'
), true);

$page_output->header(array(
    'title' => _("Search")
));

echo Nag::menu();
Nag::status();
require NAG_TEMPLATES . '/search/search.inc';
$GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('Nag_Ajax_Imple_TagAutoCompleter', array(
  'id' => 'search_tags'));
$page_output->footer();
