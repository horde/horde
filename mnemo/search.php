<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Mnemo
 */
require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('mnemo');

$page_output->addInlineScript(array(
    '$("search_pattern").focus()'
), true);

$page_output->header(array(
    'title' => _("Search")
));
$notification->notify();
require MNEMO_TEMPLATES . '/search/search.inc';
$page_output->footer();
